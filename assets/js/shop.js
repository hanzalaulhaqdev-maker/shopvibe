$(document).ready(function() {
    var currentPage = 1;
    var isLoading = false;

    // Filter products
    $('#apply-filters').on('click', function() {
        loadProducts(1);
    });

    // Sort change
    $('#sort-select').on('change', function() {
        loadProducts(1);
    });

    // Color swatch filter
    $(document).on('click', '.color-swatch', function() {
        $(this).toggleClass('active');
        loadProducts(1);
    });

    // Size filter
    $(document).on('click', '.size-filter-btn', function() {
        $(this).toggleClass('active');
        loadProducts(1);
    });

    // Category checkbox
    $(document).on('change', '.category-filter', function() {
        loadProducts(1);
    });

    function loadProducts(page) {
        if (isLoading) return;
        isLoading = true;
        currentPage = page;

        var categories = [];
        $('.category-filter:checked').each(function() {
            categories.push($(this).val());
        });

        var sizes = [];
        $('.size-filter-btn.active').each(function() {
            sizes.push($(this).data('size'));
        });

        var colors = [];
        $('.color-swatch.active').each(function() {
            colors.push($(this).data('color'));
        });

        var data = {
            categories: categories,
            min_price: $('#min-price').val(),
            max_price: $('#max-price').val(),
            sizes: sizes,
            colors: colors,
            sort: $('#sort-select').val(),
            page: page
        };

        $('#product-grid').addClass('opacity-50');

        $.ajax({
            url: 'ajax/filter-products.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                $('#product-grid').removeClass('opacity-50');
                renderProducts(response.products);
                $('#product-count').text(response.total + ' products');
                isLoading = false;
            },
            error: function() {
                $('#product-grid').removeClass('opacity-50');
                isLoading = false;
            }
        });
    }

    function renderProducts(products) {
        var html = '';
        if (products.length === 0) {
            html = '<div class="col-12 text-center py-5"><p class="text-muted">No products found.</p></div>';
        } else {
            products.forEach(function(product) {
                var priceHtml = '<span class="product-card-price">' + product.price_formatted + '</span>';
                if (product.sale_price) {
                    priceHtml = '<span class="product-card-price"><span class="sale-price">' + product.sale_price_formatted + '</span><span class="original-price">' + product.price_formatted + '</span></span>';
                }
                var saleBadge = product.sale_price ? '<span class="sale-badge">Sale</span>' : '';
                
                html += '<div class="col-lg-4 col-md-6">' +
                    '<div class="product-card">' +
                        '<div class="product-card-img-wrapper">' +
                            saleBadge +
                            '<img src="assets/images/' + product.image_main + '" alt="' + product.name + '" class="img-main">' +
                            '<img src="assets/images/' + product.image_hover + '" alt="' + product.name + '" class="img-hover">' +
                            '<button class="quick-add-btn" data-id="' + product.id + '">Quick Add</button>' +
                        '</div>' +
                        '<div class="product-card-info">' +
                            '<h5 class="product-card-title"><a href="product.php?id=' + product.id + '">' + product.name + '</a></h5>' +
                            priceHtml +
                        '</div>' +
                    '</div>' +
                '</div>';
            });
        }
        $('#product-grid').html(html);
    }

    // Load more on scroll (optional)
    $(window).on('scroll', function() {
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 500) {
            // Could implement infinite scroll here
        }
    });
});