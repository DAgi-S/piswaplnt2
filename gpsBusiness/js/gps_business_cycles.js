$(document).ready(function() {
    // Initialize DataTable
    var businessCyclesTable = $('#businessCyclesTable').DataTable({
        'ajax': 'php_action/fetchBusinessCycles.php',
        'order': [],
        'columns': [
            { data: 'cycle_number' },
            { data: 'start_date' },
            { 
                data: 'end_date',
                render: function(data) {
                    return data ? data : 'Active';
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    let badge = '';
                    switch(data) {
                        case 'active':
                            badge = '<span class="label label-success">Active</span>';
                            break;
                        case 'completed':
                            badge = '<span class="label label-info">Completed</span>';
                            break;
                        case 'cancelled':
                            badge = '<span class="label label-danger">Cancelled</span>';
                            break;
                    }
                    return badge;
                }
            },
            { 
                data: 'total_purchase_etb',
                render: function(data) {
                    return 'ETB ' + parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                data: 'total_sales_etb',
                render: function(data) {
                    return 'ETB ' + parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                data: 'total_credit_amount',
                render: function(data) {
                    return 'ETB ' + parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                data: 'net_profit_etb',
                render: function(data) {
                    const profit = parseFloat(data);
                    const color = profit >= 0 ? 'text-success' : 'text-danger';
                    return `<span class="${color}">ETB ${Math.abs(profit).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}</span>`;
                }
            },
            {
                data: null,
                render: function(data) {
                    let buttons = '';
                    if (data.status === 'active') {
                        buttons += `
                            <button class="btn btn-default btn-sm" onclick="addOrderToCycle(${data.id})">
                                <i class="fas fa-plus"></i> Order
                            </button>
                            <button class="btn btn-default btn-sm" onclick="addSaleToCycle(${data.id})">
                                <i class="fas fa-plus"></i> Sale
                            </button>
                            <button class="btn btn-success btn-sm" onclick="completeCycle(${data.id})">
                                <i class="fas fa-check"></i> Complete
                            </button>
                        `;
                    }
                    buttons += `
                        <button class="btn btn-info btn-sm" onclick="viewCycleDetails(${data.id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                    `;
                    return buttons;
                }
            }
        ]
    });

    // Handle new business cycle form submission
    $('#submitBusinessCycleForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Close modal
                    $('#addBusinessCycleModal').modal('hide');
                    // Reset form
                    $('#submitBusinessCycleForm')[0].reset();
                    // Show success message
                    $('.remove-messages').html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fas fa-check"></i></strong> '+ response.messages[0] +
                    '</div>');
                    // Reload table
                    businessCyclesTable.ajax.reload();
                }
            },
            error: function(xhr, status, error) {
                $('.remove-messages').html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="fas fa-times"></i></strong> Error occurred while creating business cycle'+
                '</div>');
            }
        });
    });

    // Handle order to cycle form submission
    $('#submitOrderToCycleForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addOrderToCycleModal').modal('hide');
                    $('#submitOrderToCycleForm')[0].reset();
                    $('.remove-messages').html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fas fa-check"></i></strong> '+ response.messages[0] +
                    '</div>');
                    businessCyclesTable.ajax.reload();
                }
            }
        });
    });

    // Handle sale to cycle form submission
    $('#submitSaleToCycleForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addSaleToCycleModal').modal('hide');
                    $('#submitSaleToCycleForm')[0].reset();
                    $('.remove-messages').html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fas fa-check"></i></strong> '+ response.messages[0] +
                    '</div>');
                    businessCyclesTable.ajax.reload();
                }
            }
        });
    });

    // Handle complete cycle form submission
    $('#completeCycleForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#completeCycleModal').modal('hide');
                    $('.remove-messages').html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fas fa-check"></i></strong> '+ response.messages[0] +
                    '</div>');
                    businessCyclesTable.ajax.reload();
                }
            }
        });
    });
});

// Function to add order to cycle
function addOrderToCycle(cycleId) {
    // Load available orders via AJAX
    $.ajax({
        url: 'php_action/fetchAvailableOrders.php',
        type: 'POST',
        data: { cycle_id: cycleId },
        dataType: 'json',
        success: function(response) {
            $('#orderId').empty();
            $.each(response.data, function(i, order) {
                $('#orderId').append($('<option>', {
                    value: order.id,
                    text: `${order.order_number} - ${order.ordered_amount} ${order.currency}`
                }));
            });
            $('#cycleIdForOrder').val(cycleId);
            $('#addOrderToCycleModal').modal('show');
        }
    });
}

// Function to add sale to cycle
function addSaleToCycle(cycleId) {
    // Load available sales via AJAX
    $.ajax({
        url: 'php_action/fetchAvailableSales.php',
        type: 'POST',
        data: { cycle_id: cycleId },
        dataType: 'json',
        success: function(response) {
            $('#saleId').empty();
            $.each(response.data, function(i, sale) {
                $('#saleId').append($('<option>', {
                    value: sale.id,
                    text: `${sale.buyer_name} - ${sale.total} ${sale.currency}`
                }));
            });
            $('#cycleIdForSale').val(cycleId);
            $('#addSaleToCycleModal').modal('show');
        }
    });
}

// Function to complete cycle
function completeCycle(cycleId) {
    $('#cycleIdToComplete').val(cycleId);
    $('#completeCycleModal').modal('show');
}

// Function to view cycle details
function viewCycleDetails(cycleId) {
    window.location.href = 'gps_business_cycle_details.php?id=' + cycleId;
} 