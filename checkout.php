<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// === BUY NOW MODE DETECTION ===
$buy_now_mode = isset($_GET['buy_now']) && !empty($_SESSION['buy_now_item']);

if ($buy_now_mode) {
    // Use only the buy_now_item for checkout
    $checkout_items = [$_SESSION['buy_now_item']];
} else {
    // Regular cart checkout
    $checkout_items = $_SESSION['cart'] ?? [];
}

// Redirect if nothing to checkout
if (empty($checkout_items)) {
    redirect('cart.php');
}

// === CALCULATE TOTALS ===
$subtotal = 0;
foreach ($checkout_items as $item) {
    $stmt = $conn->prepare("SELECT price, sale_price FROM products WHERE id = ?");
    $stmt->bind_param("i", $item['product_id']);
    $stmt->execute();
    $prod = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($prod) {
        $price = $prod['sale_price'] ? $prod['sale_price'] : $prod['price'];
        $subtotal += $price * $item['quantity'];
    }
}

$discount = $_SESSION['applied_coupon']['discount'] ?? 0;
$coupon_code_applied = $_SESSION['applied_coupon']['code'] ?? '';
$shipping = $subtotal > 100 ? 0 : 10;
$total = max(0, $subtotal + $shipping - $discount);

$error = $_SESSION['payment_error'] ?? '';
$success = '';
unset($_SESSION['payment_error']); // one-time display

