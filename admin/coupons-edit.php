<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
include 'includes/admin-header.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: coupons.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM coupons WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$coupon = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$coupon) {
    header('Location: coupons.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $type = sanitize($_POST['type'] ?? 'percent');
    $discount = floatval($_POST['discount'] ?? 0);
    $min_order = floatval($_POST['min_order'] ?? 0);
    $max_uses = !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : 100;
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($code)) {
        $error = 'Coupon code is required';
    } elseif ($discount <= 0) {
        $error = 'Discount must be greater than 0';
    } elseif ($type === 'percent' && $discount > 100) {
        $error = 'Percentage cannot exceed 100%';
    } else {
        $stmt = $conn->prepare("UPDATE coupons SET code = ?, type = ?, discount = ?, min_order = ?, max_uses = ?, expires_at = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("ssddisii", $code, $type, $discount, $min_order, $max_uses, $expires_at, $is_active, $id);
        
        if ($stmt->execute()) {
            $success = 'Coupon updated successfully';
            $stmt2 = $conn->prepare("SELECT * FROM coupons WHERE id = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $coupon = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
        } else {
            $error = 'Failed to update coupon. Code may already exist.';
        }
        $stmt->close();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Edit Coupon</h4>
    <a href="coupons.php" class="btn btn-outline-dark"><i class="bi bi-arrow-left me-2"></i>Back to Coupons</a>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Coupon Code *</label>
                    <input type="text" name="code" class="form-control text-uppercase" required 
                           value="<?php echo $coupon['code']; ?>">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                               <?php echo $coupon['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Discount Type *</label>
                    <select name="type" class="form-select" id="couponType">
                        <option value="percent" <?php echo $coupon['type'] === 'percent' ? 'selected' : ''; ?>>Percentage (%)</option>
                        <option value="fixed" <?php echo $coupon['type'] === 'fixed' ? 'selected' : ''; ?>>Fixed Amount ($)</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Discount Value *</label>
                    <div class="input-group">
                        <span class="input-group-text" id="valuePrefix"><?php echo $coupon['type'] === 'percent' ? '%' : '$'; ?></span>
                        <input type="number" name="discount" class="form-control" step="0.01" min="0.01" required
                               value="<?php echo $coupon['discount']; ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Minimum Order Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="min_order" class="form-control" step="0.01" min="0"
                               value="<?php echo $coupon['min_order']; ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Maximum Uses</label>
                    <input type="number" name="max_uses" class="form-control" min="1" 
                           value="<?php echo $coupon['max_uses']; ?>">
                    <small class="text-muted">Already used: <?php echo $coupon['used_count']; ?> times</small>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expires_at" class="form-control"
                           value="<?php echo $coupon['expires_at']; ?>">
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-dark">Update Coupon</button>
                <a href="coupons.php" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('couponType').addEventListener('change', function() {
    document.getElementById('valuePrefix').textContent = this.value === 'percent' ? '%' : '$';
});
</script>

<?php include 'includes/admin-footer.php'; ?>