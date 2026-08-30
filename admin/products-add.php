<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
include 'includes/admin-header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $sale_price = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
    $description = sanitize($_POST['description'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $sizes = isset($_POST['sizes']) ? json_encode($_POST['sizes']) : null;
    $colors = !empty($_POST['colors']) ? json_encode(explode(',', $_POST['colors'])) : null;
    $slug = generateSlug($name);

    // Handle image uploads
    $main_image = '';
    $hover_image = '';
    
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

    $stmt = $conn->prepare("INSERT INTO products (name, slug, category_id, price, sale_price, description, image_main, image_hover, sizes_json, colors_json, stock, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssidddssssii", $name, $slug, $category_id, $price, $sale_price, $description, $main_image, $hover_image, $sizes, $colors, $stock, $featured);
    
    if ($stmt->execute()) {
        $success = 'Product added successfully';
    } else {
        $error = 'Failed to add product. Slug may already exist.';
    }
    $stmt->close();
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Add Product</h4>
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
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category *</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Price *</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sale Price</label>
                    <input type="number" name="sale_price" class="form-control" step="0.01" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Stock *</label>
                    <input type="number" name="stock" class="form-control" min="0" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Main Image *</label>
                    <input type="file" name="main_image" class="form-control" accept="image/*" required>
                    <img id="main-image-preview" style="max-width: 150px; margin-top: 10px; display: none;">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Hover Image</label>
                    <input type="file" name="hover_image" class="form-control" accept="image/*">
                    <img id="hover-image-preview" style="max-width: 150px; margin-top: 10px; display: none;">
                </div>
                <div class="col-12">
                    <label class="form-label">Sizes (select all that apply)</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php $all_sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'One Size', '5', '6', '7', '8', '9', '10', '11', '12', '28', '30', '32', '34', '36']; ?>
                        <?php foreach ($all_sizes as $size): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sizes[]" value="<?php echo $size; ?>" id="size<?php echo $size; ?>">
                            <label class="form-check-label" for="size<?php echo $size; ?>"><?php echo $size; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Colors (comma separated, e.g. Red, Blue, Black)</label>
                    <input type="text" name="colors" class="form-control" placeholder="Red, Blue, Black">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="featured" id="featured">
                        <label class="form-check-label" for="featured">Featured Product</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-dark mt-4">Add Product</button>
        </form>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>