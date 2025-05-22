// Global variable for the DataTable
var manageProductTable;

$(document).ready(function() {
    // Add nav active class
    $('#navProduct').addClass('active');

    // Initialize DataTable
    initializeProductTable();

    // Initialize Bootstrap dropdown
    $(document).on('click', '.dropdown-toggle', function() {
        $(this).closest('.btn-group').toggleClass('open');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.btn-group').length) {
            $('.btn-group').removeClass('open');
        }
    });

    // Add Product Form Submit
    $("#submitProductForm").on('submit', handleAddProduct);

    // Edit Product Form Submit
    $("#editProductForm").on('submit', handleEditProduct);

    // Event delegation for action buttons
    $(document).on('click', '.edit-product', function() {
        var productId = $(this).data('id');
        editProduct(productId);
    });

    $(document).on('click', '.remove-product', function() {
        var productId = $(this).data('id');
        removeProduct(productId);
    });

    $(document).on('click', '.view-history', function() {
        var productId = $(this).data('id');
        viewPurchaseHistory(productId);
    });
});

function initializeProductTable() {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#manageProductTable')) {
        $('#manageProductTable').DataTable().destroy();
    }

    // Initialize DataTable
    manageProductTable = $('#manageProductTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchProducts.php',
            'type': 'POST',
            'dataSrc': function(response) {
                if (response.error) {
                    console.error('Server error:', response.message);
                    $('.remove-messages').html(
                        '<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.message +
                        '</div>'
                    );
                    return [];
                }
                return response.data || [];
            }
        },
        'columns': [
            {
                'data': 'product_image',
                'render': function(data, type, row) {
                    if(data && data != '') {
                        return '<img src="' + data + '" class="img-rounded" width="50" height="50" />';
                    }
                    return '<img src="assests/images/photo_default.png" class="img-rounded" width="50" height="50" />';
                },
                'orderable': false,
                'className': 'text-center'
            },
            {'data': 'name'},
            {
                'data': 'selling_price',
                'render': function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            {'data': 'current_stock'},
            {'data': 'total_purchased'},
            {
                'data': 'brand_name',
                'render': function(data) {
                    return data || 'Unassigned';
                }
            },
            {
                'data': 'category_name',
                'render': function(data) {
                    return data || 'Unassigned';
                }
            },
            {
                'data': 'status',
                'render': function(data) {
                    if (data === 'active' || data === '1') {
                        return '<span class="label label-success">Available</span>';
                    } else if (data === 'inactive' || data === '0') {
                        return '<span class="label label-danger">Not Available</span>';
                    } else {
                        return '<span class="label label-default">Unknown</span>';
                    }
                }
            },
            {
                'data': 'product_id',
                'render': function(data, type, row) {
                    return '<div class="btn-group">' +
                        '<button class="btn btn-sm btn-primary edit-product" data-id="' + data + '">' +
                        '<i class="glyphicon glyphicon-edit"></i>' +
                        '</button> ' +
                        '<button class="btn btn-sm btn-danger remove-product" data-id="' + data + '">' +
                        '<i class="glyphicon glyphicon-trash"></i>' +
                        '</button> ' +
                        '<button class="btn btn-sm btn-info view-history" data-id="' + data + '">' +
                        '<i class="glyphicon glyphicon-time"></i>' +
                        '</button>' +
                        '</div>';
                },
                'orderable': false
            }
        ],
        'order': [[1, 'asc']],
        'pageLength': 10,
        'responsive': true,
        'dom': 'Bfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print']
    });
}

