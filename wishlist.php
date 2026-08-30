<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
if (!isLoggedIn()) redirect('login.php');

$user_id = $_SESSION['user_id'];

if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $remove_id);
    $stmt->execute();
    $stmt->close();
    redirect('wishlist.php');
}

$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM wishlist w JOIN products p ON w.product_id = p.id LEFT JOIN categories c ON p.category_id = c.id WHERE w.user_id = ? AND p.status = 'active' ORDER BY w.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wishlist_items = $stmt->get_result();
$stmt->close();

include 'includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <h1 class="fw-bold mb-4">My Wishlist</h1>
        <?php if ($wishlist_items->num_rows === 0): ?>
        <div class="text-center py-5">
            <i class="bi bi-heart fs-1 text-muted"></i>
            <h4 class="mt-3">Your wishlist is empty</h4>
            <p class="text-muted mb-4">Save items you love to your wishlist and revisit them anytime.</p>
            <a href="shop.php" class="btn btn-dark px-4">Start Shopping</a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php while ($item = $wishlist_items->fetch_assoc()): ?>
            <div class="col-lg-3 col-md-4 col-6">
                <div class="product-card h-100">
                    <div class="product-card-img-wrapper position-relative overflow-hidden rounded">
                        <?php if ($item['sale_price']): ?>
                        <span class="position-absolute top-0 start-0 m-2 badge bg-danger">Sale</span>
                        <?php endif; ?>
                        <a href="product.php?id=<?php echo $item['id']; ?>">
                            <img src="assets/images/<?php echo $item['image_main']; ?>" alt="<?php echo $item['name']; ?>" class="w-100" style="aspect-ratio:3/4;object-fit:cover">
                        </a>
                        <a href="wishlist.php?remove=<?php echo $item['id']; ?>" class="position-absolute top-0 end-0 m-2 btn btn-light btn-sm rounded-circle" onclick="return confirm('Remove from wishlist?')" title="Remove from wishlist">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                    <div class="product-card-info pt-3">
                        <h6 class="product-card-title mb-1">
                            <a href="product.php?id=<?php echo $item['id']; ?>" class="text-dark text-decoration-none fw-bold"><?php echo $item['name']; ?></a>
                        </h6>
                        <div class="product-card-price mb-2">
                            <?php if ($item['sale_price']): ?>
                            <span class="fw-bold text-danger"><?php echo formatPrice($item['sale_price']); ?></span>
                            <small class="text-muted text-decoration-line-through ms-1"><?php echo formatPrice($item['price']); ?></small>
                            <?php else: ?>
                            <span class="fw-bold"><?php echo formatPrice($item['price']); ?></span>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-dark btn-sm w-100 quick-add-btn" data-id="<?php echo $item['id']; ?>">
                            <i class="bi bi-bag me-1"></i>Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.querySelectorAll('.quick-add-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.id;
        fetch('ajax/add-to-cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `product_id=${productId}&quantity=1`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const cartBadge = document.getElementById('cart-count');
                if (cartBadge) cartBadge.textContent = data.cart_count;
                document.getElementById('cart-drawer')?.classList.add('open');
                document.getElementById('cart-overlay')?.classList.add('show');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>