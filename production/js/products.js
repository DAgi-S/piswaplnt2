$(document).ready(function() {
    // Initialize Select2 for dropdowns
    $('.select2').select2();

    // Initialize DataTable
    var productsTable = $('#productsTable').DataTable({
        'processing': true,
        'serverSide': true,
        'ajax': {
            'url': 'php_action/fetchProducts.php',
            'type': 'POST',
            'dataType': 'json',
            'error': function(xhr, error, thrown) {
                console.error('DataTables Ajax Error:', error);
                console.error('Server Response:', xhr.responseText);
                alert('Error loading product data. Please check the console for details.');
            }
        },
        'columns': [
            { 'data': 'product_code' },
            { 'data': 'name' },
            { 'data': 'category_name' },
            { 'data': 'brand_name' },
            { 
                'data': 'current_stock',
                'render': function(data, type, row) {
                    let stockClass = parseFloat(data) <= parseFloat(row.min_stock_level) ? 'danger' : 'success';
                    return `<span class="label label-${stockClass}">${data} ${row.unit}</span>`;
                }
            },
            { 
                'data': 'production_cost',
                'render': function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { 
                'data': 'selling_price',
                'render': function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { 
                'data': 'status',
                'render': function(data) {
                    return `<span class="label label-${data === 'active' ? 'success' : 'warning'}">${data.toUpperCase()}</span>`;
                }
            },
            {
                'data': null,
                'orderable': false,
                'searchable': false,
                'render': function(data, type, row) {
                    let currentStatus = row.status.toLowerCase();
                    let newStatus = currentStatus === 'active' ? 'inactive' : 'active';
                    let buttonClass = currentStatus === 'active' ? 'warning' : 'success';
                    let iconClass = currentStatus === 'active' ? 'times' : 'check';
                    
                    return `<div class="btn-group">
                        <button type="button" class="btn btn-sm btn-default btn-edit" data-id="${row.id}">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-${buttonClass}" 
                                onclick="changeStatus(${row.id}, '${newStatus}')">
                            <i class="fa fa-${iconClass}"></i>
                        </button>
                    </div>`;
                }
            }
        ],
        'order': [[1, 'asc']],
        'pageLength': 25,
        'responsive': true,
        'language': {
            'processing': '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>',
            'emptyTable': 'No products found',
            'info': 'Showing _START_ to _END_ of _TOTAL_ products',
            'infoEmpty': 'Showing 0 to 0 of 0 products',
            'infoFiltered': '(filtered from _MAX_ total products)',
            'lengthMenu': '_MENU_ products per page',
            'search': 'Search:',
            'zeroRecords': 'No matching products found'
        }
    });

    // Add Product Button Click Handler
    $(document).on('click', '.add-product-btn, button[data-target="#addProductModal"]', function(e) {
        e.preventDefault();
        // Reset form and messages
        $('#submitProductForm')[0].reset();
        $('#add-product-messages').empty();
        // Show modal
        $('#addProductModal').modal({
            backdrop: 'static',
            keyboard: false
        });
    });

    // Add Product Form Submit Handler
    $('#submitProductForm').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous messages
        $('#add-product-messages').empty();
        
        // Show loading message
        $('#add-product-messages').html('<div class="alert alert-info">' +
            '<i class="fa fa-spinner fa-spin"></i> Adding product...</div>');
        
        // Disable submit button
        $('#createProductBtn').prop('disabled', true);
        
        $.ajax({
            url: 'php_action/createProduct.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('#add-product-messages').html('<div class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-check"></i></strong> ' + response.messages +
                        '</div>');
                    
                    // Reset form
                    $('#submitProductForm')[0].reset();
                    
                    // Reload the products table
                    $('#productsTable').DataTable().ajax.reload();
                    
                    // Close modal after a short delay
                    setTimeout(function() {
                        $('#addProductModal').modal('hide');
                        // Cleanup after modal is hidden
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }, 1500);
                } else {
                    // Show error message
                    $('#add-product-messages').html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-times"></i></strong> ' + response.messages +
                        '</div>');
                    // Re-enable submit button
                    $('#createProductBtn').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Add Product Error:', error);
                console.error('Server Response:', xhr.responseText);
                
                // Show error message
                $('#add-product-messages').html('<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="fa fa-times"></i></strong> An error occurred while adding the product. Please try again.' +
                    '</div>');
                
                // Re-enable submit button
                $('#createProductBtn').prop('disabled', false);
            }
        });
    });

    // Modal cleanup handlers
    $('#addProductModal').on('hidden.bs.modal', function() {
        // Clear form
        $('#submitProductForm')[0].reset();
        // Clear messages
        $('#add-product-messages').empty();
        // Enable submit button
        $('#createProductBtn').prop('disabled', false);
        // Remove any remaining backdrop and cleanup
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    });

    $('#addProductModal').on('show.bs.modal', function() {
        // Clear any previous messages
        $('#add-product-messages').empty();
        // Reset form
        $('#submitProductForm')[0].reset();
        // Enable submit button
        $('#createProductBtn').prop('disabled', false);
    });

    // Edit Product Button Click Handler
    $(document).on('click', '.btn-edit', function() {
        var productId = $(this).data('id');
        
        // Clear previous messages
        $('#edit-product-messages').empty();
        
        // Show loading message
        $('#edit-product-messages').html('<div class="alert alert-info">' +
            '<i class="fa fa-spinner fa-spin"></i> Loading product details...</div>');

        $.ajax({
            url: 'php_action/fetchSingleProduct.php',
            type: 'POST',
            data: { id: productId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    // Clear loading message
                    $('#edit-product-messages').empty();
                    
                    // Set form values
                    $('#productId').val(response.data.id);
                    $('#editProductCode').val(response.data.product_code);
                    $('#editProductName').val(response.data.name);
                    $('#editCategoryId').val(response.data.category_id);
                    $('#editBrandId').val(response.data.brand_id);
                    $('#editUnit').val(response.data.unit);
                    $('#editMinStockLevel').val(response.data.min_stock_level);
                    $('#editProductionCost').val(response.data.production_cost);
                    $('#editSellingPrice').val(response.data.selling_price);
                    $('#editDescription').val(response.data.description);
                    $('#editStatus').val(response.data.status);
                    
                    // Show the modal
                    $('#editProductModal').modal('show');
                } else {
                    $('#edit-product-messages').html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-times"></i></strong> ' + (response.messages || 'Failed to load product details') +
                        '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Edit Product Error:', error);
                $('#edit-product-messages').html('<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="fa fa-times"></i></strong> Failed to load product details. Please try again.' +
                    '</div>');
            }
        });
    });

    // Edit Product Form Submit
    $('#editProductForm').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous messages
        $('#edit-product-messages').empty();
        
        // Show loading message
        $('#edit-product-messages').html('<div class="alert alert-info">' +
            '<i class="fa fa-spinner fa-spin"></i> Updating product...</div>');
        
        // Disable submit button
        $('#editProductBtn').prop('disabled', true);
        
        $.ajax({
            url: 'php_action/editProduct.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('#edit-product-messages').html('<div class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-check"></i></strong> ' + response.messages +
                        '</div>');
                    
                    // Close modal after a short delay
                    setTimeout(function() {
                        $('#editProductModal').modal('hide');
                        // Reload the products table
                        productsTable.ajax.reload(null, false);
                    }, 1500);
                } else {
                    // Show error message
                    $('#edit-product-messages').html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-times"></i></strong> ' + response.messages +
                        '</div>');
                    // Re-enable submit button
                    $('#editProductBtn').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Update Product Error:', error);
                
                // Show error message
                $('#edit-product-messages').html('<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="fa fa-times"></i></strong> Failed to update product. Please try again.' +
                    '</div>');
                
                // Re-enable submit button
                $('#editProductBtn').prop('disabled', false);
            }
        });
    });

    // Modal cleanup
    $('#editProductModal').on('hidden.bs.modal', function() {
        // Clear form
        $('#editProductForm')[0].reset();
        // Clear messages
        $('#edit-product-messages').empty();
        // Enable submit button
        $('#editProductBtn').prop('disabled', false);
        // Remove any remaining backdrop
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    });

    // Stock Movement
    $('#productsTable').on('click', '.btn-stock', function() {
        var productId = $(this).data('id');
        $('#stockInItemId, #stockOutItemId').val(productId);
        $('#stockMovementModal').modal('show');
        
        // Load stock movement history
        $('#productStockMovementTable').DataTable({
            'destroy': true,
            'ajax': {
                'url': 'php_action/fetchProductStockMovements.php',
                'type': 'POST',
                'data': { product_id: productId }
            },
            'columns': [
                { 'data': 'created_at' },
                { 'data': 'movement_type' },
                { 'data': 'quantity' },
                { 'data': 'reference' },
                { 'data': 'notes' },
                { 'data': 'created_by' },
                { 'data': 'current_stock' }
            ],
            'order': [[0, 'desc']]
        });
    });

    // Function to open stock in modal
    window.openStockInModal = function() {
        $('#stockMovementModal').modal('hide');
        $('#addStockInModal').modal('show');
    };

    // Function to open stock out modal
    window.openStockOutModal = function() {
        $('#stockMovementModal').modal('hide');
        $('#addStockOutModal').modal('show');
    };

    // Handle Stock Movement Form Submission
    $('#addStockInForm, #addStockOutForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formMessages = form.find('.messages');
        
        // Clear previous messages
        formMessages.empty();
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Reset form
                    form[0].reset();
                    
                    // Reload tables
                    if ($.fn.DataTable.isDataTable('#productStockMovementTable')) {
                        $('#productStockMovementTable').DataTable().ajax.reload();
                    }
                    if ($.fn.DataTable.isDataTable('#productsTable')) {
                        $('#productsTable').DataTable().ajax.reload(null, false);
                    }
                    
                    // Close modal and clean up properly
                    var $modal = $('#stockMovementModal');
                    $modal.on('hidden.bs.modal', function() {
                        // Cleanup after modal is fully hidden
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        $(this).removeClass('in');
                        // Reset modal state
                        $(this).data('bs.modal', null);
                        // Unbind this specific handler
                        $(this).off('hidden.bs.modal');
                    });
                    
                    // Hide the modal
                    $modal.modal('hide');
                    
                    // Force cleanup after a short delay
                    setTimeout(function() {
                        if ($('.modal-backdrop').length > 0) {
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open').css('padding-right', '');
                            $modal.removeClass('in');
                        }
                    }, 300);
                    
                    // Show success message
                    $('.remove-messages').html('<div class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-check"></i></strong> ' + response.messages +
                        '</div>');
                    
                    // Clear success message after 3 seconds
                    setTimeout(function() {
                        $('.remove-messages').empty();
                    }, 3000);
                } else {
                    formMessages.html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-times"></i></strong> ' + response.messages +
                        '</div>');
                }
            },
            error: function(xhr, status, error) {
                formMessages.html('<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="fa fa-times"></i></strong> An error occurred. Please try again.' +
                    '</div>');
                console.error(error);
            }
        });
    });

    // Global modal cleanup handler
    $(document).ready(function() {
        // Handle any click outside modal
        $(document).on('click', function(e) {
            if ($('.modal-backdrop').length > 0 && !$(e.target).closest('.modal').length) {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
                $('.modal').removeClass('in').modal('hide');
            }
        });

        // Handle ESC key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27 && $('.modal-backdrop').length > 0) { // ESC key
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
                $('.modal').removeClass('in').modal('hide');
            }
        });

        // Cleanup when any modal is hidden
        $('.modal').on('hidden.bs.modal', function() {
            var $body = $('body');
            
            // Remove all modal-related elements and classes
            $('.modal-backdrop').remove();
            $body.removeClass('modal-open').css('padding-right', '');
            $(this).removeClass('in');
            
            // Reset modal state
            $(this).data('bs.modal', null);
            
            // Reset any forms
            $('form', this).trigger('reset');
            
            // Clear any alerts
            $('.alert').remove();
            
            // Additional cleanup
            if ($('.modal-backdrop').length > 0) {
                $('.modal-backdrop').remove();
            }
            
            // Force body cleanup
            if ($body.hasClass('modal-open')) {
                $body.removeClass('modal-open').css('padding-right', '');
            }
        });
    });

    // Activate/Deactivate Product
    $('#productsTable').on('click', '.btn-activate, .btn-deactivate', function() {
        var productId = $(this).data('id');
        var newStatus = $(this).hasClass('btn-activate') ? 'active' : 'inactive';
        
        if (confirm('Are you sure you want to ' + (newStatus === 'active' ? 'activate' : 'deactivate') + ' this product?')) {
            $.ajax({
                url: 'php_action/updateProductStatus.php',
                type: 'POST',
                data: {
                    id: productId,
                    status: newStatus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        productsTable.ajax.reload();
                        $('.remove-messages').html('<div class="alert alert-success">' +
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                            '<strong><i class="fa fa-check"></i></strong> ' + response.messages +
                            '</div>');
                    }
                }
            });
        }
    });

    // Export functionality
    $('#export').on('click', function() {
        window.location = 'php_action/exportProducts.php';
    });

    // Print BOM functionality
    window.printBOM = function() {
        var printContents = document.getElementById('bomTable').outerHTML;
        var originalContents = document.body.innerHTML;
        
        // Create a styled print version
        var printPage = '<html><head><title>Bill of Materials</title>' +
            '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">' +
            '<style>' +
            '@media print {' +
            '   .table { width: 100%; border-collapse: collapse; }' +
            '   .table th, .table td { padding: 8px; border: 1px solid #ddd; }' +
            '   .table th { background-color: #f5f5f5; }' +
            '   .label { border: 1px solid #000; padding: 2px 5px; }' +
            '   .label-success { background-color: #dff0d8; }' +
            '   .label-danger { background-color: #f2dede; }' +
            '   .label-warning { background-color: #fcf8e3; }' +
            '}' +
            '</style></head><body>' +
            '<h3 class="text-center">Bill of Materials</h3>' +
            printContents +
            '</body></html>';

        // Open new window and print
        var printWindow = window.open('', '_blank');
        printWindow.document.write(printPage);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };

    // Preview BOM Invoice
    window.previewBOM = function() {
        var productId = $('#bomTable').data('product-id');
        
        // Show loading message
        $('#bom-messages').html('<div class="alert alert-info">' +
            '<i class="fa fa-spinner fa-spin"></i> Generating invoice preview...</div>');
        
        $.ajax({
            url: 'php_action/generateBOMInvoice.php',
            type: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Clear loading message
                    $('#bom-messages').empty();
                    
                    // Create invoice preview
                    var invoiceHtml = '<div class="invoice-preview">' +
                        '<div class="row">' +
                        '<div class="col-xs-12">' +
                        '<h3>Bill of Materials - Invoice Preview</h3>' +
                        '<hr>' +
                        '<div class="row">' +
                        '<div class="col-xs-6">' +
                        '<strong>Product:</strong> ' + response.data.product_name + '<br>' +
                        '<strong>Product Code:</strong> ' + response.data.product_code + '<br>' +
                        '<strong>Date:</strong> ' + new Date().toLocaleDateString() +
                        '</div>' +
                        '<div class="col-xs-6 text-right">' +
                        '<strong>Total Items:</strong> ' + response.data.items.length + '<br>' +
                        '<strong>Total Cost:</strong> ' + response.data.total_cost.toFixed(2) +
                        '</div>' +
                        '</div>' +
                        '<hr>' +
                        '<table class="table table-bordered">' +
                        '<thead>' +
                        '<tr>' +
                        '<th>Raw Material</th>' +
                        '<th>Quantity</th>' +
                        '<th>Unit</th>' +
                        '<th>Unit Cost</th>' +
                        '<th>Total Cost</th>' +
                        '</tr>' +
                        '</thead>' +
                        '<tbody>';

                    response.data.items.forEach(function(item) {
                        invoiceHtml += '<tr>' +
                            '<td>' + item.raw_material_name + '</td>' +
                            '<td>' + parseFloat(item.quantity).toFixed(2) + '</td>' +
                            '<td>' + item.unit + '</td>' +
                            '<td>' + parseFloat(item.unit_cost).toFixed(2) + '</td>' +
                            '<td>' + parseFloat(item.total_cost).toFixed(2) + '</td>' +
                            '</tr>';
                    });

                    invoiceHtml += '</tbody>' +
                        '<tfoot>' +
                        '<tr>' +
                        '<td colspan="4" class="text-right"><strong>Total Cost:</strong></td>' +
                        '<td><strong>' + response.data.total_cost.toFixed(2) + '</strong></td>' +
                        '</tr>' +
                        '</tfoot>' +
                        '</table>' +
                        '</div>' +
                        '</div>' +
                        '</div>';

                    // Show invoice preview in a new modal
                    var previewModal = $('<div class="modal fade" id="invoicePreviewModal">' +
                        '<div class="modal-dialog modal-lg">' +
                        '<div class="modal-content">' +
                        '<div class="modal-header">' +
                        '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
                        '<h4 class="modal-title">Invoice Preview</h4>' +
                        '</div>' +
                        '<div class="modal-body">' + invoiceHtml + '</div>' +
                        '<div class="modal-footer">' +
                        '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
                        '<button type="button" class="btn btn-primary" onclick="printInvoice()">Print Invoice</button>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</div>');

                    // Remove any existing preview modal
                    $('#invoicePreviewModal').remove();
                    
                    // Add the new modal to the document
                    $('body').append(previewModal);
                    
                    // Show the modal
                    $('#invoicePreviewModal').modal('show');
                } else {
                    $('#bom-messages').html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-times"></i></strong> ' + response.messages +
                        '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Preview Error:', error);
                $('#bom-messages').html('<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="fa fa-times"></i></strong> Failed to generate invoice preview.' +
                    '</div>');
            }
        });
    };

    // Print Invoice
    window.printInvoice = function() {
        var printContents = document.querySelector('.invoice-preview').outerHTML;
        var originalContents = document.body.innerHTML;

        var printPage = '<html><head><title>BOM Invoice</title>' +
            '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">' +
            '<style>' +
            '@media print {' +
            '   .modal-footer, .close { display: none !important; }' +
            '   .invoice-preview { padding: 20px; }' +
            '   .table { width: 100%; border-collapse: collapse; }' +
            '   .table th, .table td { padding: 8px; border: 1px solid #ddd; }' +
            '}' +
            '</style></head><body>' +
            printContents +
            '</body></html>';

        var printWindow = window.open('', '_blank');
        printWindow.document.write(printPage);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };
});

