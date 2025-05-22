$(document).ready(function() {
    // Initialize DataTable
    var stockMovementsTable = $('#manageStockMovementsTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchStockMovements.php',
            'type': 'POST',
            'dataSrc': function(json) {
                if (json.error) {
                    console.error('Server Error:', json.error);
                    return [];
                }
                return json.data || [];
            },
            'error': function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                console.error('Server response:', xhr.responseText);
            }
        },
        'order': [[0, 'desc']],
        'columns': [
            { 
                'data': 'created_at',
                'render': function(data) {
                    return moment(data).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            { 'data': 'item_name' },
            { 
                'data': 'quantity',
                'render': function(data, type, row) {
                    var quantity = parseFloat(data);
                    var unit = row.unit ? ' ' + row.unit : '';
                    return quantity.toFixed(2) + unit;
                }
            },
            { 
                'data': 'movement_type',
                'render': function(data) {
                    return data.toLowerCase() === 'in' ? 
                        '<span class="label label-success">IN</span>' : 
                        '<span class="label label-danger">OUT</span>';
                }
            },
            { 'data': 'reference_type' },
            { 'data': 'reference_id' },
            { 'data': 'notes' },
            { 'data': 'created_by' },
            { 
                'data': 'current_stock',
                'render': function(data, type, row) {
                    var stock = parseFloat(data);
                    var unit = row.unit ? ' ' + row.unit : '';
                    var colorClass = stock <= 0 ? 'text-danger' : 'text-success';
                    return '<span class="' + colorClass + '">' + stock.toFixed(2) + unit + '</span>';
                }
            }
        ],
        'pageLength': 25,
        'processing': true,
        'serverSide': false,
        'dom': 'Bfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print'],
        'responsive': true,
        'language': {
            'emptyTable': 'No stock movements found',
            'zeroRecords': 'No matching stock movements found',
            'loadingRecords': 'Loading stock movements...'
        }
    });

    // Initialize Select2 for item selection
    $('.select-item').each(function() {
        var $select = $(this);
        var $form = $select.closest('form');
        var $currentStock = $form.find('#currentStock');
        var isStockOut = $form.attr('id') === 'createStockOutForm';

        $select.select2({
            theme: 'bootstrap',
            width: '100%',
            placeholder: 'Select an item',
            allowClear: true,
            ajax: {
                url: 'php_action/fetchItems.php',
                type: 'GET',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    var warehouseId = isStockOut ? $form.find('[name="source_id"]').val() : null;
                    return {
                        search: params.term,
                        warehouse_id: warehouseId
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            templateResult: formatItem,
            templateSelection: formatItemSelection
        });

        // Handle warehouse change for stock out
        if (isStockOut) {
            $form.find('[name="source_id"]').on('change', function() {
                $select.val(null).trigger('change');
                $currentStock.html('<strong>Current Stock: </strong>0');
            });
        }

        // Handle item type change
        $form.find('[name="item_type"]').on('change', function() {
            $select.val(null).trigger('change');
            $currentStock.html('<strong>Current Stock: </strong>0');
        });

        // Handle item selection
        $select.on('select2:select', function(e) {
            var data = e.params.data;
            if (data) {
                var stockValue = isStockOut ? data.warehouse_stock : data.total_stock;
                var stockText = parseFloat(stockValue).toFixed(2);
                var unit = data.unit ? ' ' + data.unit : '';
                var stockClass = parseFloat(stockValue) <= 0 ? 'text-danger' : 'text-success';
                $currentStock.html('<strong>Current Stock: </strong><span class="' + stockClass + '">' + stockText + unit + '</span>');
                
                // Update max quantity for stock out
                if (isStockOut) {
                    var quantityInput = $form.find('[name="quantity"]');
                    quantityInput.attr('max', stockValue);
                    if (parseFloat(quantityInput.val()) > stockValue) {
                        quantityInput.val('');
                    }
                }
            }
        });
    });

    // Handle form submissions
    $('#createStockInForm, #createStockOutForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        
        // Disable submit button
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: 'php_action/createStockMovementHandler.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Close modal
                    form.closest('.modal').modal('hide');
                    
                    // Reset form
                    form[0].reset();
                    form.find('.select-item').val(null).trigger('change');
                    form.find('#currentStock').html('<strong>Current Stock: </strong>0');
                    
                    // Reload table
                    stockMovementsTable.ajax.reload();
                    
                    // Show success message
                    toastr.success(response.messages);
                } else {
                    toastr.error(response.messages || 'Error occurred while processing the request.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Server response:', xhr.responseText);
                toastr.error('Error processing request. Please try again.');
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false);
            }
        });
    });

    // Add quantity validation on input
    $('#createStockOutForm [name="quantity"]').on('input', function() {
        var quantity = parseFloat($(this).val()) || 0;
        var currentStock = parseFloat($('#currentStock').text().match(/[\d.]+/)[0]) || 0;
        
        if (quantity > currentStock) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Quantity cannot exceed available stock in selected warehouse (' + currentStock + ')</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });
});

// Format item in dropdown
function formatItem(item) {
    if (!item.id) return item.text;
    var stockValue = item.warehouse_stock !== undefined ? item.warehouse_stock : item.total_stock;
    var stockText = parseFloat(stockValue).toFixed(2);
    var unit = item.unit ? ' ' + item.unit : '';
    var stockClass = parseFloat(stockValue) <= 0 ? 'text-danger' : 'text-success';
    return $('<div class="select2-result-item">' +
        '<div class="select2-result-item__code">' + item.code + '</div>' +
        '<div class="select2-result-item__name">' + item.name + '</div>' +
        '<div class="select2-result-item__stock ' + stockClass + '">' +
        '<strong>Stock:</strong> ' + stockText + unit +
        '</div>' +
    '</div>');
}

// Format selected item
function formatItemSelection(item) {
    if (!item.id) return item.text;
    return item.code + ' - ' + item.name;
} 