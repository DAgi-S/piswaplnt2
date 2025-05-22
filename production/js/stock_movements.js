$(document).ready(function() {
    var manageStockMovementsTable = $('#manageStockMovementsTable').DataTable({
        'ajax': 'php_action/fetchRawMaterialMovements.php',
        'order': [],
        'dom': 'Bfrtip',
        'buttons': [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
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
                    var color = row.movement_type === 'OUT' ? 'text-danger' : 'text-success';
                    var prefix = row.movement_type === 'OUT' ? '-' : '+';
                    return '<span class="' + color + '">' + prefix + data + '</span>';
                }
            },
            { 
                'data': 'movement_type',
                'render': function(data) {
                    var color = data === 'OUT' ? 'text-danger' : 'text-success';
                    return '<span class="' + color + '">' + data + '</span>';
                }
            },
            { 'data': 'reference_type' },
            { 'data': 'reference_id' },
            { 'data': 'notes' },
            { 'data': 'created_by' },
            { 'data': 'current_stock' }
        ]
    });

    // Initialize DataTable
    var stockMovementsTable = $('#stockMovementsTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchStockMovements.php',
            'type': 'POST',
            'dataSrc': 'data',
            'error': function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                toastr.error('Error loading data. Please try again.');
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
                'render': function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { 
                'data': 'movement_type',
                'render': function(data) {
                    return data === 'in' || data === 'IN' ? 
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
                'render': function(data) {
                    var stock = parseFloat(data);
                    var colorClass = stock <= 0 ? 'text-danger' : 'text-success';
                    return '<span class="' + colorClass + '">' + data + '</span>';
                }
            },
            {
                'data': null,
                'orderable': false,
                'searchable': false,
                'render': function(data) {
                    var buttons = '<div class="btn-group">';
                    buttons += '<button class="btn btn-info btn-sm view-btn" data-id="' + data.id + '"><i class="fa fa-eye"></i></button>';
                    buttons += '</div>';
                    return buttons;
                }
            }
        ],
        'processing': true,
        'serverSide': false,
        'pageLength': 25,
        'responsive': true,
        'dom': '<"row"<"col-sm-6"B><"col-sm-6"f>>rt<"row"<"col-sm-6"i><"col-sm-6"p>>',
        'buttons': [
            { extend: 'copy', className: 'btn-sm' },
            { extend: 'csv', className: 'btn-sm' },
            { extend: 'excel', className: 'btn-sm' },
            { extend: 'pdf', className: 'btn-sm' },
            { extend: 'print', className: 'btn-sm' }
        ],
        'language': {
            'search': '_INPUT_',
            'searchPlaceholder': 'Search...',
            'processing': '<i class="fa fa-spinner fa-spin fa-fw"></i> Loading...',
            'emptyTable': 'No stock movements found',
            'zeroRecords': 'No matching stock movements found'
        }
    });

    // Initialize Select2 for item selection
    function initializeSelect2() {
        $('.select-item').each(function() {
            var $select = $(this);
            var $form = $select.closest('form');
            var $currentStock = $form.find('#currentStock');
            var isStockOut = $form.attr('id') === 'createStockOutForm';

            // Initialize Select2
            $select.select2({
                theme: 'bootstrap',
                width: '100%',
                placeholder: 'Select an item',
                allowClear: true,
                minimumResultsForSearch: 10,
                ajax: {
                    url: 'php_action/fetchItems.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            type: $form.find('[name="item_type"]').val()
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                templateResult: formatItemOption,
                templateSelection: formatItemSelection
            });

            // Load items when item type changes
            $form.find('[name="item_type"]').on('change', function() {
                var type = $(this).val();
                
                // Clear current selection
                $select.empty().trigger('change');
                $currentStock.html('<strong>Current Stock: </strong>0');
                
                if (!type) return;

                // Trigger Ajax load
                $select.select2('open');
                $select.select2('close');
            });

            // Handle item selection
            $select.on('select2:select', function(e) {
                var data = e.params.data;
                if (data && data.current_stock !== undefined) {
                    var stockText = parseFloat(data.current_stock).toFixed(2) + ' ' + (data.unit || '');
                    var stockClass = parseFloat(data.current_stock) <= 0 ? 'text-danger' : 'text-success';
                    
                    $currentStock.html(
                        '<strong>Current Stock: </strong>' +
                        '<span class="' + stockClass + '">' + stockText + '</span>'
                    );
                    
                    if (isStockOut) {
                        $form.find('[name="quantity"]')
                            .attr('max', data.current_stock)
                            .attr('data-current-stock', data.current_stock);
                    }
                }
            }).on('select2:clear', function() {
                $currentStock.html('<strong>Current Stock: </strong>0');
                if (isStockOut) {
                    $form.find('[name="quantity"]')
                        .removeAttr('max')
                        .removeAttr('data-current-stock');
                }
            });
        });
    }

    // Format the option in the dropdown
    function formatItemOption(item) {
        if (!item.id || item.disabled) return item.text;
        
        var stockClass = parseFloat(item.current_stock) <= 0 ? 'text-danger' : 'text-success';
        
        return $('<div class="select2-result-item">' +
            '<div class="select2-result-item__code">' + item.code + '</div>' +
            '<div class="select2-result-item__name">' + item.name + '</div>' +
            '<div class="select2-result-item__stock ' + stockClass + '">' +
                '<strong>Current Stock:</strong> ' + parseFloat(item.current_stock).toFixed(2) + 
                ' ' + (item.unit || '') +
            '</div>' +
        '</div>');
    }

    // Format the selected item
    function formatItemSelection(item) {
        if (!item.id || item.disabled) return item.text;
        return item.code + ' - ' + item.name + ' (' + parseFloat(item.current_stock).toFixed(2) + ' ' + (item.unit || '') + ')';
    }

    // Handle item type change
    $('select[name="item_type"]').on('change', function() {
        var $form = $(this).closest('form');
        var $select = $form.find('.select-item');
        
        // Clear the select and trigger change
        $select.val(null).trigger('change');
        
        // Reset current stock display
        $form.find('#currentStock').text('0');
        
        // Reset quantity field
        var form = $(this).closest('form');
        form.find('.select-item').val(null).trigger('change');
        form.find('#currentStock').text('0');
    });

    // Initialize forms
    function initializeForms() {
        initializeSelect2();

        // Stock In Form Submit
        $('#createStockInForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            
            if (!validateStockForm(form)) {
                return;
            }
            
            $.ajax({
                url: 'php_action/createStockMovement.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#addStockInModal').modal('hide');
                        stockMovementsTable.ajax.reload();
                        toastr.success('Stock added successfully');
                        form[0].reset();
                    } else {
                        toastr.error(response.message || 'Error adding stock');
                    }
                },
                error: function() {
                    toastr.error('An error occurred while processing your request');
                }
            });
        });

        // Stock Out Form Submit
        $('#createStockOutForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            
            if (!validateStockForm(form)) {
                return;
            }
            
            $.ajax({
                url: 'php_action/createStockMovement.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#addStockOutModal').modal('hide');
                        stockMovementsTable.ajax.reload();
                        toastr.success('Stock removed successfully');
                        form[0].reset();
                    } else {
                        toastr.error(response.message || 'Error removing stock');
                    }
                },
                error: function() {
                    toastr.error('An error occurred while processing your request');
                }
            });
        });

        // View Movement
        $('#stockMovementsTable').on('click', '.view-btn', function() {
            var id = $(this).data('id');
            $.ajax({
                url: 'php_action/getStockMovement.php',
                type: 'POST',
                data: { movement_id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#viewMovementModal .modal-body').html(response.html);
                        $('#viewMovementModal').modal('show');
                    } else {
                        toastr.error(response.messages);
                    }
                },
                error: function() {
                    toastr.error('An error occurred while fetching movement details');
                }
            });
        });

        // Edit Movement
        $('#stockMovementsTable').on('click', '.edit-btn', function() {
            var id = $(this).data('id');
            $.ajax({
                url: 'php_action/fetchSingleStockMovement.php',
                type: 'POST',
                data: { movement_id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var form = $('#editMovementForm');
                        form.find('[name="movement_id"]').val(response.data.movement_id);
                        form.find('[name="item_id"]').val(response.data.item_id);
                        form.find('[name="reference_type"]').val(response.data.reference_type);
                        form.find('[name="reference_id"]').val(response.data.reference_id);
                        form.find('[name="quantity"]').val(response.data.quantity);
                        form.find('[name="notes"]').val(response.data.notes);
                        
                        // Initialize select2 with pre-selected value
                        var itemSelect = form.find('.select-item');
                        var option = new Option(response.data.item_name, response.data.item_id, true, true);
                        itemSelect.append(option).trigger('change');
                        
                        $('#editMovementModal').modal('show');
                    } else {
                        toastr.error(response.messages);
                    }
                },
                error: function() {
                    toastr.error('An error occurred while fetching movement details');
                }
            });
        });

        // Edit Movement Form Submit
        $('#editMovementForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            
            $.ajax({
                url: 'php_action/editStockMovement.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#editMovementModal').modal('hide');
                        stockMovementsTable.ajax.reload();
                        toastr.success('Movement updated successfully');
                    } else {
                        toastr.error(response.messages);
                    }
                },
                error: function() {
                    toastr.error('An error occurred while updating the movement');
                }
            });
        });

        // Delete Movement
        $('#stockMovementsTable').on('click', '.delete-btn', function() {
            var id = $(this).data('id');
            if (confirm('Are you sure you want to delete this movement?')) {
                $.ajax({
                    url: 'php_action/deleteStockMovement.php',
                    type: 'POST',
                    data: { movement_id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            stockMovementsTable.ajax.reload();
                            toastr.success('Movement deleted successfully');
                        } else {
                            toastr.error(response.messages);
                        }
                    },
                    error: function() {
                        toastr.error('An error occurred while deleting the movement');
                    }
                });
            }
        });
    }

    // Validate stock form
    function validateStockForm(form) {
        var itemId = form.find('.select-item').val();
        var quantity = parseFloat(form.find('[name="quantity"]').val());
        var referenceId = form.find('[name="reference_id"]').val();
        
        if (!itemId) {
            toastr.error('Please select an item');
            return false;
        }
        
        if (!quantity || quantity <= 0) {
            toastr.error('Please enter a valid quantity');
            return false;
        }
        
        if (form.attr('id') === 'createStockOutForm') {
            var currentStock = parseFloat(form.find('#currentStock').text());
            if (quantity > currentStock) {
                toastr.error('Quantity cannot be greater than current stock');
                return false;
            }
        }
        
        if (!referenceId) {
            toastr.error('Please enter a reference ID');
            return false;
        }
        
        return true;
    }

    // Clear form on modal close
    $('.modal').on('hidden.bs.modal', function() {
        var form = $(this).find('form');
        if (form.length) {
            form[0].reset();
            form.find('.select-item').val(null).trigger('change');
            form.find('#currentStock').text('0');
        }
    });

    // Initialize everything when document is ready
    initializeForms();
});