// === FORM SUBMISSION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $country = sanitize($_POST['country'] ?? '');
    $zip = sanitize($_POST['zip'] ?? '');
    $payment = sanitize($_POST['payment'] ?? 'cod');

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($zip)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $order_number = generateOrderNumber();

        // Save checkout data to session (needed for PayPal/Payoneer redirects)
        $_SESSION['checkout_data'] = [
            'order_number' => $order_number,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'country' => $country,
            'zip' => $zip,
            'payment' => $payment,
            'buy_now_mode' => $buy_now_mode // Track mode for post-order cleanup
        ];

        // === PAYPAL ===
        if ($payment === 'paypal') {
            header('Location: payments/paypal-process.php');
            exit;
        }

        // === PAYONEER ===
        if ($payment === 'payoneer') {
            header('Location: payments/payoneer-process.php');
            exit;
        }

        // === COD / BANK TRANSFER (direct order creation) ===
        $user_id = $_SESSION['user_id'] ?? null;
        $session_id = session_id();
        $shipping_address = "$address, $city, $country $zip";
        $items_json = json_encode($checkout_items); // Use $checkout_items, not $_SESSION['cart']

        // Re-read discount from session in case it was updated via AJAX before submit
        $discount = $_SESSION['applied_coupon']['discount'] ?? 0;
        $total = max(0, $subtotal + $shipping - $discount);

        $stmt = $conn->prepare("INSERT INTO orders 
            (order_number, user_id, session_id, customer_name, customer_email, customer_phone, 
             shipping_address, items_json, subtotal, shipping_fee, discount, total, 
             payment_method, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");

        $stmt->bind_param(
            "sissssssdddds",
            $order_number, $user_id, $session_id,
            $name, $email, $phone,
            $shipping_address, $items_json,
            $subtotal, $shipping, $discount, $total,
            $payment
        );

        if ($stmt->execute()) {
            // === CLEANUP SESSION ===
            if ($buy_now_mode) {
                // Clear only the buy_now_item
                unset($_SESSION['buy_now_item']);
            } else {
                // Clear entire cart for regular checkout
                $_SESSION['cart'] = [];
            }
            
            unset($_SESSION['applied_coupon']);
            unset($_SESSION['checkout_data']);
            $_SESSION['last_order'] = $order_number;
            
            redirect('order-success.php');
        } else {
            $error = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <h1 class="fw-bold mb-4">Checkout</h1>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-5">
            <!-- Billing Form -->
            <div class="col-lg-7">
                <form method="POST" action="" id="checkout-form" novalidate>
                    <h5 class="fw-bold mb-3">Billing Details</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone *</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address *</label>
                            <textarea name="address" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City *</label>
                            <input type="text" name="city" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Country *</label>
                            <input type="text" name="country" class="form-control" required value="USA">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ZIP Code *</label>
                            <input type="text" name="zip" class="form-control" required>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3 mt-4">Payment Method</h5>

                    <div class="payment-methods">
                        <!-- Cash on Delivery -->
                        <div class="form-check mb-3 p-3 border rounded">
                            <input class="form-check-input" type="radio" name="payment" id="pay-cod" value="cod" checked>
                            <label class="form-check-label d-flex align-items-center gap-2" for="pay-cod">
                                <i class="bi bi-cash-stack fs-4"></i>
                                <div>
                                    <strong>Cash on Delivery</strong>
                                    <div class="small text-muted">Pay when your order arrives</div>
                                </div>
                            </label>
                        </div>

                        <!-- Bank Transfer -->
                        <div class="form-check mb-3 p-3 border rounded">
                            <input class="form-check-input" type="radio" name="payment" id="pay-bank" value="bank">
                            <label class="form-check-label d-flex align-items-center gap-2" for="pay-bank">
                                <i class="bi bi-bank fs-4"></i>
                                <div>
                                    <strong>Bank Transfer</strong>
                                    <div class="small text-muted">Transfer to our bank account</div>
                                </div>
                            </label>
                        </div>

                        <!-- PayPal -->
                        <div class="form-check mb-3 p-3 border rounded">
                            <input class="form-check-input" type="radio" name="payment" id="pay-paypal" value="paypal">
                            <label class="form-check-label d-flex align-items-center gap-2" for="pay-paypal">
                                <i class="bi bi-paypal fs-4 text-primary"></i>
                                <div>
                                    <strong>PayPal</strong>
                                    <div class="small text-muted">Pay securely with your PayPal account</div>
                                </div>
                            </label>
                        </div>

                        <!-- Payoneer -->
                        <div class="form-check mb-3 p-3 border rounded">
                            <input class="form-check-input" type="radio" name="payment" id="pay-payoneer" value="payoneer">
                            <label class="form-check-label d-flex align-items-center gap-2" for="pay-payoneer">
                                <i class="bi bi-credit-card fs-4 text-success"></i>
                                <div>
                                    <strong>Payoneer</strong>
                                    <div class="small text-muted">Credit/Debit card via Payoneer Checkout</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark btn-lg w-100 mt-4" id="place-order-btn">
                        <span class="btn-text">Place Order - <span id="btn-total"><?php echo formatPrice($total); ?></span></span>
                        <span class="btn-loading d-none">
                            <span class="spinner-border spinner-border-sm me-2"></span>Processing...
                        </span>
                    </button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-5">
                <div class="cart-summary-card">
                    <h5 class="fw-bold mb-4">
                        Order Summary
                        <?php if ($buy_now_mode): ?>
                            <span class="badge bg-primary ms-2">Buy Now</span>
                        <?php endif; ?>
                    </h5>

                    <?php foreach ($checkout_items as $item): 
                        $stmt = $conn->prepare("SELECT name, price, sale_price, image_main FROM products WHERE id = ?");
                        $stmt->bind_param("i", $item['product_id']);
                        $stmt->execute();
                        $product = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                        if (!$product) continue;
                        $price = $product['sale_price'] ? $product['sale_price'] : $product['price'];
                    ?>
                    <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                        <img src="assets/images/<?php echo $product['image_main']; ?>" style="width: 60px; height: 75px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <div class="fw-bold"><?php echo $product['name']; ?></div>
                            <div class="small text-muted">
                                <?php if ($item['size']) echo 'Size: ' . $item['size']; ?>
                                <?php if ($item['color']) echo ($item['size'] ? ' | ' : '') . 'Color: ' . $item['color']; ?>
                            </div>
                            <div class="small"><?php echo formatPrice($price); ?> x <?php echo $item['quantity']; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- ===== COUPON SECTION ===== -->
                    <?php if (!$buy_now_mode): ?>
                    <div class="coupon-section mb-3">
                        <?php if ($coupon_code_applied): ?>
                            <!-- Already applied state -->
                            <div class="d-flex align-items-center gap-2">
                                <input type="text" id="coupon-code" class="form-control form-control-sm"
                                       value="<?php echo htmlspecialchars($coupon_code_applied); ?>" readonly>
                                <button type="button" id="remove-coupon" class="btn btn-sm btn-outline-danger text-nowrap">
                                    Remove
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center gap-2">
                                <input type="text" id="coupon-code" class="form-control form-control-sm"
                                       placeholder="Coupon code">
                                <button type="button" id="apply-coupon" class="btn btn-sm btn-outline-dark text-nowrap">
                                    Apply
                                </button>
                            </div>
                        <?php endif; ?>
                        <div id="coupon-message" class="mt-2 small"></div>
                    </div>
                    <?php endif; ?>
                    <!-- ===== END COUPON SECTION ===== -->

                    <div class="cart-summary-row">
                        <span>Subtotal</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Shipping</span>
                        <span id="checkout-shipping"><?php echo $shipping == 0 ? 'Free' : formatPrice($shipping); ?></span>
                    </div>
                    <div class="cart-summary-row" id="discount-row" style="display: <?php echo $discount > 0 ? 'flex' : 'none'; ?>;">
                        <span>Discount</span>
                        <span id="discount-value" class="text-danger">-<?php echo formatPrice($discount); ?></span>
                    </div>
                    <div class="cart-summary-row total">
                        <span>Total</span>
                        <span id="checkout-total" class="fs-5 fw-bold"><?php echo formatPrice($total); ?></span>
                    </div>
                </div>

                <!-- Secure badges -->
                <div class="mt-3 text-center">
                    <small class="text-muted">
                        <i class="bi bi-shield-lock me-1"></i>SSL Secure Checkout
                        <span class="mx-2">|</span>
                        <i class="bi bi-truck me-1"></i>Free shipping over $100
                    </small>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// ============================================================
// COUPON LOGIC
// ============================================================
(function () {
    var subtotal = <?php echo $subtotal; ?>;
    var shipping  = <?php echo $shipping; ?>;

    function fmt(amount) {
        return '$' + parseFloat(amount).toFixed(2);
    }

    function updateSummary(discount) {
        var total = Math.max(0, subtotal + shipping - discount);
        var discountRow = document.getElementById('discount-row');
        var discountVal = document.getElementById('discount-value');
        var totalEl     = document.getElementById('checkout-total');
        var btnTotal    = document.getElementById('btn-total');

        if (discount > 0) {
            discountRow.style.display = 'flex';
            discountVal.textContent = '-' + fmt(discount);
        } else {
            discountRow.style.display = 'none';
        }
        if (totalEl)  totalEl.textContent = fmt(total);
        if (btnTotal) btnTotal.textContent = fmt(total);
    }

    function applyCoupon() {
        var codeInput = document.getElementById('coupon-code');
        var msgEl     = document.getElementById('coupon-message');
        var code      = codeInput.value.trim().toUpperCase();

        if (!code) {
            msgEl.innerHTML = '<span class="text-danger">Please enter a coupon code.</span>';
            return;
        }

        var fd = new FormData();
        fd.append('code', code);          // apply-coupon.php reads $_POST['code']
        fd.append('subtotal', subtotal);

        fetch('ajax/apply-coupon.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                msgEl.innerHTML = data.valid
                    ? '<span class="text-success">' + data.message + '</span>'
                    : '<span class="text-danger">'  + data.message + '</span>';

                if (data.valid) {
                    updateSummary(data.discount);
                    codeInput.setAttribute('readonly', true);

                    // Swap Apply → Remove
                    document.getElementById('apply-coupon').outerHTML =
                        '<button type="button" id="remove-coupon" class="btn btn-sm btn-outline-danger text-nowrap">Remove</button>';
                    bindRemove();
                }
            })
            .catch(function () {
                document.getElementById('coupon-message').innerHTML =
                    '<span class="text-danger">Something went wrong. Please try again.</span>';
            });
    }

    function removeCoupon() {
        fetch('ajax/remove-coupon.php', { method: 'POST' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    updateSummary(0);
                    document.getElementById('coupon-message').innerHTML =
                        '<span class="text-muted">Coupon removed.</span>';

                    var codeInput = document.getElementById('coupon-code');
                    codeInput.value = '';
                    codeInput.removeAttribute('readonly');

                    // Swap Remove → Apply
                    document.getElementById('remove-coupon').outerHTML =
                        '<button type="button" id="apply-coupon" class="btn btn-sm btn-outline-dark text-nowrap">Apply</button>';
                    bindApply();
                }
            });
    }

    function bindApply() {
        var btn = document.getElementById('apply-coupon');
        if (btn) btn.addEventListener('click', applyCoupon);
    }

    function bindRemove() {
        var btn = document.getElementById('remove-coupon');
        if (btn) btn.addEventListener('click', removeCoupon);
    }

    // Bind whichever button is present on page load
    bindApply();
    bindRemove();
})();

// ============================================================
// LOADING STATE on PayPal / Payoneer submit
// ============================================================
document.getElementById('checkout-form').addEventListener('submit', function () {
    var payment = document.querySelector('input[name="payment"]:checked').value;
    if (payment === 'paypal' || payment === 'payoneer') {
        var btn = document.getElementById('place-order-btn');
        btn.querySelector('.btn-text').classList.add('d-none');
        btn.querySelector('.btn-loading').classList.remove('d-none');
        btn.disabled = true;
    }
});
</script>

<?php include 'includes/footer.php'; ?>