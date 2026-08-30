<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity   = max(0, intval($_POST['quantity'] ?? 1));
    $size       = $_POST['size']  ?? '';
    $color      = $_POST['color'] ?? '';

    if ($product_id <= 0) {
        $response['message'] = 'Invalid product';
        echo json_encode($response);
        exit;
    }

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    // quantity = 0 means remove the item
    if ($quantity <= 0) {
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function ($item) use ($product_id) {
            return $item['product_id'] != $product_id;
        }));
        $response['success']    = true;
        $response['cart_count'] = getCartCount();
        $response['cart_total'] = getCartTotal();
        echo json_encode($response);
        exit;
    }

    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id &&
            $item['size']       == $size        &&
            $item['color']      == $color) {

            $item['quantity'] = $quantity;

            $stmt = $conn->prepare("SELECT price, sale_price FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $price = $product['sale_price'] ? $product['sale_price'] : $product['price'];

            $response['success']              = true;
            $response['item_total']           = $price * $quantity;
            $response['item_total_formatted'] = formatPrice($price * $quantity);
            $response['cart_total']           = getCartTotal();
            $response['cart_count']           = getCartCount();
            break;
        }
    }
    unset($item);
}

echo json_encode($response);
