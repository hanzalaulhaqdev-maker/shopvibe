$(document).ready(function() {
    // Quantity controls on cart page
    $(document).on('click', '.qty-minus', function() {
        var input = $(this).siblings('input');
        var val = parseInt(input.val());
        if (val > 1) {
            input.val(val - 1);
            updateCartItem(input);
        }
    });

    $(document).on('click', '.qty-plus', function() {
        var input = $(this).siblings('input');
        var val = parseInt(input.val());
        input.val(val + 1);
        updateCartItem(input);
    });

    function updateCartItem(input) {
        var row = input.closest('tr');
        var productId = row.data('id');
        var quantity = input.val();

        $.ajax({
            url: 'ajax/update-cart.php',
            type: 'POST',
            data: {
                product_id: productId,
                quantity: quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    row.find('.item-total').text('$' + parseFloat(response.item_total).toFixed(2));
                    updateCartSummary(response.cart_total);
                    $('#cart-count').text(response.cart_count);
                }
            }
        });
    }

    // Remove from cart page
    $(document).on('click', '.remove-from-cart', function() {
        var row = $(this).closest('tr');
        var productId = row.data('id');

        $.ajax({
            url: 'ajax/remove-from-cart.php',
            type: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                        updateCartSummary(response.cart_total);
                        $('#cart-count').text(response.cart_count);
                        if (response.cart_count === 0) {
                            location.reload();
                        }
                    });
                }
            }
        });
    });

    // Apply coupon
    $('#apply-coupon').on('click', function() {
        var code = $('#coupon-code').val().trim();
        if (!code) return;

        var subtotal = parseFloat($('#subtotal-value').data('value'));

        $.ajax({
            url: 'ajax/apply-coupon.php',
            type: 'POST',
            data: {
                code: code,
                subtotal: subtotal
            },
            dataType: 'json',
            success: function(response) {
                if (response.valid) {
                    $('#coupon-message').html('<span class="text-success">' + response.message + '</span>');
                    $('#discount-row').show();
                    $('#discount-value').text('-$' + parseFloat(response.discount).toFixed(2));
                    updateCartSummary(response.new_total);
                } else {
                    $('#coupon-message').html('<span class="text-danger">' + response.message + '</span>');
                }
            }
        });
    });

    function updateCartSummary(total) {
        $('#cart-total').text('$' + parseFloat(total).toFixed(2));
        $('#subtotal-value').text('$' + parseFloat(total).toFixed(2));
    }

    // Product page quantity
    $(document).on('click', '.product-qty-minus', function() {
        var input = $('#product-quantity');
        var val = parseInt(input.val());
        if (val > 1) {
            input.val(val - 1);
        }
    });

    $(document).on('click', '.product-qty-plus', function() {
        var input = $('#product-quantity');
        var val = parseInt(input.val());
        input.val(val + 1);
    });

    // Add to cart from product page
    $('#add-to-cart-btn').on('click', function() {
        var productId = $(this).data('id');
        var size = $('input[name="size"]:checked').val() || '';
        var color = $('input[name="color"]:checked').val() || '';
        var quantity = parseInt($('#product-quantity').val()) || 1;

        $.ajax({
            url: 'ajax/add-to-cart.php',
            type: 'POST',
            data: {
                product_id: productId,
                size: size,
                color: color,
                quantity: quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#cart-count').text(response.cart_count);
                    
                    var btn = $('#add-to-cart-btn');
                    var originalText = btn.text();
                    btn.text('Added to Cart!').addClass('btn-success').removeClass('btn-dark');
                    setTimeout(function() {
                        btn.text(originalText).removeClass('btn-success').addClass('btn-dark');
                    }, 2000);

                    // Open cart drawer
                    $('#cart-drawer').addClass('open');
                    $('#cart-overlay').addClass('active');
                    
                    // Refresh drawer
                    $.ajax({
                        url: 'includes/cart-drawer.php',
                        type: 'GET',
                        success: function(html) {
                            $('#cart-drawer').replaceWith(html);
                            $('#cart-drawer').addClass('open');
                        }
                    });
                } else {
                    alert(response.message);
                }
            }
        });
    });

    // Size selector on product page
    $(document).on('click', '.size-option', function() {
        $('.size-option').removeClass('active');
        $(this).addClass('active');
        $(this).find('input').prop('checked', true);
    });

    // Color selector on product page
    $(document).on('click', '.color-option', function() {
        $('.color-option').removeClass('active');
        $(this).addClass('active');
        $(this).find('input').prop('checked', true);
    });

    // Product image gallery
    $(document).on('click', '.gallery-thumb', function() {
        var src = $(this).data('src');
        $('#main-product-image').attr('src', 'assets/images/' + src);
        $('.gallery-thumb').removeClass('active');
        $(this).addClass('active');
    });
});