<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_GET['ajax']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $order_stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $order_stmt->bind_param("i", $user_id);
    $order_stmt->execute();
    $user_orders = $order_stmt->get_result();
    $order_stmt->close();
    
    if ($user) {
?>
        <div class="row g-3">
            <div class="col-md-6">
                <h6 class="fw-bold">Customer Details</h6>
                <table class="table table-sm">
                    <tr><td>Name</td><td><?php echo $user['name']; ?></td></tr>
                    <tr><td>Email</td><td><?php echo $user['email']; ?></td></tr>
                    <tr><td>Phone</td><td><?php echo $user['phone'] ?: 'N/A'; ?></td></tr>
                    <tr><td>Address</td><td><?php echo nl2br($user['address'] ?: 'N/A'); ?></td></tr>
                    <tr><td>Registered</td><td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold">Order History</h6>
                <?php if ($user_orders->num_rows > 0): ?>
                <table class="table table-sm">
                    <thead>
                        <tr><th>Order #</th><th>Total</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($o = $user_orders->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $o['order_number']; ?></td>
                            <td><?php echo formatPrice($o['total']); ?></td>
                            <td><span class="status-badge status-<?php echo $o['status']; ?>"><?php echo ucfirst($o['status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($o['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted">No orders yet.</p>
                <?php endif; ?>
            </div>
        </div>
<?php
    }
    exit;
}

include 'includes/admin-header.php';

$customers = $conn->query("SELECT u.*, COUNT(o.id) as order_count FROM users u LEFT JOIN orders o ON u.id = o.user_id GROUP BY u.id ORDER BY u.created_at DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Customers</h4>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table admin-table" id="customers-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Orders</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $customers->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-bold"><?php echo $c['name']; ?></td>
                        <td><?php echo $c['email']; ?></td>
                        <td><?php echo $c['phone'] ?: '-'; ?></td>
                        <td><?php echo $c['order_count']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-dark view-customer" data-id="<?php echo $c['id']; ?>"><i class="bi bi-eye"></i> View</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Customer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#customers-table').DataTable({
        pageLength: 25,
        order: [[4, 'desc']]
    });
});
</script>

<?php include 'includes/admin-footer.php'; ?>