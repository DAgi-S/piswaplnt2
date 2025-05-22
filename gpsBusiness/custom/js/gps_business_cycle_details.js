// Function to view order details
function viewOrder(orderId) {
    $.ajax({
        url: 'php_action/fetchOrderDetails.php',
        type: 'POST',
        data: { order_id: orderId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate order details in modal
                $('#view_order_number').text(response.data.order_number);
                $('#view_order_date').text(response.data.order_date);
                $('#view_quantity').text(response.data.quantity);
                $('#view_unit_price').text(formatCurrency(response.data.unit_price, 'ETB'));
                $('#view_total_price').text(formatCurrency(response.data.total_price, 'ETB'));
                $('#view_credit_status').text(response.data.has_credit ? 'Yes' : 'No');
                $('#view_credit_amount').text(formatCurrency(response.data.credit_amount, 'ETB'));
                $('#view_created_at').text(response.data.created_at);

                // Show the modal
                $('#viewOrderModal').modal('show');
            } else {
                showAlert('danger', response.messages || ['Error loading order details']);
            }
        },
        error: function() {
            showAlert('danger', ['Error loading order details']);
        }
    });
}

// Function to view sale details
function viewSale(saleId) {
    $.ajax({
        url: 'php_action/fetchSaleDetails.php',
        type: 'POST',
        data: { sale_id: saleId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate sale details in modal
                $('#view_buyer_name').text(response.data.buyer_name);
                $('#view_contact').text(response.data.contact);
                $('#view_sale_date').text(response.data.sale_date);
                $('#view_sales_type').text(response.data.sales_type);
                $('#view_sale_quantity').text(response.data.quantity);
                $('#view_sale_unit_price').text(formatCurrency(response.data.unit_price, response.data.currency));
                $('#view_total_amount').text(formatCurrency(response.data.total, response.data.currency));
                $('#view_currency').text(response.data.currency);
                $('#view_rate').text(response.data.rate);

                // Show the modal
                $('#viewSaleModal').modal('show');
            } else {
                showAlert('danger', response.messages || ['Error loading sale details']);
            }
        },
        error: function() {
            showAlert('danger', ['Error loading sale details']);
        }
    });
}

// Function to add expense to cycle
function addExpenseToCycle(cycleId) {
    $('#cycleIdForExpense').val(cycleId);
    $('#addExpenseToCycleModal').modal('show');
}

// Function to view expense details
function viewExpense(expenseId) {
    console.log('Viewing expense:', expenseId); // Debug log
    $.ajax({
        url: 'php_action/fetchExpenseDetails.php',
        type: 'POST',
        data: { expense_id: expenseId },
        dataType: 'json',
        success: function(response) {
            console.log('Response:', response); // Debug log
            if (response.success) {
                // Populate expense details in modal
                $('#view_expense_date').text(response.data.expense_date || '-');
                $('#view_expense_description').text(response.data.description || '-');
                $('#view_expense_amount').text(formatCurrency(response.data.amount_etb || 0, 'ETB'));
                $('#view_expense_type').text(response.data.expense_type || '-');
                $('#view_payment_method').text(response.data.payment_method || '-');
                $('#view_reference_number').text(response.data.reference_number || '-');
                $('#view_expense_notes').text(response.data.notes || '-');
                $('#view_expense_created_at').text(response.data.created_at || '-');

                // Show the modal
                $('#viewExpenseModal').modal('show');
            } else {
                showAlert('danger', response.messages || ['Error loading expense details']);
            }
        },
        error: function(xhr, error, thrown) {
            console.error('Error:', error, thrown); // Debug log
            console.error('XHR:', xhr.responseText); // Additional debug log
            showAlert('danger', ['Error loading expense details. Please try again.']);
        }
    });
}

