<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo 'Invalid order';
    exit;
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo 'Order not found';
    exit;
}

$items = json_decode($order['items_json'], true) ?: [];

if (isset($_GET['ajax'])) {
?>
    <div class="row g-4">
        <div class="col-md-6">
            <h6 class="fw-bold mb-3">Order Information</h6>
            <table class="table table-sm">
                <tr><td>Order Number</td><td class="fw-bold"><?php echo $order['order_number']; ?></td></tr>
                <tr><td>Date</td><td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td></tr>
                <tr><td>Status</td><td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td></tr>
                <tr><td>Payment</td><td><?php echo ucfirst($order['payment_method']); ?></td></tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6 class="fw-bold mb-3">Customer Information</h6>
            <table class="table table-sm">
                <tr><td>Name</td><td><?php echo $order['customer_name']; ?></td></tr>
                <tr><td>Email</td><td><?php echo $order['customer_email']; ?></td></tr>
                <tr><td>Phone</td><td><?php echo $order['customer_phone']; ?></td></tr>
                <tr><td>Address</td><td><?php echo nl2br($order['shipping_address']); ?></td></tr>
            </table>
        </div>
    </div>
    <h6 class="fw-bold mt-4 mb-3">Order Items</h6>
    <table class="table table-sm">
        <thead>
            <tr><th>Product ID</th><th>Size</th><th>Color</th><th>Qty</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo $item['product_id']; ?></td>
                <td><?php echo $item['size'] ?: '-'; ?></td>
                <td><?php echo $item['color'] ?: '-'; ?></td>
                <td><?php echo $item['quantity']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="text-end mt-3">
        <div>Subtotal: <?php echo formatPrice($order['subtotal']); ?></div>
        <div>Shipping: <?php echo formatPrice($order['shipping_fee']); ?></div>
        <?php if ($order['discount'] > 0): ?>
        <div>Discount: -<?php echo formatPrice($order['discount']); ?></div>
        <?php endif; ?>
        <div class="fw-bold fs-5 mt-2">Total: <?php echo formatPrice($order['total']); ?></div>
    </div>
<?php
} else {
    include 'includes/admin-header.php';
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Order <?php echo $order['order_number']; ?></h4>
        <a href="orders.php" class="btn btn-outline-dark"><i class="bi bi-arrow-left me-2"></i>Back to Orders</a>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3">Order Information</h6>
                    <table class="table table-sm">
                        <tr><td>Order Number</td><td class="fw-bold"><?php echo $order['order_number']; ?></td></tr>
                        <tr><td>Date</td><td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td></tr>
                        <tr><td>Status</td><td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td></tr>
                        <tr><td>Payment</td><td><?php echo ucfirst($order['payment_method']); ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3">Customer Information</h6>
                    <table class="table table-sm">
                        <tr><td>Name</td><td><?php echo $order['customer_name']; ?></td></tr>
                        <tr><td>Email</td><td><?php echo $order['customer_email']; ?></td></tr>
                        <tr><td>Phone</td><td><?php echo $order['customer_phone']; ?></td></tr>
                        <tr><td>Address</td><td><?php echo nl2br($order['shipping_address']); ?></td></tr>
                    </table>
                </div>
            </div>
            <h6 class="fw-bold mt-4 mb-3">Order Items</h6>
            <table class="table">
                <thead>
                    <tr><th>Product ID</th><th>Size</th><th>Color</th><th>Qty</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo $item['product_id']; ?></td>
                        <td><?php echo $item['size'] ?: '-'; ?></td>
                        <td><?php echo $item['color'] ?: '-'; ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="text-end mt-3">
                <div>Subtotal: <?php echo formatPrice($order['subtotal']); ?></div>
                <div>Shipping: <?php echo formatPrice($order['shipping_fee']); ?></div>
                <?php if ($order['discount'] > 0): ?>
                <div>Discount: -<?php echo formatPrice($order['discount']); ?></div>
                <?php endif; ?>
                <div class="fw-bold fs-5 mt-2">Total: <?php echo formatPrice($order['total']); ?></div>
            </div>
        </div>
    </div>
<?php
    include 'includes/admin-footer.php';
}