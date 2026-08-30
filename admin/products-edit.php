<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    header('Content-Type: application/json');
    $id = intval($_POST['id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'active');
    $stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    echo json_encode(['success' => $stmt->execute()]);
    $stmt->close();
    exit;
}

include 'includes/admin-header.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: products.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: products.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $name = sanitize($_POST['name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $sale_price = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
    $description = sanitize($_POST['description'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $sizes = isset($_POST['sizes']) ? json_encode($_POST['sizes']) : null;
    $colors = !empty($_POST['colors']) ? json_encode(explode(',', $_POST['colors'])) : null;
    $status = sanitize($_POST['status'] ?? 'active');
    $slug = generateSlug($name);

    $main_image = $product['image_main'];
    $hover_image = $product['image_hover'];

    if (!empty($_FILES['main_image']['name'])) {
        $ext = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
        $main_image = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['main_image']['tmp_name'], '../assets/images/' . $main_image);
    }

    if (!empty($_FILES['hover_image']['name'])) {
        $ext = pathinfo($_FILES['hover_image']['name'], PATHINFO_EXTENSION);
        $hover_image = uniqid() . '_hover.' . $ext;
        move_uploaded_file($_FILES['hover_image']['tmp_name'], '../assets/images/' . $hover_image);
    }

    $stmt = $conn->prepare("UPDATE products SET name = ?, slug = ?, category_id = ?, price = ?, sale_price = ?, description = ?, image_main = ?, image_hover = ?, sizes_json = ?, colors_json = ?, stock = ?, featured = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssidddssssiisi", $name, $slug, $category_id, $price, $sale_price, $description, $main_image, $hover_image, $sizes, $colors, $stock, $featured, $status, $id);

    if ($stmt->execute()) {
        $success = 'Product updated successfully';
        // Refresh product data
        $stmt2 = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $product = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
    } else {
        $error = 'Failed to update product.';
    }
    $stmt->close();
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$product_sizes = json_decode($product['sizes_json'], true) ?: [];
$product_colors = json_decode($product['colors_json'], true) ?: [];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Edit Product</h4>
    <a href="products.php" class="btn btn-outline-dark"><i class="bi bi-arrow-left me-2"></i>Back to Products</a>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Product Name *</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo $product['name']; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category *</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php 
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price *</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" required value="<?php echo $product['price']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sale Price</label>
                    <input type="number" name="sale_price" class="form-control" step="0.01" min="0" value="<?php echo $product['sale_price']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Stock *</label>
                    <input type="number" name="stock" class="form-control" min="0" required value="<?php echo $product['stock']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo $product['description']; ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Main Image</label>
                    <input type="file" name="main_image" class="form-control" accept="image/*">
                    <img src="../assets/images/<?php echo $product['image_main']; ?>" style="max-width: 150px; margin-top: 10px;">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Hover Image</label>
                    <input type="file" name="hover_image" class="form-control" accept="image/*">
                    <img src="../assets/images/<?php echo $product['image_hover']; ?>" style="max-width: 150px; margin-top: 10px;">
                </div>
                <div class="col-12">
                    <label class="form-label">Sizes</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php $all_sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'One Size', '5', '6', '7', '8', '9', '10', '11', '12', '28', '30', '32', '34', '36']; ?>
                        <?php foreach ($all_sizes as $size): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sizes[]" value="<?php echo $size; ?>" id="size<?php echo $size; ?>" <?php echo in_array($size, $product_sizes) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="size<?php echo $size; ?>"><?php echo $size; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Colors (comma separated)</label>
                    <input type="text" name="colors" class="form-control" value="<?php echo implode(', ', $product_colors); ?>">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="featured" id="featured" <?php echo $product['featured'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="featured">Featured Product</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-dark mt-4">Update Product</button>
        </form>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>