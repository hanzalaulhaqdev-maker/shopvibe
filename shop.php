<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
include 'includes/header.php';

$cat_result = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = [];
while ($row = $cat_result->fetch_assoc()) $categories[] = $row;

$size_result = $conn->query("SELECT DISTINCT sizes_json FROM products WHERE sizes_json IS NOT NULL AND status = 'active'");
$all_sizes = [];
while ($row = $size_result->fetch_assoc()) {
    $sizes = json_decode($row['sizes_json'], true);
    if (is_array($sizes)) foreach ($sizes as $size) if (!in_array($size, $all_sizes)) $all_sizes[] = $size;
}
sort($all_sizes);

$color_result = $conn->query("SELECT DISTINCT colors_json FROM products WHERE colors_json IS NOT NULL AND status = 'active'");
$all_colors = [];
while ($row = $color_result->fetch_assoc()) {
    $colors = json_decode($row['colors_json'], true);
    if (is_array($colors)) foreach ($colors as $color) if (!in_array($color, $all_colors)) $all_colors[] = $color;
}

$user_wishlist = [];
if (isLoggedIn()) {
    $w_stmt = $conn->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $w_stmt->bind_param("i", $_SESSION['user_id']);
    $w_stmt->execute();
    $w_result = $w_stmt->get_result();
    while ($w = $w_result->fetch_assoc()) $user_wishlist[] = $w['product_id'];
    $w_stmt->close();
}

// Get category filter from URL
$category_slug = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$category_id = 0;

if (!empty($category_slug)) {
    $cat_stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ?");
    $cat_stmt->bind_param("s", $category_slug);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    if ($cat_row = $cat_result->fetch_assoc()) {
        $category_id = $cat_row['id'];
    }
    $cat_stmt->close();
}

// PAGINATION SETUP
$per_page = 12;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Build WHERE clause
$params = [];
$types = "";
$where_clauses = ["p.status = 'active'"];

if ($category_id > 0) {
    $where_clauses[] = "p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

$where_sql = implode(" AND ", $where_clauses);

// Count total products for pagination
$count_query = "SELECT COUNT(*) as total FROM products p WHERE $where_sql";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_products = $total_result->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_products / $per_page);
$current_page = min($current_page, max(1, $total_pages));

// Recalculate offset in case page was out of bounds
$offset = ($current_page - 1) * $per_page;

// Main products query with pagination
$query = "SELECT p.*, c.name as category_name, c.slug as category_slug 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE $where_sql 
          ORDER BY p.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$bind_types = $types . "ii";
$bind_params = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($bind_types, ...$bind_params);
$stmt->execute();
$products = $stmt->get_result();

// Build pagination URL base (preserve category filter)
$pagination_base = 'shop.php';
if (!empty($category_slug)) {
    $pagination_base .= '?category=' . urlencode($category_slug) . '&';
} else {
    $pagination_base .= '?';
}
?>

<style>
.product-card-img-wrapper{position:relative;overflow:hidden}
.product-card-img-wrapper .img-main{transition:opacity .3s,transform .4s ease}
.product-card-img-wrapper .img-hover{position:absolute;top:0;left:0;opacity:0;transition:opacity .3s;pointer-events:none}
.product-card:hover .img-main{transform:scale(1.05)}
.product-card:hover .img-hover{opacity:1}

