<?php
/**
 * Payoneer Payment Initiator
 * ==========================
 * File: payments/payoneer-process.php
 * 
 * Called from checkout.php when user selects Payoneer.
 * Creates a Payoneer hosted checkout session and redirects user.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/payoneer-config.php';

// Guard: must have items in cart
if (empty($_SESSION['cart'])) {
    redirect('../cart.php');
}

// Guard: must have checkout data
if (empty($_SESSION['checkout_data'])) {
    redirect('../checkout.php');
}

$checkout = $_SESSION['checkout_data'];

// Recalculate totals server-side
$subtotal = getCartTotal();
$discount = $_SESSION['applied_coupon']['discount'] ?? 0;
$shipping = $subtotal > 100 ? 0 : 10;
$total    = max(0, $subtotal + $shipping - $discount);

$orderNumber = $checkout['order_number'] ?? generateOrderNumber();

// Build absolute URLs
$baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF']));
$returnUrl = rtrim($baseUrl, '/') . '/payments/payoneer-success.php';
$cancelUrl = rtrim($baseUrl, '/') . '/payments/payoneer-cancel.php';

// Create Payoneer payment session
$payment = createPayoneerPayment(
    $total,
    $orderNumber,
    sanitize($checkout['email'] ?? ''),
    $returnUrl,
    $cancelUrl
);

if ($payment && !empty($payment['redirect_url'])) {
    // Persist IDs in session
    $_SESSION['payoneer_payment_id']   = $payment['payment_id'] ?? '';
    $_SESSION['payoneer_order_number'] = $orderNumber;

    header('Location: ' . $payment['redirect_url']);
    exit;
}

// Fall-through = failure
$_SESSION['payment_error'] = 'Unable to initialize Payoneer. Please try again or use a different payment method.';
redirect('../checkout.php');
