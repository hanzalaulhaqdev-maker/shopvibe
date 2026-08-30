<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        $stmt->close();
        exit;
    }

    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'active');
        $stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        echo json_encode(['success' => $stmt->execute()]);
        $stmt->close();
        exit;
    }
}

include 'includes/admin-header.php';

$products = $conn->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC
");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Products</h4>
    <a href="products-add.php" class="btn btn-dark"><i class="bi bi-plus-lg me-2"></i>Add Product</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table admin-table" id="products-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = $products->fetch_assoc()): ?>
                    <tr>
                        <td><img src="../assets/images/<?php echo $p['image_main']; ?>" style="width: 50px; height: 60px; object-fit: cover;"></td>
                        <td class="fw-bold"><?php echo $p['name']; ?></td>
                        <td><?php echo $p['category_name']; ?></td>
                        <td>
                            <?php if ($p['sale_price']): ?>
                            <span class="text-danger"><?php echo formatPrice($p['sale_price']); ?></span>
                            <small class="text-muted text-decoration-line-through"><?php echo formatPrice($p['price']); ?></small>
                            <?php else: ?>
                            <?php echo formatPrice($p['price']); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $p['stock']; ?></td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input status-toggle" type="checkbox" data-id="<?php echo $p['id']; ?>" <?php echo $p['status'] === 'active' ? 'checked' : ''; ?>>
                            </div>
                        </td>
                        <td>
                            <a href="products-edit.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-dark me-1"><i class="bi bi-pencil"></i></a>
                            <button class="btn btn-sm btn-outline-danger delete-product" data-id="<?php echo $p['id']; ?>"><i class="bi bi-trash"></i></button>
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
    $('#products-table').DataTable({
        pageLength: 25,
        order: [[1, 'asc']]
    });
});
</script>

<?php include 'includes/admin-footer.php'; ?>