<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$order_number = $_SESSION['last_order'] ?? '';
if (empty($order_number)) {
    redirect('index.php');
}

include 'includes/header.php';
?>

<section class="py-5">
    <div class="container text-center" style="max-width: 600px;">
        <div class="success-checkmark">
            <i class="bi bi-check-lg"></i>
        </div>
        <h1 class="fw-bold mb-3">Order Placed Successfully!</h1>
        <p class="text-muted mb-4">Thank you for your purchase. Your order has been received and is being processed.</p>
        
        <div class="bg-light p-4 mb-4">
            <div class="small text-muted mb-1">Order Number</div>
            <div class="fs-4 fw-bold"><?php echo $order_number; ?></div>
        </div>

        <p class="text-muted mb-4">A confirmation email has been sent to your email address with the order details.</p>

        <a href="shop.php" class="btn btn-dark px-4">Continue Shopping</a>
    </div>
</section>

<?php 
unset($_SESSION['last_order']);
include 'includes/footer.php'; 
?>