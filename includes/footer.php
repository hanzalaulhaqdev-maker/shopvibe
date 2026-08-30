<footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h4 class="fw-bold mb-3">ShopVibe</h4>
                    <p class="text-light opacity-75">Discover styles that define you. Premium fashion for the modern individual.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white"><i class="bi bi-facebook fs-5"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-instagram fs-5"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-twitter fs-5"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-pinterest fs-5"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="fw-bold mb-3">Shop</h6>
                    <ul class="list-unstyled">
                        <li><a href="shop.php?category=women" class="text-light opacity-75 text-decoration-none">Women</a></li>
                        <li><a href="shop.php?category=men" class="text-light opacity-75 text-decoration-none">Men</a></li>
                        <li><a href="shop.php?category=accessories" class="text-light opacity-75 text-decoration-none">Accessories</a></li>
                        <li><a href="shop.php?category=shoes" class="text-light opacity-75 text-decoration-none">Shoes</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="fw-bold mb-3">Help</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-light opacity-75 text-decoration-none">Shipping</a></li>
                        <li><a href="#" class="text-light opacity-75 text-decoration-none">Returns</a></li>
                        <li><a href="#" class="text-light opacity-75 text-decoration-none">FAQ</a></li>
                        <li><a href="#" class="text-light opacity-75 text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h6 class="fw-bold mb-3">Newsletter</h6>
                    <p class="text-light opacity-75 small">Subscribe for exclusive offers and style tips.</p>
                    <form class="d-flex gap-2">
                        <input type="email" class="form-control" placeholder="Your email">
                        <button type="submit" class="btn btn-light">Subscribe</button>
                    </form>
                </div>
            </div>
            <hr class="my-4 opacity-25">
            <div class="text-center text-light opacity-50 small">
                &copy; 2025 ShopVibe. All rights reserved.
            </div>
        </div>
    </footer>

    <div class="cart-overlay" id="cart-overlay"></div>
    <?php include __DIR__ . '/cart-drawer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/main.js"></script>
    <?php if (strpos($_SERVER['PHP_SELF'], 'index.php') !== false): ?>
    <script src="assets/js/hero.js"></script>
    <?php endif; ?>
    <?php if (strpos($_SERVER['PHP_SELF'], 'shop.php') !== false): ?>
    <script src="assets/js/shop.js"></script>
    <?php endif; ?>
    <?php if (strpos($_SERVER['PHP_SELF'], 'cart.php') !== false || strpos($_SERVER['PHP_SELF'], 'product.php') !== false): ?>
    <script src="assets/js/cart.js"></script>
    <?php endif; ?>
    <?php if (strpos($_SERVER['PHP_SELF'], 'admin/') !== false): ?>
    <script src="../assets/js/admin.js"></script>
    <?php endif; ?>
</body>
</html>