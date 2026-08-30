<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$items_html = '';
$subtotal   = 0;
$has_items  = false;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $item) {
        $stmt = $conn->prepare("SELECT name, price, sale_price, image_main FROM products WHERE id = ?");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$product) continue;

        $price     = $product['sale_price'] ? $product['sale_price'] : $product['price'];
        $subtotal += $price * $item['quantity'];
        $has_items = true;

        $name      = htmlspecialchars($product['name']);
        $img       = htmlspecialchars($product['image_main']);
        $size_line = $item['size']  ? 'Size: '  . htmlspecialchars($item['size'])  : '';
        $col_line  = $item['color'] ? ($item['size'] ? ' | ' : '') . 'Color: ' . htmlspecialchars($item['color']) : '';
        $price_fmt = formatPrice($price);
        $qty       = (int)$item['quantity'];

        $items_html .= "
        <div class=\"cart-item\">
            <img src=\"assets/images/{$img}\" alt=\"{$name}\" class=\"cart-item-img\">
            <div class=\"cart-item-details\">
                <h6 class=\"mb-1\">{$name}</h6>
                <p class=\"mb-0 small text-muted\">{$size_line}{$col_line}</p>
                <div class=\"d-flex justify-content-between align-items-center mt-2\">
                    <span class=\"fw-bold\">{$price_fmt} x {$qty}</span>
                    <button class=\"btn btn-link btn-sm text-danger p-0 remove-cart-item\" data-key=\"{$key}\">
                        <i class=\"bi bi-trash\"></i>
                    </button>
                </div>
            </div>
        </div>";
    }
}

if (!$has_items) {
    $items_html = '
        <div class="text-center py-5">
            <i class="bi bi-bag-x fs-1 text-muted"></i>
            <p class="text-muted mt-3">Your cart is empty</p>
            <a href="shop.php" class="btn btn-dark btn-sm">Start Shopping</a>
        </div>';
}

echo json_encode([
    'success'        => true,
    'items_html'     => $items_html,
    'subtotal'       => formatPrice($subtotal),
    'has_items'      => $has_items,
    'cart_count'     => getCartCount(),
]);
