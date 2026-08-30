<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
include 'includes/header.php';

$product_id = intval($_GET['id'] ?? 0);
if ($product_id <= 0) redirect('shop.php');

$stmt = $conn->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.status = 'active'");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$product) redirect('shop.php');

$images = json_decode($product['images_json'], true) ?: [];
$sizes = json_decode($product['sizes_json'], true) ?: [];
$colors = json_decode($product['colors_json'], true) ?: [];
$price = $product['sale_price'] ? $product['sale_price'] : $product['price'];

// Reviews
$review_success = '';
$review_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $review_error = 'Please login to write a review.';
    } else {
        $rating = intval($_POST['rating'] ?? 0);
        $comment = sanitize($_POST['comment'] ?? '');
        $user_id = $_SESSION['user_id'];
        $user_name = $_SESSION['user_name'] ?? 'Anonymous';
        if ($rating < 1 || $rating > 5) {
            $review_error = 'Please select a star rating.';
        } elseif (empty($comment)) {
            $review_error = 'Please write a comment.';
        } else {
            $check = $conn->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
            $check->bind_param("ii", $product_id, $user_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $review_error = 'You have already reviewed this product.';
            } else {
                $ins = $conn->prepare("INSERT INTO reviews (product_id, user_id, user_name, rating, comment) VALUES (?, ?, ?, ?, ?)");
                $ins->bind_param("iisis", $product_id, $user_id, $user_name, $rating, $comment);
                if ($ins->execute()) $review_success = 'Thank you! Your review has been submitted.';
                else $review_error = 'Failed to submit review.';
                $ins->close();
            }
            $check->close();
        }
    }
}

