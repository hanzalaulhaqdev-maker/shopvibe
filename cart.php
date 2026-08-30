<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
include 'includes/header.php';

$cart_items = [];
$subtotal = 0;
$shipping = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $stmt = $conn->prepare("SELECT id, name, price, sale_price, image_main, stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($product) {
            $price = $product['sale_price'] ? $product['sale_price'] : $product['price'];
            $item_total = $price * $item['quantity'];
            $subtotal += $item_total;
            $cart_items[] = array_merge($item, [
                'name' => $product['name'],
                'price' => $price,
                'image_main' => $product['image_main'],
                'item_total' => $item_total,
                'stock' => $product['stock']
            ]);
        }
    }
}

$shipping = $subtotal > 100 ? 0 : 10;
$total = $subtotal + $shipping;
?>

<section class="py-5">
    <div class="container">
        <h1 class="fw-bold mb-4">Shopping Cart</h1>

        <div id="cart-content">
            <?php if (empty($cart_items)): ?>
            <div class="empty-cart" id="empty-cart-message">
                <i class="bi bi-bag-x"></i>
                <h3 class="fw-bold mb-2">Your cart is empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added anything to your cart yet.</p>
                <a href="shop.php" class="btn btn-dark px-4">Start Shopping</a>
            </div>
            <?php else: ?>
            <div class="row g-5" id="cart-items-container">
                <div class="col-lg-8">
                    <div class="table-responsive">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="cart-table-body">
                                <?php foreach ($cart_items as $item): ?>
                                <tr data-id="<?php echo $item['product_id']; ?>" data-size="<?php echo htmlspecialchars($item['size']); ?>" data-color="<?php echo htmlspecialchars($item['color']); ?>">
                                    <td data-label="Product">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="assets/images/<?php echo $item['image_main']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-product-img">
                                            <div>
                                                <div class="cart-product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <div class="cart-product-meta">
                                                    <?php if ($item['size']) echo 'Size: ' . htmlspecialchars($item['size']); ?>
                                                    <?php if ($item['color']) echo ($item['size'] ? ' | ' : '') . 'Color: ' . htmlspecialchars($item['color']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Price"><?php echo formatPrice($item['price']); ?></td>
                                    <td data-label="Quantity">
                                        <div class="qty-control">
                                            <button type="button" class="qty-minus">-</button>
                                            <input type="number" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="qty-input">
                                            <button type="button" class="qty-plus">+</button>
                                        </div>
                                    </td>
                                    <td data-label="Total" class="item-total fw-bold"><?php echo formatPrice($item['item_total']); ?></td>
                                    <td>
                                        <button class="remove-from-cart remove-btn">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <a href="shop.php" class="text-dark text-decoration-none"><i class="bi bi-arrow-left me-2"></i>Continue Shopping</a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="cart-summary-card">
                        <h5 class="fw-bold mb-4">Order Summary</h5>

                        <div class="cart-summary-row">
                            <span>Subtotal</span>
                            <span id="subtotal-value" data-value="<?php echo $subtotal; ?>"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        <div class="cart-summary-row">
                            <span>Shipping</span>
                            <span id="shipping-value"><?php echo $shipping == 0 ? 'Free' : formatPrice($shipping); ?></span>
                        </div>
                        <div class="cart-summary-row total">
                            <span>Total</span>
                            <span id="cart-total"><?php echo formatPrice($total); ?></span>
                        </div>

                        <a href="checkout.php" class="btn btn-dark w-100 mt-3">Proceed to Checkout</a>
                        <p class="small text-muted mt-2 mb-0"><i class="bi bi-shield-check me-1"></i>Secure checkout</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
// ============================================
// CART AUTO-REFRESH SYSTEM
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Refresh when page becomes visible (user comes back from product page)
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            refreshCart();
        }
    });
    
    // 2. Listen for cart updates from same tab (after adding from shop/product page)
    window.addEventListener('storage', function(e) {
        if (e.key === 'cartUpdated') {
            refreshCart();
        }
    });

    // 3. Reload on back/forward navigation (bfcache)
    window.addEventListener('pageshow', function() {
        refreshCart();
    });

    // 4. Initial load - always refresh to get latest data
    refreshCart();
});

function refreshCart() {
    fetch('ajax-cart.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartUI(data);
            }
        })
        .catch(error => console.error('Error refreshing cart:', error));
}

