<?php
/**
 * PayPal Payment Initiator
 * ========================
 * File: payments/paypal-process.php
 * 
 * Called from checkout.php when user selects PayPal.
 * Creates a PayPal order and redirects user to PayPal for approval.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/paypal-config.php';

// Guard: must have items in cart
if (empty($_SESSION['cart'])) {
    redirect('../cart.php');
}

// Guard: must have checkout data saved in session
if (empty($_SESSION['checkout_data'])) {
    redirect('../checkout.php');
}

$checkout = $_SESSION['checkout_data'];

// Recalculate totals (trust nothing from client)
$subtotal = getCartTotal();
$discount = $_SESSION['applied_coupon']['discount'] ?? 0;
$shipping = $subtotal > 100 ? 0 : 10;
$total    = max(0, $subtotal + $shipping - $discount);

$orderNumber = $checkout['order_number'] ?? generateOrderNumber();

// Build absolute return/cancel URLs
$baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF']));
$returnUrl = rtrim($baseUrl, '/') . '/payments/paypal-success.php';
$cancelUrl = rtrim($baseUrl, '/') . '/payments/paypal-cancel.php';

// Create PayPal order
$paypalOrder = createPayPalOrder($total, $orderNumber, $returnUrl, $cancelUrl);

if ($paypalOrder && isset($paypalOrder['id'])) {
    // Persist IDs in session so success.php can capture
    $_SESSION['paypal_order_id']     = $paypalOrder['id'];
    $_SESSION['paypal_order_number'] = $orderNumber;

    // Find the approval URL and redirect user there
    foreach ($paypalOrder['links'] as $link) {
        if ($link['rel'] === 'approve') {
            header('Location: ' . $link['href']);
            exit;
        }
    }
}

// Fall-through = failure
$_SESSION['payment_error'] = 'Unable to initialize PayPal. Please try again or use a different payment method.';
redirect('../checkout.php');
