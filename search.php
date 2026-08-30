<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
include 'includes/header.php';

$query = sanitize($_GET['q'] ?? '');
$products = [];

if (!empty($query)) {
    $search = "%$query%";
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE (p.name LIKE ? OR p.description LIKE ?) AND p.status = 'active' ORDER BY p.name");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $products = $stmt->get_result();
    $stmt->close();
}
?>

<section class="search-hero">
    <div class="container">
        <h1 class="fw-bold mb-3">Search</h1>
        <form method="GET" action="" class="d-flex justify-content-center">
            <div class="input-group" style="max-width: 500px;">
                <input type="text" name="q" class="form-control form-control-lg" placeholder="Search products..." value="<?php echo $query; ?>">
                <button type="submit" class="btn btn-dark"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if (!empty($query)): ?>
        <p class="text-muted mb-4">Results for "<?php echo $query; ?>"</p>
        
        <?php if ($products && $products->num_rows > 0): ?>
        <div class="row g-4">
            <?php while ($product = $products->fetch_assoc()): ?>
            <div class="col-lg-3 col-md-4 col-6">
                <div class="product-card">
                    <div class="product-card-img-wrapper">
                        <?php if ($product['sale_price']): ?>
                        <span class="sale-badge">Sale</span>
                        <?php endif; ?>
                        <img src="assets/images/<?php echo $product['image_main']; ?>" alt="<?php echo $product['name']; ?>" class="img-main">
                        <img src="assets/images/<?php echo $product['image_hover']; ?>" alt="<?php echo $product['name']; ?>" class="img-hover">
                        <button class="quick-add-btn" data-id="<?php echo $product['id']; ?>">Quick Add</button>
                    </div>
                    <div class="product-card-info">
                        <h5 class="product-card-title"><a href="product.php?id=<?php echo $product['id']; ?>"><?php echo $product['name']; ?></a></h5>
                        <div class="product-card-price">
                            <?php if ($product['sale_price']): ?>
                            <span class="sale-price"><?php echo formatPrice($product['sale_price']); ?></span>
                            <span class="original-price"><?php echo formatPrice($product['price']); ?></span>
                            <?php else: ?>
                            <?php echo formatPrice($product['price']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-search fs-1 text-muted"></i>
            <h4 class="mt-3">No products found</h4>
            <p class="text-muted">Try different keywords or browse our categories.</p>
            <a href="shop.php" class="btn btn-dark">Browse All Products</a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>