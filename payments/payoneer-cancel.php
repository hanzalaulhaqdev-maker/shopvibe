<?php
/**
 * Payoneer Payment Cancel Handler
 * ================================
 * File: payments/payoneer-cancel.php
 * 
 * User cancelled or failed payment on Payoneer's hosted page.
 */

require_once __DIR__ . '/../includes/functions.php';

$_SESSION['payment_error'] = 'Payoneer payment was cancelled. You can review your order and try again.';
redirect('../checkout.php');
