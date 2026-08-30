<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['key'] ?? null;
    $product_id = intval($_POST['product_id'] ?? 0);

    if ($key !== null && isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        $response['success'] = true;
        $response['cart_count'] = getCartCount();
        $response['cart_total'] = getCartTotal();
    } elseif ($product_id > 0) {
        foreach ($_SESSION['cart'] as $k => $item) {
            if ($item['product_id'] == $product_id) {
                unset($_SESSION['cart'][$k]);
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        $response['success'] = true;
        $response['cart_count'] = getCartCount();
        $response['cart_total'] = getCartTotal();
    }
}

echo json_encode($response);