$rev_stmt = $conn->prepare("SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$rev_stmt->bind_param("i", $product_id);
$rev_stmt->execute();
$reviews = $rev_stmt->get_result();
$rev_stmt->close();

$avg_rating = 0;
$total_reviews = 0;
$rating_counts = [5=>0,4=>0,3=>0,2=>0,1=>0];
$stats = $conn->prepare("SELECT rating, COUNT(*) as count FROM reviews WHERE product_id = ? GROUP BY rating");
$stats->bind_param("i", $product_id);
$stats->execute();
$stats_res = $stats->get_result();
while ($row = $stats_res->fetch_assoc()) {
    $rating_counts[$row['rating']] = $row['count'];
    $total_reviews += $row['count'];
}
$stats->close();
if ($total_reviews > 0) {
    $sum = 0;
    foreach ($rating_counts as $star => $count) $sum += $star * $count;
    $avg_rating = round($sum / $total_reviews, 1);
}

$user_has_reviewed = false;
if (isLoggedIn()) {
    $chk = $conn->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
    $chk->bind_param("ii", $product_id, $_SESSION['user_id']);
    $chk->execute();
    $user_has_reviewed = $chk->get_result()->num_rows > 0;
    $chk->close();
}

// Wishlist
$in_wishlist = false;
if (isLoggedIn()) {
    $w = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $w->bind_param("ii", $_SESSION['user_id'], $product_id);
    $w->execute();
    $in_wishlist = $w->get_result()->num_rows > 0;
    $w->close();
}

// Related
$rel = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND status = 'active' ORDER BY RAND() LIMIT 4");
$rel->bind_param("ii", $product['category_id'], $product_id);
$rel->execute();
$related = $rel->get_result();
?>

<style>
.star-rating-display{color:#ffc107;font-size:1.1rem}.star-rating-display .bi-star{color:#ddd}
.star-rating-input{direction:rtl;display:inline-flex}.star-rating-input input{display:none}
.star-rating-input label{cursor:pointer;color:#ddd;font-size:2rem;padding:0 2px;transition:color .2s}
.star-rating-input input:checked~label,.star-rating-input label:hover,.star-rating-input label:hover~label{color:#ffc107}
.rating-bar{height:8px;background:#e9ecef;border-radius:4px;overflow:hidden}
.rating-bar-fill{height:100%;background:#ffc107;border-radius:4px;transition:width .3s}
.review-card{border-bottom:1px solid #e9ecef;padding:1.5rem 0}.review-card:last-child{border-bottom:none}
.review-avatar{width:48px;height:48px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.2rem}
.gallery-thumb{transition:border-color .2s,opacity .2s;cursor:pointer}.gallery-thumb:hover{opacity:.8}.gallery-thumb.active{border-color:#000!important}
.wishlist-btn.active{background:#dc3545;color:#fff;border-color:#dc3545}.wishlist-btn.active i::before{content:"\f415"}
.size-option.active{background:#000;color:#fff;border-color:#000}.color-option.active span{box-shadow:0 0 0 3px #000}
.zoom-container{overflow:hidden;cursor:crosshair}.zoom-container img{transition:transform .3s}
.related-img-link {
    display: block;
    position: relative;
    overflow: hidden;
    background: #f8f9fa;
}
.related-img-link .img-main {
    transition: transform 0.4s ease;
    position: relative;
    z-index: 1;
}
.related-img-link .img-hover {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
    z-index: 2;
}
.related-img-link:hover .img-hover {
    opacity: 1;
}
.related-img-link:hover .img-main {
    transform: scale(1.05);
}
.product-card-img-wrapper .quick-add-btn{z-index:10;pointer-events:auto}
</style>

<section class="py-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="shop.php?category=<?php echo $product['category_slug']; ?>" class="text-decoration-none"><?php echo $product['category_name']; ?></a></li>
                <li class="breadcrumb-item active"><?php echo $product['name']; ?></li>
            </ol>
        </nav>

        <div class="row g-5">
            <!-- Gallery -->
            <div class="col-lg-6">
                <div class="position-sticky" style="top:100px">
                    <div class="zoom-container mb-3 rounded overflow-hidden" style="background:#f8f9fa">
                        <img src="assets/images/<?php echo $product['image_main']; ?>" alt="<?php echo $product['name']; ?>" id="main-product-image" class="w-100" style="aspect-ratio:3/4;object-fit:cover" onmouseover="this.style.transform='scale(1.3)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    <div class="d-flex gap-2">
                        <div class="gallery-thumb active rounded overflow-hidden" data-src="<?php echo $product['image_main']; ?>" style="width:80px;height:100px;border:2px solid #000">
                            <img src="assets/images/<?php echo $product['image_main']; ?>" class="w-100 h-100" style="object-fit:cover">
                        </div>
                        <?php foreach ($images as $img): ?>
                        <div class="gallery-thumb rounded overflow-hidden" data-src="<?php echo $img; ?>" style="width:80px;height:100px;border:2px solid transparent">
                            <img src="assets/images/<?php echo $img; ?>" class="w-100 h-100" style="object-fit:cover">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="col-lg-6">
                <div class="mb-2"><span class="text-muted"><?php echo $product['category_name']; ?></span></div>
                <h1 class="fw-bold mb-3" style="font-size:2.2rem"><?php echo $product['name']; ?></h1>
                
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="star-rating-display">
                        <?php for ($i=1;$i<=5;$i++): ?><i class="bi bi-star<?php echo $i<=round($avg_rating)?'-fill':''; ?>"></i><?php endfor; ?>
                    </div>
                    <span class="fw-bold"><?php echo $avg_rating; ?></span>
                    <span class="text-muted">|</span>
                    <a href="#reviews" class="text-muted text-decoration-underline"><?php echo $total_reviews; ?> review<?php echo $total_reviews!==1?'s':''; ?></a>
                </div>

                <div class="mb-4">
                    <?php if ($product['sale_price']): ?>
                    <span class="fs-2 fw-bold text-danger"><?php echo formatPrice($product['sale_price']); ?></span>
                    <span class="fs-4 text-muted text-decoration-line-through ms-2"><?php echo formatPrice($product['price']); ?></span>
                    <span class="badge bg-danger ms-2">SAVE <?php echo round((1-$product['sale_price']/$product['price'])*100); ?>%</span>
                    <?php else: ?>
                    <span class="fs-2 fw-bold"><?php echo formatPrice($product['price']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <?php if ($product['stock']>10): ?><span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle-fill me-1"></i>In Stock</span>
                    <?php elseif ($product['stock']>0): ?><span class="badge bg-warning-subtle text-warning"><i class="bi bi-exclamation-circle-fill me-1"></i>Only <?php echo $product['stock']; ?> left</span>
                    <?php else: ?><span class="badge bg-danger-subtle text-danger"><i class="bi bi-x-circle-fill me-1"></i>Out of Stock</span><?php endif; ?>
                </div>

                <p class="text-muted mb-4" style="font-size:1.05rem;line-height:1.7"><?php echo nl2br($product['description']); ?></p>

                <form id="product-add-form" class="mb-4">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <?php if (!empty($sizes)): ?>
                    <div class="mb-4">
                        <label class="fw-bold mb-2 d-block">Size <span class="text-muted fw-normal">— <span id="selected-size-text"><?php echo $sizes[0]; ?></span></span></label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($sizes as $i=>$size): ?>
                            <input type="radio" class="btn-check" name="size" id="size<?php echo $i; ?>" value="<?php echo $size; ?>" <?php echo $i===0?'checked':''; ?>>
                            <label class="btn btn-outline-dark size-option <?php echo $i===0?'active':''; ?>" for="size<?php echo $i; ?>" style="min-width:50px"><?php echo $size; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($colors)): ?>
                    <div class="mb-4">
                        <label class="fw-bold mb-2 d-block">Color <span class="text-muted fw-normal">— <span id="selected-color-text"><?php echo $colors[0]; ?></span></span></label>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach ($colors as $i=>$color): ?>
                            <label class="color-option <?php echo $i===0?'active':''; ?>" style="cursor:pointer">
                                <input type="radio" name="color" value="<?php echo $color; ?>" <?php echo $i===0?'checked':''; ?> style="display:none">
                                <span style="width:32px;height:32px;border-radius:50%;background-color:<?php echo strtolower($color); ?>;border:2px solid #fff;box-shadow:0 0 0 1px #ddd;display:inline-block" title="<?php echo $color; ?>"></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <label class="fw-bold mb-2 d-block">Quantity</label>
                        <div class="input-group" style="width:130px">
                            <button type="button" class="btn btn-outline-dark" id="qty-minus">−</button>
                            <input type="number" id="product-quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="form-control text-center" readonly>
                            <button type="button" class="btn btn-outline-dark" id="qty-plus">+</button>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mb-2">
                        <button type="button" class="btn btn-dark btn-lg flex-grow-1" id="add-to-cart-btn" data-id="<?php echo $product['id']; ?>"><i class="bi bi-bag me-2"></i>Add to Cart</button>
                        <button type="button" class="btn btn-primary btn-lg flex-grow-1" id="buy-now-btn" data-id="<?php echo $product['id']; ?>"><i class="bi bi-lightning me-2"></i>Buy Now</button>
                        <button type="button" class="btn btn-outline-dark btn-lg wishlist-btn <?php echo $in_wishlist?'active':''; ?>" data-id="<?php echo $product['id']; ?>" style="width:58px"><i class="bi <?php echo $in_wishlist?'bi-heart-fill':'bi-heart'; ?>"></i></button>
                    </div>
                    <div class="small text-muted"><i class="bi bi-info-circle me-1"></i>Buy Now skips the cart and takes you straight to checkout</div>
                </form>

                <div class="border-top pt-4 mt-4">
                    <div class="row g-3 text-center">
                        <div class="col-4"><i class="bi bi-truck fs-4 text-muted"></i><div class="small text-muted mt-1">Free Shipping<br>Over $100</div></div>
                        <div class="col-4"><i class="bi bi-arrow-return-left fs-4 text-muted"></i><div class="small text-muted mt-1">30-Day<br>Returns</div></div>
                        <div class="col-4"><i class="bi bi-shield-check fs-4 text-muted"></i><div class="small text-muted mt-1">Secure<br>Checkout</div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews -->
        <div class="mt-5 pt-5" id="reviews">
            <h3 class="fw-bold mb-4">Customer Reviews</h3>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 bg-light">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <div class="display-4 fw-bold"><?php echo $avg_rating; ?></div>
                                <div class="star-rating-display fs-5 my-2">
                                    <?php for ($i=1;$i<=5;$i++): ?><i class="bi bi-star<?php echo $i<=round($avg_rating)?'-fill':''; ?>"></i><?php endfor; ?>
                                </div>
                                <div class="text-muted">Based on <?php echo $total_reviews; ?> review<?php echo $total_reviews!==1?'s':''; ?></div>
                            </div>
                            <?php for ($star=5;$star>=1;$star--): 
                                $count=$rating_counts[$star];
                                $percentage=$total_reviews>0?($count/$total_reviews)*100:0;
                            ?>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="small" style="width:40px"><?php echo $star; ?> star</span>
                                <div class="flex-grow-1 rating-bar"><div class="rating-bar-fill" style="width:<?php echo $percentage; ?>%"></div></div>
                                <span class="small text-muted" style="width:30px;text-align:right"><?php echo $count; ?></span>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <?php if (isLoggedIn() && !$user_has_reviewed): ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3">Write a Review</h5>
                            <?php if ($review_success): ?><div class="alert alert-success"><?php echo $review_success; ?></div><?php endif; ?>
                            <?php if ($review_error): ?><div class="alert alert-danger"><?php echo $review_error; ?></div><?php endif; ?>
                            <form method="POST" action="">
                                <input type="hidden" name="submit_review" value="1">
                                <div class="mb-3">
                                    <label class="fw-bold mb-2 d-block">Your Rating</label>
                                    <div class="star-rating-input">
                                        <input type="radio" id="star5" name="rating" value="5"><label for="star5"><i class="bi bi-star-fill"></i></label>
                                        <input type="radio" id="star4" name="rating" value="4"><label for="star4"><i class="bi bi-star-fill"></i></label>
                                        <input type="radio" id="star3" name="rating" value="3"><label for="star3"><i class="bi bi-star-fill"></i></label>
                                        <input type="radio" id="star2" name="rating" value="2"><label for="star2"><i class="bi bi-star-fill"></i></label>
                                        <input type="radio" id="star1" name="rating" value="1"><label for="star1"><i class="bi bi-star-fill"></i></label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold mb-2">Your Review</label>
                                    <textarea name="comment" class="form-control" rows="4" placeholder="Share your thoughts about this product..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-dark">Submit Review</button>
                            </form>
                        </div>
                    </div>
                    <?php elseif (!isLoggedIn()): ?>
                    <div class="alert alert-light border mb-4"><i class="bi bi-info-circle me-2"></i>Please <a href="login.php" class="fw-bold">login</a> to write a review.</div>
                    <?php endif; ?>

                    <div class="reviews-list">
                        <?php if ($reviews->num_rows===0): ?>
                        <div class="text-center py-5"><i class="bi bi-chat-square-text fs-1 text-muted"></i><p class="text-muted mt-3">No reviews yet. Be the first to review this product!</p></div>
                        <?php else: while ($review=$reviews->fetch_assoc()): ?>
                        <div class="review-card">
                            <div class="d-flex gap-3">
                                <div class="review-avatar flex-shrink-0"><?php echo strtoupper(substr($review['user_name'],0,1)); ?></div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-1"><?php echo $review['user_name']; ?></h6>
                                            <div class="star-rating-display small mb-2">
                                                <?php for ($i=1;$i<=5;$i++): ?><i class="bi bi-star<?php echo $i<=$review['rating']?'-fill':''; ?>"></i><?php endfor; ?>
                                            </div>
                                        </div>
                                        <span class="small text-muted"><?php echo date('M d, Y',strtotime($review['created_at'])); ?></span>
                                    </div>
                                    <p class="mb-0 text-muted"><?php echo nl2br($review['comment']); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if ($related->num_rows>0): ?>
        <div class="mt-5 pt-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">You May Also Like</h3>
                <a href="shop.php?category=<?php echo $product['category_slug']; ?>" class="text-dark text-decoration-underline">View All</a>
            </div>
            <div class="row g-4">
                <?php while ($rel=$related->fetch_assoc()): ?>
                <?php
                    $hover_file = !empty($rel['image_hover']) ? 'assets/images/' . $rel['image_hover'] : '';
                    $main_file = 'assets/images/' . $rel['image_main'];
                    $has_real_hover = !empty($hover_file) 
                        && file_exists($hover_file) 
                        && $rel['image_hover'] !== $rel['image_main'];
                    $hover_src = $has_real_hover ? $rel['image_hover'] : $rel['image_main'];
                ?>
                <div class="col-lg-3 col-md-6">
                    <div class="product-card h-100">
                        <div class="product-card-img-wrapper position-relative overflow-hidden rounded">
                            <a href="product.php?id=<?php echo $rel['id']; ?>" class="related-img-link d-block">
                                <?php if ($rel['sale_price']): ?><span class="position-absolute top-0 start-0 m-2 badge bg-danger" style="z-index:3">Sale</span><?php endif; ?>
                                <img src="assets/images/<?php echo $rel['image_main']; ?>" alt="<?php echo $rel['name']; ?>" class="img-main w-100" style="aspect-ratio:3/4;object-fit:cover;">
                                <img src="assets/images/<?php echo $hover_src; ?>" alt="<?php echo $rel['name']; ?>" class="img-hover w-100" style="aspect-ratio:3/4;object-fit:cover;">
                            </a>
                            <button class="quick-add-btn position-absolute bottom-0 start-0 end-0 btn btn-dark" data-id="<?php echo $rel['id']; ?>" style="transform:translateY(100%);transition:transform .3s;z-index:10">Quick Add</button>
                        </div>
                        <div class="product-card-info pt-3">
                            <h6 class="product-card-title mb-1"><a href="product.php?id=<?php echo $rel['id']; ?>" class="text-dark text-decoration-none fw-bold"><?php echo $rel['name']; ?></a></h6>
                            <div class="product-card-price">
                                <?php if ($rel['sale_price']): ?>
                                <span class="text-danger fw-bold"><?php echo formatPrice($rel['sale_price']); ?></span>
                                <small class="text-muted text-decoration-line-through ms-1"><?php echo formatPrice($rel['price']); ?></small>
                                <?php else: ?>
                                <span class="fw-bold"><?php echo formatPrice($rel['price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Gallery thumbnails
document.querySelectorAll('.gallery-thumb').forEach(thumb => {
    thumb.addEventListener('click', function(){
        document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('main-product-image').src = 'assets/images/' + this.dataset.src;
    });
});

// Quantity controls
const qtyInput = document.getElementById('product-quantity');
const maxStock = parseInt(qtyInput?.max || 99);
document.getElementById('qty-minus')?.addEventListener('click', () => {
    let val = parseInt(qtyInput.value);
    if (val > 1) qtyInput.value = val - 1;
});
document.getElementById('qty-plus')?.addEventListener('click', () => {
    let val = parseInt(qtyInput.value);
    if (val < maxStock) qtyInput.value = val + 1;
});

// Size selection UI
document.querySelectorAll('input[name="size"]').forEach(radio => {
    radio.addEventListener('change', function(){
        document.querySelectorAll('.size-option').forEach(l => l.classList.remove('active'));
        this.nextElementSibling.classList.add('active');
        document.getElementById('selected-size-text').textContent = this.value;
    });
});

// Color selection UI
document.querySelectorAll('input[name="color"]').forEach(radio => {
    radio.addEventListener('change', function(){
        document.querySelectorAll('.color-option').forEach(l => l.classList.remove('active'));
        this.parentElement.classList.add('active');
        document.getElementById('selected-color-text').textContent = this.value;
    });
});

// ============================================
// ADD TO CART - WITH localStorage NOTIFICATION
// ============================================
document.getElementById('add-to-cart-btn')?.addEventListener('click', function(){
    const btn = this;
    const productId = btn.dataset.id;
    const quantity = document.getElementById('product-quantity')?.value || 1;
    const size = document.querySelector('input[name="size"]:checked')?.value || '';
    const color = document.querySelector('input[name="color"]:checked')?.value || '';
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
    
    fetch('ajax/add-to-cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${productId}&quantity=${quantity}&size=${encodeURIComponent(size)}&color=${encodeURIComponent(color)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Added!';
            btn.classList.remove('btn-dark');
            btn.classList.add('btn-success');

            // Refresh drawer and notify other tabs
            localStorage.setItem('cartUpdated', Date.now().toString());
            refreshDrawer();
            document.getElementById('cart-drawer')?.classList.add('open');
            document.getElementById('cart-overlay')?.classList.add('show');
            
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-bag me-2"></i>Add to Cart';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-dark');
                btn.disabled = false;
            }, 2000);
        } else {
            alert(data.message || 'Failed to add to cart');
            btn.innerHTML = '<i class="bi bi-bag me-2"></i>Add to Cart';
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        btn.innerHTML = '<i class="bi bi-bag me-2"></i>Add to Cart';
        btn.disabled = false;
    });
});

// ============================================
// BUY NOW BUTTON
// ============================================
document.getElementById('buy-now-btn')?.addEventListener('click', function(){
    const btn = this;
    const productId = btn.dataset.id;
    const quantity = document.getElementById('product-quantity')?.value || 1;
    const size = document.querySelector('input[name="size"]:checked')?.value || '';
    const color = document.querySelector('input[name="color"]:checked')?.value || '';
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    
    fetch('ajax/add-to-cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${productId}&quantity=${quantity}&size=${encodeURIComponent(size)}&color=${encodeURIComponent(color)}&buy_now=1`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const cartBadge = document.getElementById('cart-count');
            if (cartBadge) cartBadge.textContent = data.cart_count;
            window.location.href = 'checkout.php?buy_now=1';
        } else {
            alert(data.message || 'Failed to process. Please try again.');
            btn.innerHTML = '<i class="bi bi-lightning me-2"></i>Buy Now';
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        alert('Connection error. Please try again.');
        btn.innerHTML = '<i class="bi bi-lightning me-2"></i>Buy Now';
        btn.disabled = false;
    });
});

// Wishlist toggle
document.querySelector('.wishlist-btn')?.addEventListener('click', function(){
    const btn = this;
    const productId = btn.dataset.id;
    const isActive = btn.classList.contains('active');
    const icon = btn.querySelector('i');
    const url = isActive ? 'ajax/remove-from-wishlist.php' : 'ajax/add-to-wishlist.php';
    
    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${productId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.classList.toggle('active');
            icon.classList.toggle('bi-heart');
            icon.classList.toggle('bi-heart-fill');
        } else if (data.message === 'login_required') {
            window.location.href = 'login.php';
        }
    });
});

// Quick Add for related products
document.querySelectorAll('.quick-add-btn').forEach(btn => {
    btn.addEventListener('click', function(e){
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
                localStorage.setItem('cartUpdated', Date.now().toString());
                refreshDrawer();
                document.getElementById('cart-drawer')?.classList.add('open');
                document.getElementById('cart-overlay')?.classList.add('show');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>