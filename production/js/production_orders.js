$(document).ready(function() {
    // Initialize DataTable with proper error handling
    let productionOrdersTable;
    
    // Check if DataTable is already initialized and destroy it if necessary
    if ($.fn.dataTable.isDataTable('#productionOrdersTable')) {
        $('#productionOrdersTable').DataTable().destroy();
    }
    
    productionOrdersTable = $('#productionOrdersTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchProductionOrders.php',
            'type': 'POST',
            'dataSrc': function(json) {
                if (!json.data) {
                    console.error('Invalid response format:', json);
                    toastr.error('Error: Invalid server response format');
                    return [];
                }
                return json.data;
            },
            'error': function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                console.error('Server response:', xhr.responseText);
                toastr.error('Error loading production orders: ' + error);
            }
        },
        'processing': true,
        'serverSide': false,
        'order': [[0, 'desc']],
        'columns': [
            { data: 'order_number' },
            { data: 'product_name' },
            { 
                data: 'status',
                render: function(data) {
                    return StatusHandler.getStatusBadge(data);
                }
            },
            { data: 'target_quantity' },
            { data: 'completed_quantity' },
            { data: 'start_date' },
            { data: 'expected_completion_date' },
            { data: 'created_by' },
            {
                data: null,
                render: function(data, type, row) {
                    return StatusHandler.getActionButtons(row.id, row.status);
                }
            }
        ],
        'pageLength': 10,
        'responsive': true,
        'dom': "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        'language': {
            'processing': '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>',
            'emptyTable': 'No production orders available',
            'zeroRecords': 'No matching records found'
        },
        'destroy': true // Add destroy parameter to handle reinitialization
    });

    // Initialize Select2 for product selection
    $('#product_id').select2({
        placeholder: 'Select Product',
        allowClear: true
    }).on('change', function() {
        // Clear materials table when product changes
        $('#materialsTable tbody').empty();
    });

    // Handle status change events with debouncing
    let statusChangeTimeout;
    $(document).on('click', '.change-status', function(e) {
        e.preventDefault();
        clearTimeout(statusChangeTimeout);
        
        const $btn = $(this);
        const orderId = $btn.data('id');
        const newStatus = $btn.data('status');
        
        // Disable the button to prevent double clicks
        $btn.prop('disabled', true);
        
        statusChangeTimeout = setTimeout(() => {
            StatusHandler.changeStatus(orderId, newStatus)
                .then((result) => {
                    // Only re-enable the button if the operation wasn't successful
                    // This prevents clicking again after a successful status change
                    if (!result.success) {
                        $btn.prop('disabled', false);
                    }
                })
                .catch(() => {
                    // Re-enable the button if there was an unexpected error
                    $btn.prop('disabled', false);
                });
        }, 300);
    });

    // Clean up on page unload
    $(window).on('unload', function() {
        if (productionOrdersTable) {
            productionOrdersTable.destroy();
        }
    });

    // Initialize Select2 for material selection
    function initializeMaterialSelect(element) {
        $(element).select2({
            placeholder: 'Select Raw Material',
            allowClear: true,
            ajax: {
                url: 'php_action/fetchRawMaterialsForSelect.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.material_code + ' - ' + item.name,
                                stock: item.current_stock,
                                unit: item.unit
                            };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        }).on('select2:select', function(e) {
            var data = e.params.data;
            $(this).closest('tr').find('.available-stock').text(
                parseFloat(data.stock).toFixed(2) + ' ' + data.unit
            );
        });
    }

    // Initialize existing material selects
    $('.material-select').each(function() {
        initializeMaterialSelect(this);
    });

    // Add Material Row
    $('#addMaterialRow').on('click', function() {
        var newRow = `
            <tr>
                <td>
                    <select class="form-control material-select" name="materials[]" required>
                        <option value="">Select Raw Material</option>
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control required-quantity" name="quantities[]" step="0.01" min="0.01" required />
                </td>
                <td class="available-stock">0</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-material">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>`;
        
        var $newRow = $(newRow);
        $('#productMaterialsTable tbody').append($newRow);
        
        // Initialize Select2 for the new row
        initializeMaterialSelect($newRow.find('.material-select'));
    });

    // Remove Material Row
    $('#productMaterialsTable').on('click', '.remove-material', function() {
        var tbody = $(this).closest('tbody');
        if(tbody.find('tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            toastr.warning('At least one material is required');
        }
    });

    // Form submission
    $('#submitProductionOrderForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        var isValid = true;
        var $form = $(this);
        
        // Check required fields
        $form.find('[required]').each(function() {
            if(!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
        
        // Specifically validate target quantity
        const targetQuantity = parseFloat($('#targetQuantity').val());
        if(isNaN(targetQuantity) || targetQuantity <= 0) {
            isValid = false;
            $('#targetQuantity').addClass('error');
            toastr.error('Target quantity must be greater than zero');
            return false;
        } else {
            $('#targetQuantity').removeClass('error');
        }
        
        if(!isValid) {
            toastr.error('Please fill in all required fields');
            return false;
        }
        
        // Show loading state
        $('#createProductionOrderBtn')
            .prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> Creating...');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Show success message
                    toastr.success(response.messages);
                    
                    // Reset the form
                    $form[0].reset();
                    
                    // Close the modal
                    $('#addProductionOrderModal').modal('hide');
                    
                    // Reload DataTable
                    productionOrdersTable.ajax.reload();
                } else {
                    toastr.error(response.messages);
                }
            },
            error: function(xhr, status, error) {
                toastr.error('An error occurred while creating the production order');
                console.error('Error:', error);
            },
            complete: function() {
                // Reset button state
                $('#createProductionOrderBtn')
                    .prop('disabled', false)
                    .html('Create Order');
            }
        });
    });

    // Make functions globally available
    window.submitProgress = ProductionOrder.submitProgress;
    window.viewProductionOrder = ProductionOrder.view;
    window.updateProgress = ProductionOrder.updateProgress;
}); 