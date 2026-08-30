<div class="admin-sidebar">
    <div class="admin-sidebar-brand">ShopVibe Admin</div>
    <ul class="admin-sidebar-menu">
        <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li><a href="products.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'products-add.php', 'products-edit.php']) ? 'active' : ''; ?>"><i class="bi bi-box-seam"></i> Products</a></li>
        <li><a href="coupons.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['coupons.php', 'coupons-add.php', 'coupons-edit.php']) ? 'active' : ''; ?>"><i class="bi bi-ticket-perforated"></i> Coupons</a></li>
        <li><a href="orders.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['orders.php', 'order-detail.php']) ? 'active' : ''; ?>"><i class="bi bi-cart3"></i> Orders</a></li>
        <li><a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>"><i class="bi bi-tags"></i> Categories</a></li>
        <li><a href="customers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>"><i class="bi bi-people"></i> Customers</a></li>
        <li><a href="../index.php" target="_blank"><i class="bi bi-shop"></i> View Store</a></li>
    </ul>
</div>