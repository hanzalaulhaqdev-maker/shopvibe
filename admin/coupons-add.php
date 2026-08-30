<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
include 'includes/admin-header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $type = sanitize($_POST['type'] ?? 'percent');
    $discount = floatval($_POST['discount'] ?? 0);
    $min_order = floatval($_POST['min_order'] ?? 0);
    $max_uses = !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : 100;
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

    if (empty($code)) {
        $error = 'Coupon code is required';
    } elseif (strlen($code) < 3) {
        $error = 'Code must be at least 3 characters';
    } elseif ($discount <= 0) {
        $error = 'Discount must be greater than 0';
    } elseif ($type === 'percent' && $discount > 100) {
        $error = 'Percentage cannot exceed 100%';
    } else {
        $stmt = $conn->prepare("INSERT INTO coupons (code, type, discount, min_order, max_uses, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddis", $code, $type, $discount, $min_order, $max_uses, $expires_at);
        
        if ($stmt->execute()) {
            $success = 'Coupon created successfully';
            $_POST = [];
        } else {
            $error = 'Failed to create coupon. Code may already exist.';
        }
        $stmt->close();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Add Coupon</h4>
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
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-ticket-perforated"></i></span>
                        <input type="text" name="code" class="form-control text-uppercase" required 
                               value="<?php echo $_POST['code'] ?? ''; ?>" 
                               placeholder="e.g. SUMMER2024">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Discount Type *</label>
                    <select name="type" class="form-select" id="couponType">
                        <option value="percent" <?php echo ($_POST['type'] ?? '') === 'percent' ? 'selected' : ''; ?>>Percentage (%)</option>
                        <option value="fixed" <?php echo ($_POST['type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>Fixed Amount ($)</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Discount Value *</label>
                    <div class="input-group">
                        <span class="input-group-text" id="valuePrefix">%</span>
                        <input type="number" name="discount" class="form-control" step="0.01" min="0.01" required
                               value="<?php echo $_POST['discount'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Minimum Order Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="min_order" class="form-control" step="0.01" min="0"
                               value="<?php echo $_POST['min_order'] ?? '0'; ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Maximum Uses</label>
                    <input type="number" name="max_uses" class="form-control" min="1" 
                           value="<?php echo $_POST['max_uses'] ?? '100'; ?>">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expires_at" class="form-control"
                           value="<?php echo $_POST['expires_at'] ?? ''; ?>">
                    <small class="text-muted">Leave empty for no expiry</small>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-dark">Create Coupon</button>
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