function updateCartUI(data) {
    const cartContent = document.getElementById('cart-content');
    const emptyMessage = document.getElementById('empty-cart-message');
    const itemsContainer = document.getElementById('cart-items-container');
    
    // Handle empty cart
    if (data.cart_items.length === 0) {
        if (itemsContainer) itemsContainer.style.display = 'none';
        if (!emptyMessage) {
            cartContent.innerHTML = `
                <div class="empty-cart" id="empty-cart-message">
                    <i class="bi bi-bag-x"></i>
                    <h3 class="fw-bold mb-2">Your cart is empty</h3>
                    <p class="text-muted mb-4">Looks like you haven't added anything to your cart yet.</p>
                    <a href="shop.php" class="btn btn-dark px-4">Start Shopping</a>
                </div>
            `;
        } else {
            emptyMessage.style.display = 'block';
        }
        return;
    }

    // Show items, hide empty message
    if (emptyMessage) emptyMessage.style.display = 'none';
    if (itemsContainer) {
        itemsContainer.style.display = 'flex';
    } else {
        // Rebuild the whole cart layout if it doesn't exist
        rebuildCartLayout(data);
        return;
    }

    // Update table body
    const tbody = document.getElementById('cart-table-body');
    if (tbody) {
        tbody.innerHTML = data.cart_items.map(item => `
            <tr data-id="${item.product_id}" data-size="${item.size || ''}" data-color="${item.color || ''}">
                <td data-label="Product">
                    <div class="d-flex align-items-center gap-3">
                        <img src="assets/images/${item.image_main}" alt="${item.name}" class="cart-product-img">
                        <div>
                            <div class="cart-product-name">${item.name}</div>
                            <div class="cart-product-meta">
                                ${item.size ? 'Size: ' + item.size : ''}
                                ${item.color ? (item.size ? ' | ' : '') + 'Color: ' + item.color : ''}
                            </div>
                        </div>
                    </div>
                </td>
                <td data-label="Price">${item.price_formatted}</td>
                <td data-label="Quantity">
                    <div class="qty-control">
                        <button type="button" class="qty-minus">-</button>
                        <input type="number" value="${item.quantity}" min="1" max="${item.stock}" class="qty-input">
                        <button type="button" class="qty-plus">+</button>
                    </div>
                </td>
                <td data-label="Total" class="item-total fw-bold">${item.item_total_formatted}</td>
                <td>
                    <button class="remove-from-cart remove-btn">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </td>
            </tr>
        `).join('');
    }

    // Update summary
    const subtotalEl = document.getElementById('subtotal-value');
    const shippingEl = document.getElementById('shipping-value');
    const totalEl = document.getElementById('cart-total');
    if (subtotalEl) {
        subtotalEl.textContent = data.subtotal_formatted;
        subtotalEl.setAttribute('data-value', data.subtotal);
    }
    if (shippingEl) shippingEl.textContent = data.shipping === 0 ? 'Free' : data.shipping_formatted;
    if (totalEl) totalEl.textContent = data.total_formatted;
    
    // Re-attach event listeners
    attachCartEventListeners();
}

function rebuildCartLayout(data) {
    const cartContent = document.getElementById('cart-content');
    
    cartContent.innerHTML = `
        <div class="row g-5" id="cart-items-container">
            <div class="col-lg-8">
                <div class="table-responsive">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cart-table-body">
                            ${data.cart_items.map(item => `
                            <tr data-id="${item.product_id}" data-size="${item.size || ''}" data-color="${item.color || ''}">
                                <td data-label="Product">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="assets/images/${item.image_main}" alt="${item.name}" class="cart-product-img">
                                        <div>
                                            <div class="cart-product-name">${item.name}</div>
                                            <div class="cart-product-meta">
                                                ${item.size ? 'Size: ' + item.size : ''}
                                                ${item.color ? (item.size ? ' | ' : '') + 'Color: ' + item.color : ''}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Price">${item.price_formatted}</td>
                                <td data-label="Quantity">
                                    <div class="qty-control">
                                        <button type="button" class="qty-minus">-</button>
                                        <input type="number" value="${item.quantity}" min="1" max="${item.stock}" class="qty-input">
                                        <button type="button" class="qty-plus">+</button>
                                    </div>
                                </td>
                                <td data-label="Total" class="item-total fw-bold">${item.item_total_formatted}</td>
                                <td>
                                    <button class="remove-from-cart remove-btn">
                                        <i class="bi bi-trash"></i> Remove
                                    </button>
                                </td>
                            </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <a href="shop.php" class="text-dark text-decoration-none"><i class="bi bi-arrow-left me-2"></i>Continue Shopping</a>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="cart-summary-card">
                    <h5 class="fw-bold mb-4">Order Summary</h5>
                    <div class="cart-summary-row">
                        <span>Subtotal</span>
                        <span id="subtotal-value" data-value="${data.subtotal}">${data.subtotal_formatted}</span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Shipping</span>
                        <span id="shipping-value">${data.shipping === 0 ? 'Free' : data.shipping_formatted}</span>
                    </div>
                    <div class="cart-summary-row total">
                        <span>Total</span>
                        <span id="cart-total">${data.total_formatted}</span>
                    </div>
                    <a href="checkout.php" class="btn btn-dark w-100 mt-3">Proceed to Checkout</a>
                    <p class="small text-muted mt-2 mb-0"><i class="bi bi-shield-check me-1"></i>Secure checkout</p>
                </div>
            </div>
        </div>
    `;
    
    attachCartEventListeners();
}

function attachCartEventListeners() {
    // Quantity minus
    document.querySelectorAll('.qty-minus').forEach(btn => {
        btn.onclick = function() {
            const input = this.nextElementSibling;
            let val = parseInt(input.value) - 1;
            if (val >= 1) {
                input.value = val;
                updateQuantity(this.closest('tr'), val);
            }
        };
    });

    // Quantity plus
    document.querySelectorAll('.qty-plus').forEach(btn => {
        btn.onclick = function() {
            const input = this.previousElementSibling;
            let val = parseInt(input.value) + 1;
            const max = parseInt(input.getAttribute('max'));
            if (val <= max) {
                input.value = val;
                updateQuantity(this.closest('tr'), val);
            }
        };
    });

    // Remove buttons
    document.querySelectorAll('.remove-from-cart').forEach(btn => {
        btn.onclick = function() {
            const row = this.closest('tr');
            const productId = row.getAttribute('data-id');
            const size = row.getAttribute('data-size');
            const color = row.getAttribute('data-color');
            removeFromCart(productId, size, color);
        };
    });
}

function updateQuantity(row, quantity) {
    const productId = row.getAttribute('data-id');
    const size = row.getAttribute('data-size');
    const color = row.getAttribute('data-color');
    
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    formData.append('size', size);
    formData.append('color', color);

    fetch('update-cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            refreshCart();
        }
    });
}

function removeFromCart(productId, size, color) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('size', size);
    formData.append('color', color);

    fetch('remove-from-cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            refreshCart();
        }
    });
}

// Attach listeners on initial load
attachCartEventListeners();
</script>

<?php include 'includes/footer.php'; ?>