// Change Product Status
function changeStatus(productId, newStatus) {
    if (!productId || !newStatus) {
        console.error('Invalid parameters:', { productId, newStatus });
        return;
    }

    // Ensure newStatus is lowercase
    newStatus = newStatus.toLowerCase();
    
    // Validate status value
    if (!['active', 'inactive'].includes(newStatus)) {
        console.error('Invalid status value:', newStatus);
        return;
    }

    if (confirm('Are you sure you want to change the status to ' + newStatus.toUpperCase() + '?')) {
        // Show loading message
        $('.remove-messages').html('<div class="alert alert-info">' +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
            '<strong><i class="fa fa-spinner fa-spin"></i></strong> Updating status...' +
            '</div>');

        console.log('Sending status change request:', { productId, newStatus });
        
        $.ajax({
            url: 'php_action/changeProductStatus.php',
            type: 'POST',
            data: {
                id: productId,
                status: newStatus
            },
            dataType: 'json',
            beforeSend: function() {
                console.log('Sending request to change status to:', newStatus);
            },
            success: function(response) {
                console.log('Status change response:', response);
                
                if (response.success) {
                    // Show success message
                    $('.remove-messages').html('<div class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-check"></i></strong> ' + response.messages +
                        '</div>');
                    
                    // Reload the products table
                    setTimeout(function() {
                        $('#productsTable').DataTable().ajax.reload(null, false);
                    }, 100);
                } else {
                    // Show error message
                    $('.remove-messages').html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-times"></i></strong> ' + (response.messages || 'Failed to update status') +
                        '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                let errorMessage = 'An error occurred while changing the status';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.messages) {
                        errorMessage = response.messages;
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    errorMessage += ': ' + error;
                }
                
                $('.remove-messages').html('<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="fa fa-times"></i></strong> ' + errorMessage +
                    '</div>');
            }
        });
    }
}

