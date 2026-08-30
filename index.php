<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
include 'includes/header.php';

$cat_result = $conn->query("SELECT * FROM categories WHERE slug != 'sale' ORDER BY id LIMIT 4");
$categories = [];
while ($row = $cat_result->fetch_assoc()) $categories[] = $row;

$feat_stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.featured = 1 AND p.status = 'active' ORDER BY p.created_at DESC LIMIT 8");
$feat_stmt->execute();
$featured = $feat_stmt->get_result();

$new_stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.created_at DESC LIMIT 4");
$new_stmt->execute();
$new_arrivals = $new_stmt->get_result();

$sale_stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.sale_price IS NOT NULL AND p.status = 'active' ORDER BY p.created_at DESC LIMIT 4");
$sale_stmt->execute();
$sale_products = $sale_stmt->get_result();

$user_wishlist = [];
if (isLoggedIn()) {
    $w_stmt = $conn->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $w_stmt->bind_param("i", $_SESSION['user_id']);
    $w_stmt->execute();
    $w_result = $w_stmt->get_result();
    while ($w = $w_result->fetch_assoc()) $user_wishlist[] = $w['product_id'];
    $w_stmt->close();
}
?>

<style>
.product-card-img-wrapper { position: relative; overflow: hidden; }
.product-card-img-wrapper .img-main { transition: opacity 0.3s, transform 0.4s ease; }
.product-card-img-wrapper .img-hover { position: absolute; top: 0; left: 0; opacity: 0; transition: opacity 0.3s; pointer-events: none; }
.product-card:hover .img-main { transform: scale(1.05); }
.product-card:hover .img-hover { opacity: 1; }

.product-img-link { display: block; position: relative; }
.wishlist-heart { position: absolute; top: 10px; right: 10px; width: 36px; height: 36px; border-radius: 50%; background: white; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 0; transition: opacity 0.3s, transform 0.2s; z-index: 10; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.product-card:hover .wishlist-heart { opacity: 1; }
.wishlist-heart:hover { transform: scale(1.1); }
.wishlist-heart.active { opacity: 1; background: #dc3545; }
.wishlist-heart.active i { color: white; }

.shop-btn-group { transform: translateY(100%); transition: transform 0.3s ease; z-index: 10; pointer-events: auto; }
.product-card:hover .shop-btn-group { transform: translateY(0); }

.hero-section { position: relative; height: 80vh; min-height: 500px; overflow: hidden; }
.hero-layer { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center; transition: opacity 1s; }
.hero-content-panel {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 10;
    color: white;
    text-align: center;
    width: 100%;
    max-width: 700px;
}
.hero-title { font-size: 4rem; font-weight: 700; line-height: 1.1; }
.btn-shop-now { background: white; color: black; padding: 12px 30px; text-decoration: none; font-weight: 600; margin-right: 15px; display: inline-block; }
.btn-lookbook { border: 2px solid white; color: white; padding: 12px 30px; text-decoration: none; font-weight: 600; display: inline-block; }

.category-card { position: relative; overflow: hidden; height: 300px; }
.category-card img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
.category-card:hover img { transform: scale(1.1); }
.category-card-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.7)); padding: 30px 20px; }
.category-card-title { color: white; font-size: 1.5rem; font-weight: 700; }

