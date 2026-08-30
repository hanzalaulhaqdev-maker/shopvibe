<?php
require_once __DIR__ . '/../config/db.php';

function getCartCount() {
    $count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    return $count;
}

function getCartTotal() {
    global $conn;
    $total = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $conn->prepare("SELECT price, sale_price FROM products WHERE id = ?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $price = $row['sale_price'] ? $row['sale_price'] : $row['price'];
                $total += $price * $item['quantity'];
            }
            $stmt->close();
        }
    }
    return $total;
}

function formatPrice($price) {
    return '$' . number_format($price, 2);
}

function generateSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return $slug;
}

function generateOrderNumber() {
    return 'SV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
?>