<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$response = ['valid' => false, 'message' => '', 'discount' => 0, 'new_total' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $subtotal = floatval($_POST['subtotal'] ?? 0);

    if (empty($code)) {
        $response['message'] = 'Please enter a coupon code';
        echo json_encode($response);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND expires_at >= CURDATE()");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $coupon = $result->fetch_assoc();
    $stmt->close();

    if (!$coupon) {
        $response['message'] = 'Invalid or expired coupon code';
        echo json_encode($response);
        exit;
    }

    if ($coupon['used_count'] >= $coupon['max_uses']) {
        $response['message'] = 'Coupon usage limit reached';
        echo json_encode($response);
        exit;
    }

    if ($subtotal < $coupon['min_order']) {
        $response['message'] = 'Minimum order of ' . formatPrice($coupon['min_order']) . ' required';
        echo json_encode($response);
        exit;
    }

    $discount = 0;
    if ($coupon['type'] === 'percent') {
        $discount = $subtotal * ($coupon['discount'] / 100);
    } else {
        $discount = $coupon['discount'];
    }

    $new_total = max(0, $subtotal - $discount);

    // Update used count
    $stmt = $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $coupon['id']);
    $stmt->execute();
    $stmt->close();

    $_SESSION['applied_coupon'] = [
        'code' => $code,
        'discount' => $discount,
        'type' => $coupon['type']
    ];

    $response['valid'] = true;
    $response['discount'] = $discount;
    $response['new_total'] = $new_total;
    $response['message'] = 'Coupon applied successfully!';
}

echo json_encode($response);