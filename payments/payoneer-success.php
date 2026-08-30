<?php
/**
 * Payoneer Payment Success Handler
 * =================================
 * File: payments/payoneer-success.php
 * 
 * Payoneer redirects here after customer completes payment.
 * We verify the payment status and create the order in our database.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/payoneer-config.php';

$paymentId = $_GET['payment_id'] ?? $_SESSION['payoneer_payment_id'] ?? '';

if (empty($paymentId)) {
    $_SESSION['payment_error'] = 'Payoneer payment was not completed.';
    redirect('../checkout.php');
}

// Verify payment status with Payoneer
$paymentStatus = getPayoneerPaymentStatus($paymentId);

$isApproved = false;
if ($paymentStatus) {
    $status = $paymentStatus['status'] ?? '';
    // Payoneer statuses: pending, authorized, captured, approved, declined, cancelled
    $isApproved = in_array($status, ['approved', 'captured', 'authorized']);
}

if ($isApproved) {

    $order_number = $_SESSION['payoneer_order_number'] ?? generateOrderNumber();
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

    // Recalculate totals
    $subtotal = getCartTotal();
    $discount = $_SESSION['applied_coupon']['discount'] ?? 0;
    $shipping = $subtotal > 100 ? 0 : 10;
    $total    = max(0, $subtotal + $shipping - $discount);

    $items_json = json_encode($_SESSION['cart']);

    // === INSERT ORDER ===
    $stmt = $conn->prepare("INSERT INTO orders 
        (order_number, user_id, session_id, customer_name, customer_email, customer_phone, 
         shipping_address, items_json, subtotal, shipping_fee, discount, total, 
         payment_method, status, payoneer_payment_id, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'payoneer', 'processing', ?, NOW())");

    $stmt->bind_param(
        "sissssssdddds",
        $order_number, $user_id, $session_id,
        $name, $email, $phone,
        $shipping_address, $items_json,
        $subtotal, $shipping, $discount, $total,
        $paymentId
    );

    if ($stmt->execute()) {
        // Clear session data
        $_SESSION['cart'] = [];
        unset($_SESSION['applied_coupon']);
        unset($_SESSION['checkout_data']);
        unset($_SESSION['payoneer_payment_id']);
        unset($_SESSION['payoneer_order_number']);

        $_SESSION['last_order'] = $order_number;
        redirect('../order-success.php');
    } else {
        $_SESSION['payment_error'] = 'Payment verified but order creation failed. Contact support with Order #: ' . $order_number;
        redirect('../checkout.php');
    }
    $stmt->close();

} else {
    $_SESSION['payment_error'] = 'Payoneer payment could not be verified. Please try again.';
    redirect('../checkout.php');
}
