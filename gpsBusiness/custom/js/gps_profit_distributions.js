$(document).ready(function() {
    // Function to update summary cards
    function updateSummaryCards() {
        $.ajax({
            url: 'php_action/fetchDistributionSummary.php',
            type: 'POST',
            data: {
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val(),
                investor_id: $('#investorSelect').val(),
                status: $('#statusSelect').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#totalDistributed').text('ETB ' + response.total_distributed.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                    $('#completedCount').text(response.completed_count);
                    $('#pendingCount').text(response.pending_count);
                    $('#averageDistribution').text('ETB ' + response.average_distribution.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                } else {
                    console.error('Error fetching summary:', response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
            }
        });
    }

    // Initialize DataTable
    var distributionsTable = $('#distributionsTable').DataTable({
        'processing': true,
        'serverSide': false,
        'ajax': {
            'url': 'php_action/fetchProfitDistributions.php',
            'type': 'POST',
            'data': function(d) {
                d.start_date = $('#startDate').val();
                d.end_date = $('#endDate').val();
                d.investor_id = $('#investorSelect').val();
                d.status = $('#statusSelect').val();
            },
            'dataSrc': function(json) {
                updateSummaryCards(); // Update summary when data is loaded
                return json.data;
            },
            'error': function(xhr, error, thrown) {
                console.error('DataTables error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error,
                    thrown: thrown
                });
                $('.remove-messages').html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="fas fa-times"></i></strong> Error loading distributions. Please try refreshing the page.'+
                '</div>');
            }
        },
        'columns': [
            { 'data': 'cycle_number' },
            { 'data': 'investor_name' },
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
                        case 'distributed':
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
            },
            {
                'data': null,
                'render': function(data, type, row) {
                    return '<button type="button" class="btn btn-info btn-sm" onclick="viewDistribution(' + row.id + ')"><i class="fas fa-eye"></i> View</button>';
                }
            }
        ],
        'order': [[4, 'desc']], // Order by distribution date descending
        'pageLength': 10,
        'responsive': true,
        'dom': 'Bfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print']
    });

    // Handle filter changes
    $('#startDate, #endDate, #investorSelect, #statusSelect').on('change', function() {
        distributionsTable.ajax.reload();
    });

    // Initialize datepicker for date inputs
    $('#startDate, #endDate').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true
    });
});

// Function to view distribution details
window.viewDistribution = function(distributionId) {
    $.ajax({
        url: 'php_action/fetchDistributionDetails.php',
        type: 'POST',
        data: { distribution_id: distributionId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#view_cycle_number').text(response.data.cycle_number);
                $('#view_investor_name').text(response.data.investor_name);
                $('#view_share_percentage').text(response.data.share_percentage + '%');
                $('#view_amount_etb').text('ETB ' + response.data.amount_etb);
                $('#view_distribution_date').text(response.data.distribution_date);
                $('#view_distribution_type').text(response.data.distribution_type);
                $('#view_status').text(response.data.status);
                $('#view_reinvested').text(response.data.reinvested);
                $('#view_created_at').text(response.data.created_at);

                $('#viewDistributionModal').modal('show');
            } else {
                showAlert('danger', response.messages || ['Error loading distribution details']);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            showAlert('danger', ['Error loading distribution details. Please try again.']);
        }
    });
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