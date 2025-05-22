$(document).ready(function() {
    // Initialize DataTable
    var businessCyclesTable = $('#businessCyclesTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchBusinessCycles.php',
            'type': 'POST',
            'dataSrc': function(json) {
                // Check if we have data
                if (json.data && json.data.length > 0) {
                    return json.data;
                } else {
                    // If no data, show message and check console for details
                    console.log('Response:', json);
                    $('.remove-messages').html('<div class="alert alert-info">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fas fa-info-circle"></i></strong> No business cycles found.'+
                    '</div>');
                    return [];
                }
            },
            'error': function(xhr, error, thrown) {
                // Enhanced error logging
                console.error('DataTables error details:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error,
                    thrown: thrown
                });
                $('.remove-messages').html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="fas fa-times"></i></strong> Error loading business cycles. Please try refreshing the page.'+
                '</div>');
            }
        },
        'order': [[0, 'desc']],
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
                        default:
                            badge = '<span class="label label-default">' + data + '</span>';
                    }
                    return badge;
                }
            },
            { 
                data: 'total_purchase_etb',
                render: function(data) {
                    return 'ETB ' + parseFloat(data || 0).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                data: 'total_sales_etb',
                render: function(data) {
                    return 'ETB ' + parseFloat(data || 0).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                data: 'total_expenses_etb',
                render: function(data) {
                    return 'ETB ' + parseFloat(data || 0).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                data: 'total_credit_amount',
                render: function(data) {
                    return 'ETB ' + parseFloat(data || 0).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                data: 'net_profit_etb',
                render: function(data) {
                    const profit = parseFloat(data || 0);
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
        ],
        'pageLength': 10,
        'responsive': true
    });

    // Handle form submissions
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
                    showAlert('success', response.messages);
                    businessCyclesTable.ajax.reload();
                } else {
                    showAlert('danger', response.messages);
                }
            },
            error: function() {
                showAlert('danger', ['Error adding order to cycle']);
            }
        });
    });

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
                    showAlert('success', response.messages);
                    businessCyclesTable.ajax.reload();
                } else {
                    showAlert('danger', response.messages);
                }
            },
            error: function() {
                showAlert('danger', ['Error adding sale to cycle']);
            }
        });
    });

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
                    showAlert('success', response.messages);
                    businessCyclesTable.ajax.reload();
                } else {
                    showAlert('danger', response.messages);
                }
            },
            error: function() {
                showAlert('danger', ['Error completing cycle']);
            }
        });
    });

    $('#submitBusinessCycleForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addBusinessCycleModal').modal('hide');
                    $('#submitBusinessCycleForm')[0].reset();
                    showAlert('success', response.messages);
                    businessCyclesTable.ajax.reload();
                } else {
                    showAlert('danger', response.messages);
                }
            },
            error: function(xhr, error, thrown) {
                console.error('Error details:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error,
                    thrown: thrown
                });
                showAlert('danger', ['Error creating business cycle. Please try again.']);
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
            if (response.success) {
                $('#orderId').empty();
                $.each(response.data, function(i, order) {
                    $('#orderId').append($('<option>', {
                        value: order.id,
                        text: `${order.order_number} - ${order.ordered_amount} ${order.currency}`
                    }));
                });
                $('#cycleIdForOrder').val(cycleId);
                $('#addOrderToCycleModal').modal('show');
            } else {
                showAlert('danger', response.messages || ['Error loading available orders']);
            }
        },
        error: function() {
            showAlert('danger', ['Error loading available orders']);
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
            if (response.success) {
                $('#saleId').empty();
                $.each(response.data, function(i, sale) {
                    $('#saleId').append($('<option>', {
                        value: sale.id,
                        text: `${sale.buyer_name} - ${sale.total} ${sale.currency}`
                    }));
                });
                $('#cycleIdForSale').val(cycleId);
                $('#addSaleToCycleModal').modal('show');
            } else {
                showAlert('danger', response.messages || ['Error loading available sales']);
            }
        },
        error: function() {
            showAlert('danger', ['Error loading available sales']);
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

// Helper function to show alerts
function showAlert(type, messages) {
    let alertHtml = '<div class="alert alert-' + type + '">'+
        '<button type="button" class="close" data-dismiss="alert">&times;</button>';
    
    if (Array.isArray(messages)) {
        messages.forEach(function(message) {
            alertHtml += '<p><strong><i class="fas fa-' + (type === 'success' ? 'check' : 'times') + '"></i></strong> ' + message + '</p>';
        });
    } else {
        alertHtml += '<strong><i class="fas fa-' + (type === 'success' ? 'check' : 'times') + '"></i></strong> ' + messages;
    }
    
    alertHtml += '</div>';
    $('.remove-messages').html(alertHtml);
} 