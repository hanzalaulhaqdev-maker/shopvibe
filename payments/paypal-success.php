<?php
/**
 * PayPal Payment Success Handler
 * ================================
 * File: payments/paypal-success.php
 * 
 * PayPal redirects here with ?token=ORDER_ID&PayerID=XXX after user approves.
 * We capture the payment and create the order in our database.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/paypal-config.php';

$token   = $_GET['token']   ?? '';   // PayPal Order ID
$payerId = $_GET['PayerID'] ?? '';   // Payer ID (optional, for logging)

if (empty($token)) {
    $_SESSION['payment_error'] = 'PayPal payment was not completed.';
    redirect('../checkout.php');
}

// Capture the authorized payment
$capture = capturePayPalOrder($token);

if ($capture && isset($capture['status']) && $capture['status'] === 'COMPLETED') {

    $order_number = $_SESSION['paypal_order_number'] ?? generateOrderNumber();
    $checkout     = $_SESSION['checkout_data'] ?? [];

    $user_id    = $_SESSION['user_id'] ?? null;
    $session_id = session_id();

    $name    = sanitize($checkout['name']    ?? 'Guest');
    $email   = sanitize($checkout['email']   ?? '');
    $phone   = sanitize($checkout['phone']   ?? '');
    $address = sanitize($checkout['address'] ?? '');
    $city    = sanitize($checkout['city']    ?? '');
    $country = sanitize($checkout['country']  ?? '');
    $zip     = sanitize($checkout['zip']      ?? '');

    $shipping_address = "$address, $city, $country $zip";

    // Recalculate totals server-side
    $subtotal = getCartTotal();
    $discount = $_SESSION['applied_coupon']['discount'] ?? 0;
    $shipping = $subtotal > 100 ? 0 : 10;
    $total    = max(0, $subtotal + $shipping - $discount);

    $items_json = json_encode($_SESSION['cart']);

    // Extract PayPal transaction ID for refunds/lookup
    $paypal_txn = '';
    if (!empty($capture['purchase_units'][0]['payments']['captures'][0]['id'])) {
        $paypal_txn = $capture['purchase_units'][0]['payments']['captures'][0]['id'];
    }

    // === INSERT ORDER ===
    $stmt = $conn->prepare("INSERT INTO orders 
        (order_number, user_id, session_id, customer_name, customer_email, customer_phone, 
         shipping_address, items_json, subtotal, shipping_fee, discount, total, 
         payment_method, status, paypal_transaction_id, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paypal', 'processing', ?, NOW())");

    $stmt->bind_param(
        "sissssssdddds",
        $order_number, $user_id, $session_id,
        $name, $email, $phone,
        $shipping_address, $items_json,
        $subtotal, $shipping, $discount, $total,
        $paypal_txn
    );

    if ($stmt->execute()) {
        // Clear everything
        $_SESSION['cart'] = [];
        unset($_SESSION['applied_coupon']);
        unset($_SESSION['checkout_data']);
        unset($_SESSION['paypal_order_id']);
        unset($_SESSION['paypal_order_number']);

        $_SESSION['last_order'] = $order_number;
        redirect('../order-success.php');
    } else {
        $_SESSION['payment_error'] = 'Payment captured but order creation failed. Contact support with Order #: ' . $order_number;
        redirect('../checkout.php');
    }
    $stmt->close();

} else {
    $_SESSION['payment_error'] = 'PayPal payment could not be completed. Please try again.';
    redirect('../checkout.php');
}
