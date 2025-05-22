$(document).ready(function() {
    // Get production order ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const productionOrderId = urlParams.get('id');

    // Load production order details
    loadProductionOrderDetails();

    // Initialize Materials DataTable with enhanced features
    const materialsTable = $('#materialsTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchProductionMaterials.php',
            'type': 'POST',
            'data': function(d) {
                d.productionOrderId = productionOrderId;
            }
        },
        'order': [],
        'dom': 'Bfrtip',  // Add export buttons
        'buttons': [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        'pageLength': 10,
        'lengthMenu': [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        'columns': [
            { data: 'material_code' },
            { data: 'name' },
            { 
                data: 'required_quantity',
                render: function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { 
                data: 'consumed_quantity',
                render: function(data, type, row) {
                    let percentage = (parseFloat(data) / parseFloat(row.required_quantity) * 100).toFixed(1);
                    return parseFloat(data).toFixed(2) + ' <div class="progress" style="margin-bottom: 0;">' +
                           '<div class="progress-bar" role="progressbar" style="width: ' + percentage + '%;">' +
                           percentage + '%</div></div>';
                }
            },
            { 
                data: 'available_stock',
                render: function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { data: 'unit' },
            { 
                data: 'status',
                render: function(data) {
                    let labelClass = '';
                    switch(data) {
                        case 'pending':
                            labelClass = 'label-default';
                            break;
                        case 'partially_consumed':
                            labelClass = 'label-warning';
                            break;
                        case 'fully_consumed':
                            labelClass = 'label-success';
                            break;
                    }
                    return '<span class="label ' + labelClass + '">' + 
                           data.replace('_', ' ').toUpperCase() + '</span>';
                }
            },
            {
                data: null,
                render: function(data) {
                    let buttons = '<div class="btn-group">';
                    buttons += '<button type="button" class="btn btn-default dropdown-toggle" ' +
                              'data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                              'Action <span class="caret"></span></button>';
                    buttons += '<ul class="dropdown-menu dropdown-menu-right">';
                    if(data.status !== 'fully_consumed') {
                        buttons += '<li><a href="#" onclick="consumeMaterial(' + data.id + 
                                 ', \'' + data.name + '\', ' + data.available_stock + 
                                 ')"><i class="fa fa-cube"></i> Consume</a></li>';
                    }
                    buttons += '<li><a href="#" onclick="viewMaterialHistory(' + data.id + 
                             ')"><i class="fa fa-history"></i> View History</a></li>';
                    buttons += '</ul></div>';
                    return buttons;
                }
            }
        ],
        'responsive': true,
        'language': {
            'emptyTable': 'No materials found',
            'processing': '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        }
    });

    // Initialize Progress DataTable with enhanced features
    const progressTable = $('#progressTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchProductionProgress.php',
            'type': 'POST',
            'data': function(d) {
                d.productionOrderId = productionOrderId;
            }
        },
        'order': [[ 0, 'desc' ]],
        'dom': 'Bfrtip',  // Add export buttons
        'buttons': [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        'pageLength': 10,
        'lengthMenu': [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        'columns': [
            { 
                data: 'created_at',
                render: function(data) {
                    return moment(data).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            { 
                data: 'quantity',
                render: function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { data: 'notes' },
            { data: 'created_by' }
        ],
        'responsive': true,
        'language': {
            'emptyTable': 'No progress records found',
            'processing': '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        }
    });

    // Load materials for dropdown with enhanced error handling
    $.ajax({
        url: 'php_action/fetchAvailableMaterials.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            let options = '<option value="">Select Material</option>';
            if(Array.isArray(response)) {
                response.forEach(function(material) {
                    options += '<option value="' + material.id + '" data-stock="' + 
                              material.current_stock + '">' + material.name + 
                              ' (' + material.material_code + ')</option>';
                });
            }
            $('#materialId').html(options);
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load materials. Please refresh the page.'
            });
        }
    });

    // Material selection change handler
    $('#materialId').on('change', function() {
        const stock = $(this).find(':selected').data('stock') || 0;
        $('#availableStock').text(stock.toFixed(2));
    });

    // Add Material Form Submit with enhanced validation
    $('#addMaterialForm').on('submit', function(e) {
        e.preventDefault();
        
        const materialId = $('#materialId').val();
        const requiredQuantity = parseFloat($('#requiredQuantity').val());
        const availableStock = parseFloat($('#availableStock').text());

        if(!materialId) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please select a material'
            });
            return;
        }

        if(isNaN(requiredQuantity) || requiredQuantity <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please enter a valid quantity'
            });
            return;
        }

        if(requiredQuantity > availableStock) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Required quantity cannot exceed available stock'
            });
            return;
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addMaterialModal').modal('hide');
                    materialsTable.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing your request'
                });
            }
        });
    });

    // Update Progress Form Submit with enhanced validation
    $('#updateProgressForm').on('submit', function(e) {
        e.preventDefault();
        
        const quantityProduced = parseFloat($('#quantityProduced').val());
        const remainingQuantity = parseFloat($('#remainingQuantity').text());

        if(isNaN(quantityProduced) || quantityProduced <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please enter a valid quantity'
            });
            return;
        }

        if(quantityProduced > remainingQuantity) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Quantity produced cannot exceed remaining quantity'
            });
            return;
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#updateProgressModal').modal('hide');
                    progressTable.ajax.reload();
                    loadProductionOrderDetails();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing your request'
                });
            }
        });
    });

    // Consume Material Form Submit with enhanced validation
    $('#consumeMaterialForm').on('submit', function(e) {
        e.preventDefault();
        
        const consumeQuantity = parseFloat($('#consumeQuantity').val());
        const availableStock = parseFloat($('#consumeAvailableStock').text());

        if(isNaN(consumeQuantity) || consumeQuantity <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please enter a valid quantity'
            });
            return;
        }

        if(consumeQuantity > availableStock) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Consume quantity cannot exceed available stock'
            });
            return;
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#consumeMaterialModal').modal('hide');
                    materialsTable.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing your request'
                });
            }
        });
    });

    // Button click handlers
    $('#btnAddMaterial').on('click', function() {
        $('#addMaterialForm')[0].reset();
        $('#availableStock').text('0.00');
        $('#addMaterialModal').modal('show');
    });

    $('#btnUpdateProgress').on('click', function() {
        $('#updateProgressForm')[0].reset();
        const remaining = parseFloat($('#targetQuantity').text()) - 
                        parseFloat($('#completedQuantity').text());
        $('#remainingQuantity').text(remaining.toFixed(2));
        $('#updateProgressModal').modal('show');
    });
});

