$(document).ready(function() {
    // Cart Drawer Toggle (desktop + mobile)
    $('#cart-toggle, #cart-toggle-mobile').on('click', function() {
        $('#cart-drawer').addClass('open');
        $('#cart-overlay').addClass('active');
    });

    $('#cart-close, #cart-overlay').on('click', function() {
        $('#cart-drawer').removeClass('open');
        $('#cart-overlay').removeClass('active');
    });

    // Remove from cart drawer
    $(document).on('click', '.remove-cart-item', function() {
        var key = $(this).data('key');
        var btn = $(this);
        $.ajax({
            url: 'ajax/remove-from-cart.php',
            type: 'POST',
            data: { key: key },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    btn.closest('.cart-item').fadeOut(300, function() {
                        $(this).remove();
                        updateCartUI(response);
                        if (response.cart_count === 0) {
                            location.reload();
                        }
                    });
                }
            }
        });
    });

    // Season label typewriter effect
    var seasonTexts = ['SS 2025 COLLECTION', 'NEW ARRIVALS', 'EXCLUSIVE STYLES'];
    var seasonIndex = 0;
    var seasonLabel = $('.season-label');

    function typeWriter(text, i, callback) {
        if (i < text.length) {
            seasonLabel.text(text.substring(0, i + 1));
            setTimeout(function() {
                typeWriter(text, i + 1, callback);
            }, 80);
        } else {
            setTimeout(callback, 2000);
        }
    }

    function startTypewriter() {
        var text = seasonTexts[seasonIndex];
        seasonLabel.text('');
        typeWriter(text, 0, function() {
            seasonIndex = (seasonIndex + 1) % seasonTexts.length;
            startTypewriter();
        });
    }

    if (seasonLabel.length) {
        startTypewriter();
    }

    // Quick add to cart
    $(document).on('click', '.quick-add-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var productId = $(this).data('id');
        var btn = $(this);

        $.ajax({
            url: 'ajax/add-to-cart.php',
            type: 'POST',
            data: {
                product_id: productId,
                size: '',
                color: '',
                quantity: 1
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#cart-count, #cart-count-mobile').text(response.cart_count);
                    btn.text('Added!').addClass('bg-success');
                    setTimeout(function() {
                        btn.text('Quick Add').removeClass('bg-success');
                    }, 1500);

                    // Refresh cart drawer
                    $.ajax({
                        url: 'includes/cart-drawer.php',
                        type: 'GET',
                        success: function(html) {
                            $('#cart-drawer').replaceWith(html);
                            $('#cart-drawer').addClass('open');
                            $('#cart-overlay').addClass('active');
                        }
                    });
                } else {
                    alert(response.message);
                }
            }
        });
    });

    // Wishlist toggle
    $(document).on('click', '.wishlist-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var btn = $(this);
        var productId = btn.data('id');

        if (btn.hasClass('active')) {
            $.ajax({
                url: 'ajax/remove-from-wishlist.php',
                type: 'POST',
                data: { product_id: productId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        btn.removeClass('active');
                        btn.find('i').removeClass('bi-heart-fill').addClass('bi-heart');
                    }
                }
            });
        } else {
            $.ajax({
                url: 'ajax/add-to-wishlist.php',
                type: 'POST',
                data: { product_id: productId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        btn.addClass('active');
                        btn.find('i').removeClass('bi-heart').addClass('bi-heart-fill');
                    } else if (response.message === 'login_required') {
                        window.location.href = 'login.php';
                    }
                }
            });
        }
    });

    // Flash Sale Countdown
    var flashSaleEnd = new Date();
    flashSaleEnd.setHours(flashSaleEnd.getHours() + 24);

    function updateCountdown() {
        var now = new Date();
        var diff = flashSaleEnd - now;

        if (diff <= 0) {
            diff = 24 * 60 * 60 * 1000;
            flashSaleEnd = new Date(now.getTime() + diff);
        }

        var hours = Math.floor(diff / (1000 * 60 * 60));
        var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((diff % (1000 * 60)) / 1000);

        $('#countdown-hours').text(String(hours).padStart(2, '0'));
        $('#countdown-minutes').text(String(minutes).padStart(2, '0'));
        $('#countdown-seconds').text(String(seconds).padStart(2, '0'));
    }

    if ($('#countdown-hours').length) {
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

    // Lazy loading images
    function lazyLoad() {
        $('.lazy-img').each(function() {
            var img = $(this);
            if (img.offset().top < $(window).scrollTop() + $(window).height() + 200) {
                img.attr('src', img.data('src')).addClass('loaded');
            }
        });
    }

    $(window).on('scroll resize', lazyLoad);
    lazyLoad();
});

function updateCartUI(response) {
    $('#cart-count, #cart-count-mobile').text(response.cart_count);
    if ($('#cart-drawer-total').length) {
        $('#cart-drawer-total').text('$' + parseFloat(response.cart_total).toFixed(2));
    }
}