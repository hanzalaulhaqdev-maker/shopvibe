<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$response = ['products' => [], 'total' => 0];

$categories = $_POST['categories'] ?? [];
$min_price = floatval($_POST['min_price'] ?? 0);
$max_price = floatval($_POST['max_price'] ?? 0);
$sizes = $_POST['sizes'] ?? [];
$colors = $_POST['colors'] ?? [];
$sort = sanitize($_POST['sort'] ?? 'newest');
$page = max(1, intval($_POST['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

$where = ["p.status = 'active'"];
$params = [];
$types = "";

if (!empty($categories)) {
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $where[] = "c.slug IN ($placeholders)";
    $params = array_merge($params, $categories);
    $types .= str_repeat('s', count($categories));
}

if ($min_price > 0) {
    $where[] = "COALESCE(p.sale_price, p.price) >= ?";
    $params[] = $min_price;
    $types .= 'd';
}

if ($max_price > 0) {
    $where[] = "COALESCE(p.sale_price, p.price) <= ?";
    $params[] = $max_price;
    $types .= 'd';
}

$order = "p.created_at DESC";
switch ($sort) {
    case 'price-low': $order = "COALESCE(p.sale_price, p.price) ASC"; break;
    case 'price-high': $order = "COALESCE(p.sale_price, p.price) DESC"; break;
    case 'popular': $order = "p.stock DESC"; break;
}

$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE " . implode(' AND ', $where) . " 
        ORDER BY $order 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $response['products'][] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'slug' => $row['slug'],
        'price' => $row['price'],
        'price_formatted' => formatPrice($row['price']),
        'sale_price' => $row['sale_price'],
        'sale_price_formatted' => $row['sale_price'] ? formatPrice($row['sale_price']) : null,
        'image_main' => $row['image_main'],
        'image_hover' => $row['image_hover'],
        'category' => $row['category_name']
    ];
}
$stmt->close();

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE " . implode(' AND ', $where);
$count_stmt = $conn->prepare($count_sql);
$count_params = array_slice($params, 0, -2);
$count_types = substr($types, 0, -2);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$response['total'] = $count_result['total'];
$count_stmt->close();

echo json_encode($response);