// Helper function to format currency
function formatCurrency(amount, currency) {
    return currency + ' ' + parseFloat(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Initialize Profit Distribution Table
var profitDistributionTable = $('#profitDistributionTable').DataTable({
    'ajax': {
        'url': 'php_action/fetchProfitDistribution.php',
        'type': 'POST',
        'data': function(d) {
            d.cycle_id = cycleId;
        },
        'error': function(xhr, error, thrown) {
            console.error('Error loading profit distributions:', error);
            showAlert('danger', ['Error loading profit distributions. Please try refreshing the page.']);
        }
    },
    'columns': [
        { 'data': 'investor' },
        { 'data': 'share_percentage' },
        { 'data': 'amount_etb' },
        { 'data': 'distribution_date' },
        { 'data': 'distribution_type' },
        { 
            'data': 'status',
            'render': function(data, type, row) {
                let statusClass = '';
                switch(data.toLowerCase()) {
                    case 'pending':
                        statusClass = 'label-warning';
                        break;
                    case 'completed':
                        statusClass = 'label-success';
                        break;
                    case 'cancelled':
                        statusClass = 'label-danger';
                        break;
                    default:
                        statusClass = 'label-default';
                }
                return '<span class="label ' + statusClass + '">' + data + '</span>';
            }
        }
    ],
    'pageLength': 10,
    'responsive': true,
    'dom': 'Bfrtip',
    'buttons': [
        'copy', 'csv', 'excel', 'pdf', 'print'
    ]
});

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

$(document).ready(function() {
    // Add error handling for DataTables
    $.fn.dataTable.ext.errMode = 'none';

    // Initialize Orders DataTable with error handling
    var cycleOrdersTable = $('#cycleOrdersTable').DataTable({
        'processing': true,
        'serverSide': false,
        'ajax': {
            'url': 'php_action/fetchCycleOrders.php',
            'type': 'POST',
            'data': {
                cycle_id: cycleId
            },
            'error': function(xhr, error, thrown) {
                console.error('DataTables error:', error, thrown);
                $('.remove-messages').html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="fas fa-times"></i></strong> Error loading orders. Please try refreshing the page.'+
                '</div>');
            }
        },
        'order': [[1, 'desc']], // Order by date descending
        'columns': [
            { data: 'order_number' },
            { data: 'order_date' },
            { data: 'amount' },
            { data: 'currency' },
            { data: 'credit_amount' },
            { data: 'status' },
            { 
                data: 'action',
                render: function(data, type, row) {
                    return '<button class="btn btn-info btn-sm" onclick="viewOrder(' + row.id + ')"><i class="fas fa-eye"></i> View</button>';
                }
            }
        ]
    });

    // Initialize Sales DataTable with error handling
    var cycleSalesTable = $('#cycleSalesTable').DataTable({
        'processing': true,
        'serverSide': false,
        'ajax': {
            'url': 'php_action/fetchCycleSales.php',
            'type': 'POST',
            'data': {
                cycle_id: cycleId
            },
            'error': function(xhr, error, thrown) {
                console.error('DataTables error:', error, thrown);
                $('.remove-messages').html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="fas fa-times"></i></strong> Error loading sales. Please try refreshing the page.'+
                '</div>');
            }
        },
        'order': [[0, 'desc']], // Order by date descending
        'columns': [
            { data: 'date' },
            { data: 'buyer' },
            { data: 'amount' },
            { data: 'currency' },
            { data: 'type' },
            { data: 'action' }
        ]
    });

    // Handle DataTable errors globally
    $(document).on('error.dt', function(e, settings, techNote, message) {
        console.error('DataTables error:', message);
    });

    // Initialize Expenses DataTable
    var cycleExpensesTable = $('#cycleExpensesTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchCycleExpenses.php',
            'type': 'POST',
            'data': function(d) {
                d.cycle_id = cycleId;
            },
            'error': function(xhr, error, thrown) {
                console.error('Error loading expenses:', error);
                showAlert('danger', ['Error loading expenses. Please try refreshing the page.']);
            }
        },
        'columns': [
            { 'data': 'date' },
            { 'data': 'description' },
            { 'data': 'amount' },
            { 'data': 'type' },
            { 'data': 'payment_method' },
            { 'data': 'reference_number' },
            { 'data': 'action' }
        ],
        'order': [[0, 'desc']],
        'pageLength': 10,
        'responsive': true
    });

    // Handle expense form submission
    $('#submitExpenseToCycleForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addExpenseToCycleModal').modal('hide');
                    $('#submitExpenseToCycleForm')[0].reset();
                    showAlert('success', response.messages);
                    cycleExpensesTable.ajax.reload();
                    // Reload the page to update totals
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert('danger', response.messages);
                }
            },
            error: function() {
                showAlert('danger', ['Error adding expense']);
            }
        });
    });
}); 