<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        $stmt->close();
        exit;
    }
    
    if ($action === 'expire') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE coupons SET is_active = 0, expires_at = CURDATE() WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        $stmt->close();
        exit;
    }
    
    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        $active = intval($_POST['active'] ?? 1);
        $stmt = $conn->prepare("UPDATE coupons SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $active, $id);
        echo json_encode(['success' => $stmt->execute()]);
        $stmt->close();
        exit;
    }
}

include 'includes/admin-header.php';

// Auto-expire coupons past their date
$conn->query("UPDATE coupons SET is_active = 0 WHERE expires_at < CURDATE() AND is_active = 1");

$coupons = $conn->query("
    SELECT c.*, 
           CASE 
               WHEN c.expires_at < CURDATE() THEN 'expired'
               WHEN c.used_count >= c.max_uses THEN 'expired'
               WHEN c.is_active = 0 THEN 'disabled'
               ELSE 'active'
           END as current_status
    FROM coupons c 
    ORDER BY c.id DESC
");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Coupons</h4>
    <a href="coupons-add.php" class="btn btn-dark"><i class="bi bi-plus-lg me-2"></i>Add Coupon</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table admin-table" id="coupons-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Discount</th>
                        <th>Usage</th>
                        <th>Min Order</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $coupons->fetch_assoc()): 
                        $isExpired = $c['current_status'] === 'expired';
                        $usagePercent = $c['max_uses'] > 0 ? ($c['used_count'] / $c['max_uses'] * 100) : 0;
                    ?>
                    <tr>
                        <td class="fw-bold font-monospace"><?php echo strtoupper($c['code']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $c['type'] === 'percent' ? 'info' : 'secondary'; ?>">
                                <?php echo $c['type'] === 'percent' ? 'Percentage' : 'Fixed'; ?>
                            </span>
                        </td>
                        <td class="fw-bold">
                            <?php echo $c['type'] === 'percent' ? $c['discount'] . '%' : formatPrice($c['discount']); ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                    <div class="progress-bar <?php echo $usagePercent >= 90 ? 'bg-danger' : 'bg-success'; ?>" 
                                         style="width: <?php echo min($usagePercent, 100); ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo $c['used_count']; ?>/<?php echo $c['max_uses']; ?></small>
                            </div>
                        </td>
                        <td><?php echo $c['min_order'] > 0 ? formatPrice($c['min_order']) : '-'; ?></td>
                        <td>
                            <small class="<?php echo $isExpired ? 'text-danger' : 'text-muted'; ?>">
                                <?php echo $c['expires_at'] ? date('M d, Y', strtotime($c['expires_at'])) : 'Never'; ?>
                            </small>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $c['current_status']; ?>">
                                <?php echo ucfirst($c['current_status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="coupons-edit.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-dark me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if (!$isExpired): ?>
                            <button class="btn btn-sm btn-outline-warning expire-coupon me-1" data-id="<?php echo $c['id']; ?>" title="Expire Now">
                                <i class="bi bi-clock-history"></i>
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-danger delete-coupon" data-id="<?php echo $c['id']; ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#coupons-table').DataTable({
        pageLength: 25,
        order: [[6, 'desc']]
    });
    
    $('.expire-coupon').on('click', function() {
        const id = $(this).data('id');
        if (confirm('Expire this coupon immediately?')) {
            $.post('coupons.php', {action: 'expire', id: id}, function(res) {
                if (res.success) location.reload();
            });
        }
    });
    
    $('.delete-coupon').on('click', function() {
        const id = $(this).data('id');
        if (confirm('Delete this coupon permanently?')) {
            $.post('coupons.php', {action: 'delete', id: id}, function(res) {
                if (res.success) location.reload();
            });
        }
    });
});
</script>

<?php include 'includes/admin-footer.php'; ?>