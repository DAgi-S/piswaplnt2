$(document).ready(function() {
    // Initialize DataTable
    var table = $('#stockMovementsTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "php_action/fetchStockMovements.php",
            "type": "POST",
            "error": function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while loading the data. Please try again.'
                });
            }
        },
        "columns": [
            {"data": "movement_id"},
            {"data": "product_name"},
            {
                "data": "reference_type",
                "render": function(data, type, row) {
                    if (type !== 'display') return data;
                    var classes = {
                        'production_order': 'info',
                        'purchase': 'primary',
                        'sale': 'success',
                        'adjustment': 'warning'
                    };
                    var className = classes[data] || 'default';
                    return '<span class="label label-' + className + '">' + data.replace('_', ' ').toUpperCase() + '</span>';
                }
            },
            {"data": "reference_id"},
            {
                "data": "quantity",
                "render": function(data, type, row) {
                    if (type !== 'display') return data;
                    var color = parseFloat(data) >= 0 ? 'success' : 'danger';
                    return '<span class="text-' + color + '">' + data + '</span>';
                }
            },
            {
                "data": "movement_type",
                "render": function(data, type, row) {
                    if (type !== 'display') return data;
                    var color = data === 'in' ? 'success' : 'danger';
                    return '<span class="label label-' + color + '">' + data.toUpperCase() + '</span>';
                }
            },
            {"data": "notes"},
            {
                "data": "created_at",
                "render": function(data, type, row) {
                    if (type !== 'display') return data;
                    return moment(data).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            {"data": "created_by"}
        ],
        "order": [[7, "desc"]],
        "pageLength": 25,
        "dom": '<"row"<"col-sm-4"l><"col-sm-4 text-center"B><"col-sm-4"f>>rtip',
        "buttons": [
            {
                extend: 'collection',
                text: '<i class="fa fa-download"></i> Export',
                buttons: [
                    'copy',
                    'excel',
                    'csv',
                    'pdf',
                    'print'
                ]
            }
        ],
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });

    // Handle Stock In Form Submit
    $('#addStockInForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = form.serialize();
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addStockInModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages
                    });
                    table.ajax.reload();
                    form[0].reset();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing your request. Please try again.'
                });
            }
        });
    });

    // Handle Stock Out Form Submit
    $('#addStockOutForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = form.serialize();
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addStockOutModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages
                    });
                    table.ajax.reload();
                    form[0].reset();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing your request. Please try again.'
                });
            }
        });
    });

    // Check Available Stock on Product Selection for Stock Out
    $('#productOut').on('change', function() {
        var productId = $(this).val();
        if(productId) {
            $.ajax({
                url: 'php_action/getProductStock.php',
                type: 'POST',
                data: {product_id: productId},
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        $('.available-stock').text('Available Stock: ' + response.stock);
                    } else {
                        $('.available-stock').text('Error getting stock information');
                    }
                },
                error: function() {
                    $('.available-stock').text('Error getting stock information');
                }
            });
        } else {
            $('.available-stock').text('');
        }
    });

    // Reset forms when modals are closed
    $('#addStockInModal, #addStockOutModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('.available-stock').text('');
    });
}); 