// Function to load production order details with enhanced error handling
function loadProductionOrderDetails() {
    const productionOrderId = new URLSearchParams(window.location.search).get('id');
    
    $.ajax({
        url: 'php_action/fetchProductionOrderById.php',
        type: 'POST',
        data: { productionOrderId: productionOrderId },
        dataType: 'json',
        success: function(response) {
            if(response) {
                $('#orderNumber').text(response.order_number);
                $('#orderStatus').html('<span class="label label-' + getStatusLabel(response.status) + 
                                     '">' + response.status.toUpperCase() + '</span>');
                $('#startDate').text(moment(response.start_date).format('YYYY-MM-DD'));
                $('#completionDate').text(response.completion_date ? 
                                        moment(response.completion_date).format('YYYY-MM-DD') : '-');
                $('#targetQuantity').text(parseFloat(response.target_quantity).toFixed(2));
                $('#completedQuantity').text(parseFloat(response.completed_quantity).toFixed(2));
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load production order details'
            });
        }
    });
}

// Function to consume material
function consumeMaterial(materialId, materialName, availableStock) {
    $('#consumeMaterialId').val(materialId);
    $('#consumeMaterialName').text(materialName);
    $('#consumeAvailableStock').text(parseFloat(availableStock).toFixed(2));
    $('#consumeQuantity').val('');
    $('#consumeNotes').val('');
    $('#consumeMaterialModal').modal('show');
}

// Function to view material consumption history with enhanced display
function viewMaterialHistory(materialId) {
    $.ajax({
        url: 'php_action/fetchMaterialConsumptionHistory.php',
        type: 'POST',
        data: { materialId: materialId },
        dataType: 'json',
        success: function(response) {
            let historyHtml = '<div class="table-responsive"><table class="table table-bordered table-striped">';
            historyHtml += '<thead><tr><th>Date</th><th>Quantity</th><th>Notes</th><th>By</th></tr></thead><tbody>';
            
            if(Array.isArray(response)) {
                response.forEach(function(record) {
                    historyHtml += '<tr>';
                    historyHtml += '<td>' + moment(record.created_at).format('YYYY-MM-DD HH:mm:ss') + '</td>';
                    historyHtml += '<td>' + parseFloat(record.quantity).toFixed(2) + '</td>';
                    historyHtml += '<td>' + (record.notes || '-') + '</td>';
                    historyHtml += '<td>' + record.created_by + '</td>';
                    historyHtml += '</tr>';
                });
            }
            
            historyHtml += '</tbody></table></div>';
            
            Swal.fire({
                title: 'Material Consumption History',
                html: historyHtml,
                width: '800px',
                customClass: {
                    container: 'history-modal'
                }
            });
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load consumption history'
            });
        }
    });
}

// Helper function to get status label class
function getStatusLabel(status) {
    switch(status) {
        case 'draft':
            return 'default';
        case 'confirmed':
            return 'info';
        case 'in_progress':
            return 'primary';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'default';
    }
} 