.wishlist-heart{position:absolute;top:10px;right:10px;width:36px;height:36px;border-radius:50%;background:white;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0;transition:opacity .3s,transform .2s;z-index:10;box-shadow:0 2px 5px rgba(0,0,0,.1)}
.product-card:hover .wishlist-heart{opacity:1}
.wishlist-heart:hover{transform:scale(1.1)}
.wishlist-heart.active{opacity:1;background:#dc3545}
.wishlist-heart.active i{color:white}
.wishlist-heart.active i::before{content:"\f415"}

.shop-btn-group{transform:translateY(100%);transition:transform .3s ease;z-index:10;pointer-events:auto}
.product-card:hover .shop-btn-group{transform:translateY(0)}
.product-img-link{display:block;position:relative}

.size-filter-btn{display:inline-block;padding:6px 14px;border:1px solid #ddd;border-radius:4px;margin:3px;cursor:pointer;font-size:.85rem;transition:all .2s}
.size-filter-btn:hover,.size-filter-btn.active{background:#000;color:#fff;border-color:#000}

.color-swatch{display:inline-block;width:28px;height:28px;border-radius:50%;margin:3px;cursor:pointer;border:2px solid #fff;box-shadow:0 0 0 1px #ddd;transition:box-shadow .2s}
.color-swatch:hover,.color-swatch.active{box-shadow:0 0 0 2px #000}

.shop-sidebar{position:sticky;top:90px}
.filter-section{margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid #e9ecef}
.filter-section:last-child{border-bottom:none}
.filter-title{font-weight:700;margin-bottom:1rem;font-size:1rem}
.filter-list{list-style:none;padding:0;margin:0}
.filter-list li{margin-bottom:.5rem}
.filter-list label{cursor:pointer;display:flex;align-items:center;gap:.5rem}
.price-range-inputs{display:flex;align-items:center;gap:.5rem}
.price-range-inputs input{width:80px}

.shop-toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid #e9ecef}

.pagination .page-link{color:#000;border-radius:0}
.pagination .page-item.active .page-link{background:#000;border-color:#000;color:#fff}
.pagination .page-item.disabled .page-link{color:#6c757d}
</style>

<section class="py-4">
    <div class="container">
        <!-- Mobile filter toggle -->
        <div class="d-lg-none mb-3">
            <button class="btn btn-outline-dark w-100" type="button" data-bs-toggle="collapse" data-bs-target="#mobileFilters" aria-expanded="false">
                <i class="bi bi-funnel me-2"></i>Filters
            </button>
        </div>
        <div class="row">
            <div class="col-lg-3">
                <div class="collapse d-lg-block" id="mobileFilters">
                <div class="shop-sidebar">
                    <div class="filter-section">
                        <h5 class="filter-title">Categories</h5>
                        <ul class="filter-list">
                            <?php foreach ($categories as $cat): ?>
                            <li>
                                <label>
                                    <input type="checkbox" class="category-filter" value="<?php echo $cat['slug']; ?>" 
                                        <?php echo ($category_slug === $cat['slug']) ? 'checked' : ''; ?>> 
                                    <?php echo $cat['name']; ?>
                                </label>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="filter-section">
                        <h5 class="filter-title">Price Range</h5>
                        <div class="price-range-inputs">
                            <input type="number" id="min-price" placeholder="Min" min="0" class="form-control form-control-sm">
                            <span>-</span>
                            <input type="number" id="max-price" placeholder="Max" min="0" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="filter-section">
                        <h5 class="filter-title">Sizes</h5>
                        <div>
                            <?php foreach ($all_sizes as $size): ?>
                            <span class="size-filter-btn" data-size="<?php echo $size; ?>"><?php echo $size; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="filter-section">
                        <h5 class="filter-title">Colors</h5>
                        <div>
                            <?php foreach ($all_colors as $color): ?>
                            <span class="color-swatch" data-color="<?php echo $color; ?>" style="background-color:<?php echo strtolower($color); ?>;" title="<?php echo $color; ?>"></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button class="btn btn-dark w-100" id="apply-filters">Apply Filters</button>
                    <button class="btn btn-outline-dark w-100 mt-2" id="clear-filters">Clear All</button>
                </div>
                </div><!-- end mobileFilters collapse -->
            </div>

            <div class="col-lg-9">
                <div class="shop-toolbar">
                    <span class="product-count" id="product-count">Showing <?php echo $products->num_rows; ?> of <?php echo $total_products; ?> products</span>
                    <select id="sort-select" class="form-select form-select-sm" style="width:auto;">
                        <option value="newest">Newest</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                        <option value="popular">Popular</option>
                    </select>
                </div>

                <div class="row g-4" id="product-grid">
                    <?php if ($products->num_rows === 0): ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-search fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No products found in this category.</p>
                        <a href="shop.php" class="btn btn-outline-dark">View All Products</a>
                    </div>
                    <?php else: ?>
                    <?php while ($product = $products->fetch_assoc()):
                        $in_wishlist = in_array($product['id'], $user_wishlist);
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="product-card h-100 position-relative">
                            <div class="product-card-img-wrapper position-relative overflow-hidden rounded">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-img-link d-block">
                                    <?php if ($product['sale_price']): ?>
                                    <span class="position-absolute top-0 start-0 m-2 badge bg-danger" style="z-index:2">Sale</span>
                                    <?php endif; ?>
                                    <img src="assets/images/<?php echo $product['image_main']; ?>" alt="<?php echo $product['name']; ?>" class="img-main w-100" style="aspect-ratio:3/4;object-fit:cover;transition:transform .4s ease">
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
                    <?php endif; ?>
                </div>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                <nav class="mt-5" aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <!-- Previous -->
                        <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $pagination_base; ?>page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <!-- Page Numbers -->
                        <?php 
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if ($start_page > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?php echo $pagination_base; ?>page=1">1</a></li>
                        <?php if ($start_page > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo $pagination_base; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?php echo $pagination_base; ?>page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a></li>
                        <?php endif; ?>
                        
                        <!-- Next -->
                        <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $pagination_base; ?>page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
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
                localStorage.setItem('cartUpdated', Date.now().toString());
                refreshDrawer();
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
                localStorage.setItem('cartUpdated', Date.now().toString());
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

// Filters
document.querySelectorAll('.size-filter-btn').forEach(btn => {
    btn.addEventListener('click', function() { this.classList.toggle('active'); });
});
document.querySelectorAll('.color-swatch').forEach(btn => {
    btn.addEventListener('click', function() { this.classList.toggle('active'); });
});
document.getElementById('clear-filters')?.addEventListener('click', function() {
    document.querySelectorAll('.category-filter').forEach(cb => cb.checked = false);
    document.querySelectorAll('.size-filter-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.color-swatch').forEach(btn => btn.classList.remove('active'));
    document.getElementById('min-price').value = '';
    document.getElementById('max-price').value = '';
    window.location.href = 'shop.php';
});
</script>

<?php include 'includes/footer.php'; ?>