.flash-sale-section { background: #1a1a1a; color: white; padding: 80px 0; }
.countdown-box { display: flex; justify-content: center; gap: 30px; margin-top: 30px; }
.countdown-item { text-align: center; }
.countdown-item .number { display: block; font-size: 3rem; font-weight: 700; }
.countdown-item .label { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 2px; opacity: 0.7; }

.testimonial-card { text-align: center; padding: 40px; }
.testimonial-text { font-size: 1.2rem; font-style: italic; margin-bottom: 20px; }
.testimonial-author { font-weight: 700; }
.testimonial-role { color: #666; font-size: 0.9rem; }

.section-title { font-size: 2.5rem; font-weight: 700; }
.section-subtitle { color: #666; font-size: 1.1rem; }
</style>

<!-- Hero -->
<div class="hero-section">
    <div class="hero-layer" id="hero-a" style="background-image:url('assets/images/hero1.png')"></div>
    <div class="hero-layer" id="hero-b" style="background-image:url('assets/images/hero2.png');opacity:0"></div>
    <div class="hero-content-panel">
        <span class="season-label">Spring Summer 2025</span>
        <h1 class="hero-title">New Collection<br><em>2025</em></h1>
        <p>Discover styles that define you</p>
        <a href="shop.php" class="btn-shop-now">Shop Now</a>
        <a href="shop.php" class="btn-lookbook">View Lookbook</a>
    </div>
</div>

<!-- Categories -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Shop by Category</h2>
            <p class="section-subtitle">Find your perfect style</p>
        </div>
        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
            <div class="col-6 col-lg-3">
                <a href="shop.php?category=<?php echo $cat['slug']; ?>" class="text-decoration-none">
                    <div class="category-card">
                        <img src="assets/images/<?php echo $cat['image']; ?>" alt="<?php echo $cat['name']; ?>">
                        <div class="category-card-overlay">
                            <span class="category-card-title"><?php echo $cat['name']; ?></span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Featured Products</h2>
            <p class="section-subtitle">Handpicked for you</p>
        </div>
        <div class="row g-4">
            <?php while ($product = $featured->fetch_assoc()):
                $in_wishlist = in_array($product['id'], $user_wishlist);
            ?>
            <div class="col-lg-3 col-md-4 col-6">
                <div class="product-card h-100">
                    <div class="product-card-img-wrapper position-relative overflow-hidden rounded">
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="product-img-link d-block">
                            <?php if ($product['sale_price']): ?>
                            <span class="badge bg-danger position-absolute top-0 start-0 m-2" style="z-index:2">Sale</span>
                            <?php endif; ?>
                            <img src="assets/images/<?php echo $product['image_main']; ?>" alt="<?php echo $product['name']; ?>" class="img-main w-100" style="aspect-ratio:3/4;object-fit:cover">
                            <img src="assets/images/<?php echo $product['image_hover']; ?>" alt="<?php echo $product['name']; ?>" class="img-hover w-100" style="aspect-ratio:3/4;object-fit:cover">
                        </a>
                        <button class="wishlist-heart <?php echo $in_wishlist ? 'active' : ''; ?>" data-id="<?php echo $product['id']; ?>" title="<?php echo $in_wishlist ? 'Remove from' : 'Add to'; ?> wishlist">
                            <i class="bi <?php echo $in_wishlist ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                        </button>
                        <div class="shop-btn-group d-flex gap-2 position-absolute bottom-0 start-0 end-0 p-2">
                            <button class="quick-add-btn btn btn-dark btn-sm flex-grow-1" data-id="<?php echo $product['id']; ?>">Add to Cart</button>
                            <button class="buy-now-btn btn btn-primary btn-sm flex-grow-1" data-id="<?php echo $product['id']; ?>">Buy Now</button>
                        </div>
                    </div>
                    <div class="product-card-info pt-3">
                        <h6 class="product-card-title mb-1">
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="text-dark text-decoration-none fw-bold"><?php echo $product['name']; ?></a>
                        </h6>
                        <div class="product-card-price">
                            <?php if ($product['sale_price']): ?>
                            <span class="text-danger fw-bold"><?php echo formatPrice($product['sale_price']); ?></span>
                            <small class="text-muted text-decoration-line-through ms-1"><?php echo formatPrice($product['price']); ?></small>
                            <?php else: ?>
                            <span class="fw-bold"><?php echo formatPrice($product['price']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <div class="text-center mt-4">
            <a href="shop.php" class="btn btn-outline-dark px-4">View All Products</a>
        </div>
    </div>
</section>

<!-- Flash Sale -->
<section class="flash-sale-section">
    <div class="container text-center">
        <span class="text-uppercase letter-spacing-2 small">Limited Time Offer</span>
        <h2 class="display-4 fw-bold mt-2 mb-3">Flash Sale</h2>
        <p class="opacity-75 mb-4">Up to 50% off on selected items. Don't miss out!</p>
        <div class="countdown-box">
            <div class="countdown-item"><span class="number" id="countdown-hours">00</span><span class="label">Hours</span></div>
            <div class="countdown-item"><span class="number" id="countdown-minutes">00</span><span class="label">Minutes</span></div>
            <div class="countdown-item"><span class="number" id="countdown-seconds">00</span><span class="label">Seconds</span></div>
        </div>
        <a href="shop.php?category=sale" class="btn btn-light mt-4 px-4">Shop Sale</a>
    </div>
</section>

<!-- New Arrivals -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">New Arrivals</h2>
            <p class="section-subtitle">The latest additions to our collection</p>
        </div>
        <div class="row g-4">
            <?php while ($product = $new_arrivals->fetch_assoc()):
                $in_wishlist = in_array($product['id'], $user_wishlist);
            ?>
            <div class="col-lg-3 col-md-6">
                <div class="product-card h-100">
                    <div class="product-card-img-wrapper position-relative overflow-hidden rounded">
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="product-img-link d-block">
                            <?php if ($product['sale_price']): ?>
                            <span class="badge bg-danger position-absolute top-0 start-0 m-2" style="z-index:2">Sale</span>
                            <?php endif; ?>
                            <img src="assets/images/<?php echo $product['image_main']; ?>" alt="<?php echo $product['name']; ?>" class="img-main w-100" style="aspect-ratio:3/4;object-fit:cover">
                            <img src="assets/images/<?php echo $product['image_hover']; ?>" alt="<?php echo $product['name']; ?>" class="img-hover w-100" style="aspect-ratio:3/4;object-fit:cover">
                        </a>
                        <button class="wishlist-heart <?php echo $in_wishlist ? 'active' : ''; ?>" data-id="<?php echo $product['id']; ?>" title="<?php echo $in_wishlist ? 'Remove from' : 'Add to'; ?> wishlist">
                            <i class="bi <?php echo $in_wishlist ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                        </button>
                        <div class="shop-btn-group d-flex gap-2 position-absolute bottom-0 start-0 end-0 p-2">
                            <button class="quick-add-btn btn btn-dark btn-sm flex-grow-1" data-id="<?php echo $product['id']; ?>">Add to Cart</button>
                            <button class="buy-now-btn btn btn-primary btn-sm flex-grow-1" data-id="<?php echo $product['id']; ?>">Buy Now</button>
                        </div>
                    </div>
                    <div class="product-card-info pt-3">
                        <h6 class="product-card-title mb-1">
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="text-dark text-decoration-none fw-bold"><?php echo $product['name']; ?></a>
                        </h6>
                        <div class="product-card-price">
                            <?php if ($product['sale_price']): ?>
                            <span class="text-danger fw-bold"><?php echo formatPrice($product['sale_price']); ?></span>
                            <small class="text-muted text-decoration-line-through ms-1"><?php echo formatPrice($product['price']); ?></small>
                            <?php else: ?>
                            <span class="fw-bold"><?php echo formatPrice($product['price']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">What Our Customers Say</h2>
        </div>
        <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="testimonial-card">
                        <p class="testimonial-text">"The quality of the clothes is exceptional. I've never been disappointed with any purchase from ShopVibe. Fast shipping and great customer service too!"</p>
                        <div class="testimonial-author">Sarah Mitchell</div>
                        <div class="testimonial-role">Verified Buyer</div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="testimonial-card">
                        <p class="testimonial-text">"ShopVibe has become my go-to for all my fashion needs. The styles are trendy yet timeless, and the prices are very reasonable for the quality you get."</p>
                        <div class="testimonial-author">James Rodriguez</div>
                        <div class="testimonial-role">Verified Buyer</div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="testimonial-card">
                        <p class="testimonial-text">"I love the variety of products available. The website is easy to navigate and the checkout process is seamless. Highly recommended!"</p>
                        <div class="testimonial-author">Emily Chen</div>
                        <div class="testimonial-role">Verified Buyer</div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
            <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
        </div>
    </div>
</section>

<script>
// Quick Add
document.querySelectorAll('.quick-add-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
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

// Buy Now
document.querySelectorAll('.buy-now-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
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
                window.location.href = 'checkout.php';
            }
        });
    });
});

// Wishlist
document.querySelectorAll('.wishlist-heart').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const productId = this.dataset.id;
        const isActive = this.classList.contains('active');
        const icon = this.querySelector('i');
        const url = isActive ? 'ajax/remove-from-wishlist.php' : 'ajax/add-to-wishlist.php';
        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `product_id=${productId}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.classList.toggle('active');
                icon.classList.toggle('bi-heart');
                icon.classList.toggle('bi-heart-fill');
                this.title = isActive ? 'Add to wishlist' : 'Remove from wishlist';
            } else if (data.message === 'login_required') {
                window.location.href = 'login.php';
            }
        });
    });
});

// Countdown
function updateCountdown() {
    const now = new Date();
    const endOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
    const diff = endOfDay - now;
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    document.getElementById('countdown-hours').textContent = String(hours).padStart(2, '0');
    document.getElementById('countdown-minutes').textContent = String(minutes).padStart(2, '0');
    document.getElementById('countdown-seconds').textContent = String(seconds).padStart(2, '0');
}
updateCountdown();
setInterval(updateCountdown, 1000);
</script>

<?php include 'includes/footer.php'; ?>