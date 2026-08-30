$(document).ready(function() {
    // Admin sidebar toggle
    $('#sidebar-toggle').on('click', function() {
        $('.admin-sidebar').toggleClass('open');
    });

    // Status toggle on products
    $(document).on('change', '.status-toggle', function() {
        var checkbox = $(this);
        var productId = checkbox.data('id');
        var status = checkbox.is(':checked') ? 'active' : 'inactive';

        $.ajax({
            url: 'products-edit.php',
            type: 'POST',
            data: {
                action: 'toggle_status',
                id: productId,
                status: status
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    checkbox.closest('tr').find('.status-text').text(status);
                }
            }
        });
    });

    // Delete product
    $(document).on('click', '.delete-product', function() {
        if (!confirm('Are you sure you want to delete this product?')) return;
        
        var btn = $(this);
        var productId = btn.data('id');

        $.ajax({
            url: 'products.php',
            type: 'POST',
            data: {
                action: 'delete',
                id: productId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    btn.closest('tr').fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            }
        });
    });

    // Order status update
    $(document).on('change', '.order-status-select', function() {
        var select = $(this);
        var orderId = select.data('id');
        var status = select.val();

        $.ajax({
            url: 'orders.php',
            type: 'POST',
            data: {
                action: 'update_status',
                id: orderId,
                status: status
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    select.closest('tr').find('.status-badge')
                        .removeClass('status-pending status-processing status-shipped status-delivered status-cancelled')
                        .addClass('status-' + status)
                        .text(status);
                }
            }
        });
    });

    // View order detail modal
    $(document).on('click', '.view-order', function() {
        var orderId = $(this).data('id');
        
        $.ajax({
            url: 'order-detail.php',
            type: 'GET',
            data: { id: orderId, ajax: 1 },
            success: function(response) {
                $('#orderModal .modal-body').html(response);
                $('#orderModal').modal('show');
            }
        });
    });

    // View customer modal
    $(document).on('click', '.view-customer', function() {
        var userId = $(this).data('id');
        
        $.ajax({
            url: 'customers.php',
            type: 'GET',
            data: { id: userId, ajax: 1 },
            success: function(response) {
                $('#customerModal .modal-body').html(response);
                $('#customerModal').modal('show');
            }
        });
    });

    // Image preview on add product
    $('#main_image').on('change', function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#main-image-preview').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(file);
        }
    });

    $('#hover_image').on('change', function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#hover-image-preview').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(file);
        }
    });
});