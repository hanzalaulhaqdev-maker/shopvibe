<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Fetch categories dynamically for the navbar
$nav_categories = [];
$cat_stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
while ($row = $cat_result->fetch_assoc()) {
    $nav_categories[] = $row;
}
$cat_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopVibe — Fashion Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (strpos($_SERVER['PHP_SELF'], 'shop.php') !== false): ?>
    <link rel="stylesheet" href="assets/css/shop.css">
    <?php endif; ?>
    <?php if (strpos($_SERVER['PHP_SELF'], 'cart.php') !== false): ?>
    <link rel="stylesheet" href="assets/css/cart.css">
    <?php endif; ?>
    <?php if (strpos($_SERVER['PHP_SELF'], 'admin/') !== false): ?>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <?php endif; ?>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="index.php">ShopVibe</a>

            <!-- Mobile: icons always visible + hamburger -->
            <div class="d-flex align-items-center gap-3 ms-auto me-2 d-lg-none">
                <a href="search.php" class="text-dark"><i class="bi bi-search fs-5"></i></a>
                <a href="javascript:void(0)" id="cart-toggle-mobile" class="text-dark position-relative">
                    <i class="bi bi-bag fs-5"></i>
                    <span class="cart-badge" id="cart-count-mobile"><?php echo getCartCount(); ?></span>
                </a>
                <?php if (isLoggedIn()): ?>
                <div class="dropdown">
                    <a href="#" class="text-dark dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person fs-5"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="account.php">My Account</a></li>
                        <li><a class="dropdown-item" href="wishlist.php">Wishlist</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </div>
                <?php else: ?>
                <a href="login.php" class="text-dark"><i class="bi bi-person fs-5"></i></a>
                <?php endif; ?>
            </div>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="shop.php">Shop</a></li>
                    <?php foreach ($nav_categories as $cat): ?>
                    <li class="nav-item"><a class="nav-link" href="shop.php?category=<?php echo $cat['slug']; ?>"><?php echo $cat['name']; ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <!-- Desktop: icons inside collapse -->
                <div class="d-none d-lg-flex align-items-center gap-3">
                    <a href="search.php" class="text-dark"><i class="bi bi-search fs-5"></i></a>
                    <a href="wishlist.php" class="text-dark position-relative">
                        <i class="bi bi-heart fs-5"></i>
                    </a>
                    <a href="javascript:void(0)" id="cart-toggle" class="text-dark position-relative">
                        <i class="bi bi-bag fs-5"></i>
                        <span class="cart-badge" id="cart-count"><?php echo getCartCount(); ?></span>
                    </a>
                    <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <a href="#" class="text-dark dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person fs-5"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="account.php">My Account</a></li>
                            <li><a class="dropdown-item" href="wishlist.php">Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <a href="login.php" class="text-dark"><i class="bi bi-person fs-5"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>