function handleAddProduct(e) {
    e.preventDefault();
    var form = $(this);
    var formData = new FormData(this);

    $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: formData,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        success: function(response) {
            if(response.success) {
                $("#submitProductForm")[0].reset();
                $("#addProductModal").modal('hide');
                manageProductTable.ajax.reload(null, false);
                $('.remove-messages').html('<div class="alert alert-success">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                    '</div>');
            } else {
                $("#add-product-messages").html('<div class="alert alert-warning">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
                    '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error(xhr.responseText);
            $("#add-product-messages").html('<div class="alert alert-danger">'+
                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> An error occurred while adding the product.'+
                '</div>');
        }
    });
}

function handleEditProduct(e) {
    e.preventDefault();
    
    // Show loading indicator
    $('#edit-product-messages').html(
        '<div class="alert alert-info">'+
        '<i class="glyphicon glyphicon-refresh"></i> Updating product...'+
        '</div>'
    );
    
    var formData = new FormData(this);
    
    $.ajax({
        url: $(this).attr('action'),
        type: $(this).attr('method'),
        data: formData,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        success: function(response) {
            // Clear loading message
            $('#edit-product-messages').empty();
            
            if(response.success) {
                $('#editProductModal').modal('hide');
                manageProductTable.ajax.reload(null, false);
                $('.remove-messages').html(
                    '<div class="alert alert-success">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ 
                    response.messages +
                    '</div>'
                );
            } else {
                $('#edit-product-messages').html(
                    '<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ 
                    (response.messages || 'Error updating product') +
                    '</div>'
                );
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            console.error('Response:', xhr.responseText);
            
            // Clear loading message
            $('#edit-product-messages').empty();
            
            $('#edit-product-messages').html(
                '<div class="alert alert-danger">'+
                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+
                'Error updating product. Please try again.'+
                '</div>'
            );
        }
    });
}

function viewPurchaseHistory(productId) {
    if(productId) {
        // Initialize DataTable for purchase history
        if($.fn.DataTable.isDataTable('#purchaseHistoryTable')) {
            $('#purchaseHistoryTable').DataTable().destroy();
        }

        $('#purchaseHistoryTable').DataTable({
            "ajax": {
                "url": "php_action/fetchProductPurchaseHistory.php",
                "type": "POST",
                "data": {productId: productId},
                "error": function(xhr, error, thrown) {
                    console.error('DataTables error:', error);
                    console.error('Server response:', xhr.responseText);
                    $('#purchaseHistoryTable tbody').html(
                        '<tr><td colspan="6" class="text-center text-danger">' +
                        '<i class="glyphicon glyphicon-warning-sign"></i> ' +
                        'Error loading purchase history. Please try again.' +
                        '</td></tr>'
                    );
                }
            },
            "columns": [
                {"data": "purchase_date"},
                {"data": "purchase_number"},
                {"data": "supplier_name"},
                {
                    "data": "quantity",
                    "className": "text-right"
                },
                {
                    "data": "rate",
                    "className": "text-right"
                },
                {
                    "data": "total",
                    "className": "text-right"
                }
            ],
            "order": [[0, "desc"]],
            "pageLength": 10,
            "responsive": true,
            "dom": 'Bfrtip',
            "buttons": ['copy', 'csv', 'excel', 'pdf', 'print']
        });

        // Show modal
        $('#purchaseHistoryModal').modal('show');
    }
}

function editProduct(productId) {
    if(productId) {
        // Clear previous messages
        $('#edit-product-messages').empty();
        
        // Show loading indicator
        $('#edit-product-messages').html(
            '<div class="alert alert-info">'+
            '<i class="glyphicon glyphicon-refresh"></i> Loading product data...'+
            '</div>'
        );
        
        $.ajax({
            url: 'php_action/fetchSelectedProduct.php',
            type: 'post',
            data: {productId: productId},
            dataType: 'json',
            success: function(response) {
                console.log('Edit product response:', response);
                
                // Clear loading message
                $('#edit-product-messages').empty();
                
                if(response.success) {
                    // Populate the form fields
                    $("#productId").val(response.product_id);
                    $("#editProductName").val(response.name);
                    $("#editPrice").val(response.selling_price);
                    $("#editQuantity").val(response.current_stock);
                    $("#editBrandName").val(response.brand_id);
                    $("#editCategoryName").val(response.category_id);
                    $("#editProductStatus").val(response.status === 'active' ? '1' : '0');
                    
                    // Show the modal
                    $("#editProductModal").modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                } else {
                    // Show error message
                    $('#edit-product-messages').html(
                        '<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ 
                        (response.messages || 'Error fetching product data') +
                        '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
                
                // Clear loading message
                $('#edit-product-messages').empty();
                
                try {
                    var response = JSON.parse(xhr.responseText);
                    var message = response.messages || 'Error fetching product data';
                    $('#edit-product-messages').html(
                        '<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ 
                        message +
                        '</div>'
                    );
                } catch(e) {
                    $('#edit-product-messages').html(
                        '<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+
                        'Error fetching product data. Please try again.'+
                        '</div>'
                    );
                }
            }
        });
    }
}

function removeProduct(productId) {
    if(productId) {
        // Show confirmation dialog
        $('#removeProductModal').modal({
            backdrop: 'static',
            keyboard: false
        });
        
        // Handle remove button click
        $("#removeProductBtn").off('click').on('click', function() {
            $.ajax({
                url: 'php_action/removeProduct.php',
                type: 'post',
                data: {productId: productId},
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        // Hide modal
                        $('#removeProductModal').modal('hide');
                        
                        // Reload table
                        manageProductTable.ajax.reload(null, false);
                        
                        // Show success message
                        $('.remove-messages').html(
                            '<div class="alert alert-success">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ 
                            response.messages +
                            '</div>'
                        );
                    } else {
                        // Show error message in modal
                        $('.removeProductMessages').html(
                            '<div class="alert alert-danger">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ 
                            response.messages +
                            '</div>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);
                    
                    // Show error message in modal
                    $('.removeProductMessages').html(
                        '<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+
                        'Error removing product. Please try again.'+
                        '</div>'
                    );
                }
            });
        });
    }
} 