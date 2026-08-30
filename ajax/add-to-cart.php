<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cart_count' => 0, 'cart_total' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity   = max(1, intval($_POST['quantity'] ?? 1));
    $size       = sanitize($_POST['size']  ?? '');
    $color      = sanitize($_POST['color'] ?? '');
    $buy_now    = isset($_POST['buy_now']) && $_POST['buy_now'] === '1';

    if ($product_id <= 0) {
        $response['message'] = 'Invalid product';
        echo json_encode($response);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, stock, name, price, sale_price FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        $response['message'] = 'Product not found';
        echo json_encode($response);
        exit;
    }

    // === BUY NOW MODE ===
    if ($buy_now) {
        $_SESSION['buy_now_item'] = [
            'product_id' => $product_id,
            'quantity'   => $quantity,
            'size'       => $size,
            'color'      => $color,
            'added_at'   => time()
        ];
        $response['success']    = true;
        $response['message']    = 'Ready for checkout';
        $response['cart_count'] = getCartCount();
        $response['cart_total'] = getCartTotal();
        echo json_encode($response);
        exit;
    }

    // === REGULAR CART MODE ===
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id &&
            $item['size']       == $size        &&
            $item['color']      == $color) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    unset($item);

    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity'   => $quantity,
            'size'       => $size,
            'color'      => $color,
            'added_at'   => time()
        ];
    }

    $response['success']    = true;
    $response['message']    = 'Added to cart';
    $response['cart_count'] = getCartCount();
    $response['cart_total'] = getCartTotal();
}

// Notify same-server session flag (for any server-side checks)
if ($response['success']) {
    $_SESSION['cart_updated'] = time();
}

echo json_encode($response);
