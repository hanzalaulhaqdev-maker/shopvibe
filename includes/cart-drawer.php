<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
?>
<div class="cart-drawer" id="cart-drawer">
    <div class="cart-drawer-header">
        <h5 class="mb-0 fw-bold">Your Cart</h5>
        <button class="btn-close" id="cart-close"></button>
    </div>

    <!-- Items injected by refreshDrawer() -->
    <div class="cart-drawer-body" id="cart-drawer-items">
        <div class="text-center py-5">
            <i class="bi bi-bag-x fs-1 text-muted"></i>
            <p class="text-muted mt-3">Your cart is empty</p>
            <a href="shop.php" class="btn btn-dark btn-sm">Start Shopping</a>
        </div>
    </div>

    <!-- Footer shown/hidden by JS -->
    <div class="cart-drawer-footer" id="cart-drawer-footer" style="display:none">
        <div class="d-flex justify-content-between mb-3">
            <span class="fw-bold">Subtotal</span>
            <span class="fw-bold" id="cart-drawer-total"></span>
        </div>
        <a href="cart.php" class="btn btn-dark w-100 mb-2">View Cart</a>
        <a href="checkout.php" class="btn btn-outline-dark w-100">Checkout</a>
    </div>
</div>

<script>
// -------------------------------------------------------
// Refresh the cart drawer via AJAX — called from anywhere
// -------------------------------------------------------
function refreshDrawer() {
    fetch('ajax/get-cart-drawer.php')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            // Update items
            document.getElementById('cart-drawer-items').innerHTML = data.items_html;

            // Show/hide footer
            const footer = document.getElementById('cart-drawer-footer');
            if (footer) footer.style.display = data.has_items ? 'block' : 'none';

            // Update subtotal
            const totalEl = document.getElementById('cart-drawer-total');
            if (totalEl) totalEl.textContent = data.subtotal;

            // Update header badge
            const badge = document.getElementById('cart-count');
            if (badge) badge.textContent = data.cart_count;

            // Re-attach remove listeners
            attachRemoveListeners();
        })
        .catch(err => console.error('Drawer refresh error:', err));
}

// -------------------------------------------------------
// Remove item from cart
// -------------------------------------------------------
function attachRemoveListeners() {
    document.querySelectorAll('.remove-cart-item').forEach(btn => {
        btn.onclick = function () {
            const key = this.dataset.key;
            const fd  = new FormData();
            fd.append('cart_key', key);
            fetch('ajax/remove-from-cart.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        refreshDrawer();
                        localStorage.setItem('cartUpdated', Date.now().toString());
                    }
                });
        };
    });
}

// Load drawer on page init
document.addEventListener('DOMContentLoaded', function () {
    refreshDrawer();

    // Reload drawer if another tab updated the cart
    window.addEventListener('storage', function (e) {
        if (e.key === 'cartUpdated') refreshDrawer();
    });
});
</script>
