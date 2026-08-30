<?php
/**
 * PayPal Payment Cancel Handler
 * =============================
 * File: payments/paypal-cancel.php
 * 
 * User clicked "Cancel and return to ShopVibe" on PayPal.
 */

require_once __DIR__ . '/../includes/functions.php';

$_SESSION['payment_error'] = 'PayPal payment was cancelled. You can review your order and try again.';
redirect('../checkout.php');
