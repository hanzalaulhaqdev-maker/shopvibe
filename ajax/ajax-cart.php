<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/json');

$response = [
    'success'            => false,
    'cart_items'         => [],
    'subtotal'           => 0,
    'subtotal_formatted' => '',
    'shipping'           => 0,
    'shipping_formatted' => '',
    'discount'           => 0,
    'discount_formatted' => '',
    'total'              => 0,
    'total_formatted'    => '',
    'cart_count'         => 0
];

$cart_items = [];
$subtotal   = 0;
$discount   = $_SESSION['applied_coupon']['discount'] ?? 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $stmt = $conn->prepare("SELECT id, name, price, sale_price, image_main, stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($product) {
            $price      = $product['sale_price'] ? $product['sale_price'] : $product['price'];
            $item_total = $price * $item['quantity'];
            $subtotal  += $item_total;

            $cart_items[] = [
                'product_id'          => $item['product_id'],
                'name'                => $product['name'],
                'price'               => $price,
                'price_formatted'     => formatPrice($price),
                'quantity'            => $item['quantity'],
                'size'                => $item['size']  ?? '',
                'color'               => $item['color'] ?? '',
                'image_main'          => $product['image_main'],
                'item_total'          => $item_total,
                'item_total_formatted'=> formatPrice($item_total),
                'stock'               => $product['stock']
            ];
        }
    }
}

$shipping = $subtotal > 100 ? 0 : 10;
$total    = max(0, $subtotal + $shipping - $discount);

$response['success']            = true;
$response['cart_items']         = $cart_items;
$response['subtotal']           = $subtotal;
$response['subtotal_formatted'] = formatPrice($subtotal);
$response['shipping']           = $shipping;
$response['shipping_formatted'] = formatPrice($shipping);
$response['discount']           = $discount;
$response['discount_formatted'] = formatPrice($discount);
$response['total']              = $total;
$response['total_formatted']    = formatPrice($total);
$response['cart_count']         = getCartCount();

echo json_encode($response);
