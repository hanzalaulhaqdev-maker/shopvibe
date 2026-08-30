<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
include 'includes/admin-header.php';

// Stats
$revenue = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE status != 'cancelled'")->fetch_assoc()['total'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'];
$total_customers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Recent orders
$recent_orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");

// Top products - FIXED: Compatible with MySQL 5.7/MariaDB < 10.6
// Using JSON_EXTRACT with numbers table instead of JSON_TABLE
$top_products = $conn->query("
    SELECT 
        p.name, 
        p.image_main, 
        COUNT(*) as sales_count 
    FROM orders o 
    CROSS JOIN (
        SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
        UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
        UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14
        UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19
    ) numbers
    JOIN products p ON p.id = JSON_UNQUOTE(JSON_EXTRACT(o.items_json, CONCAT('$[', numbers.n, '].product_id')))
    WHERE o.status != 'cancelled' 
        AND JSON_EXTRACT(o.items_json, CONCAT('$[', numbers.n, ']')) IS NOT NULL
    GROUP BY p.id 
    ORDER BY sales_count DESC 
    LIMIT 5
");

// Monthly revenue
$monthly = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COALESCE(SUM(total), 0) as revenue
    FROM orders WHERE status != 'cancelled'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC LIMIT 6
");
$monthly_data = [];
$max_revenue = 0;
while ($row = $monthly->fetch_assoc()) {
    $monthly_data[] = $row;
    if ($row['revenue'] > $max_revenue) $max_revenue = $row['revenue'];
}
$monthly_data = array_reverse($monthly_data);
?>

<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="stat-card-value"><?php echo formatPrice($revenue); ?></div>
            <div class="stat-card-label">Total Revenue</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-cart3"></i>
            </div>
            <div class="stat-card-value"><?php echo $total_orders; ?></div>
            <div class="stat-card-label">Total Orders</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-clock"></i>
            </div>
            <div class="stat-card-value"><?php echo $pending_orders; ?></div>
            <div class="stat-card-label">Pending Orders</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-card-value"><?php echo $total_customers; ?></div>
            <div class="stat-card-label">Customers</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold mb-4">Recent Orders</h5>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td><a href="order-detail.php?id=<?php echo $order['id']; ?>" class="fw-bold text-dark"><?php echo $order['order_number']; ?></a></td>
                                <td><?php echo $order['customer_name']; ?></td>
                                <td><?php echo formatPrice($order['total']); ?></td>
                                <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-4">Monthly Revenue</h5>
                <?php foreach ($monthly_data as $m): 
                    $width = $max_revenue > 0 ? ($m['revenue'] / $max_revenue * 100) : 0;
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?php echo date('M Y', strtotime($m['month'] . '-01')); ?></span>
                        <span class="fw-bold"><?php echo formatPrice($m['revenue']); ?></span>
                    </div>
                    <div style="background: #e9ecef; height: 30px;">
                        <div class="chart-bar" style="width: <?php echo $width; ?>%"><?php echo round($width); ?>%</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold mb-4">Top Products</h5>
                <?php if ($top_products && $top_products->num_rows > 0): ?>
                    <?php while ($product = $top_products->fetch_assoc()): ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="../assets/images/<?php echo $product['image_main']; ?>" style="width: 40px; height: 50px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <div class="fw-bold small"><?php echo $product['name']; ?></div>
                            <div class="small text-muted"><?php echo $product['sales_count']; ?> sales</div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted small">No sales data available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>