<?php
require_once '../php_action/core.php';

// Initialize the database connection if not already done
if (!isset($connect)) {
    require_once '../php_action/db_connect.php';
}

require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('location: ../index.php');
    exit();
}

// Fetch investors for dropdown
$investorsSql = "SELECT id, name FROM gps_investors ORDER BY name ASC";
$investorsResult = $connect->query($investorsSql);
?>

<style>
    /* Form Styles */
    .form-group {
        margin-bottom: 8px;
    }
    
    .form-control {
        font-size: 11px;
        height: 30px;
        padding: 5px 10px;
    }
    
    .control-label {
        font-size: 11px;
        padding-top: 5px;
    }
    
    .modal-body {
        padding: 15px;
    }
    
    .row {
        margin-bottom: 5px;
    }
    
    /* Image Preview */
    .payment-image-preview {
        max-width: 100%;
        max-height: 200px;
        margin-top: 10px;
    }
    
    /* Modal Size */
    .modal-dialog {
        width: 600px;
    }
    
    /* Button Styles */
    .btn {
        font-size: 11px;
        padding: 4px 8px;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fas fa-chart-pie"></i> Profit Distribution Report
                </div>
            </div>
            <div class="panel-body">
                <!-- Filters -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <label for="startDate">Start Date</label>
                        <input type="date" class="form-control" id="startDate" name="start_date">
                    </div>
                    <div class="col-md-3">
                        <label for="endDate">End Date</label>
                        <input type="date" class="form-control" id="endDate" name="end_date">
                    </div>
                    <div class="col-md-3">
                        <label for="investor">Investor</label>
                        <select class="form-control" id="investor" name="investor_id">
                            <option value="">All Investors</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="distributed">Distributed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h3 class="panel-title">Total Profit Distributed</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="totalProfitDistributed">ETB 0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <h3 class="panel-title">Completed Distributions</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="completedDistributions">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <h3 class="panel-title">Pending Distributions</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="pendingDistributions">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h3 class="panel-title">Average Distribution</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="averageDistribution">ETB 0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Distribution Table -->
                <table class="table table-hover table-striped table-bordered" id="profitDistributionTable">
                    <thead>
                        <tr>
                            <th>Cycle Number</th>
                            <th>Investor</th>
                            <th>Share %</th>
                            <th>Amount (ETB)</th>
                            <th>Distribution Date</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!-- Distribution Details Modal -->
        <div class="modal fade" id="distributionDetailsModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Distribution Details</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Cycle Number</th>
                                        <td id="modalCycleNumber"></td>
                                    </tr>
                                    <tr>
                                        <th>Investor</th>
                                        <td id="modalInvestor"></td>
                                    </tr>
                                    <tr>
                                        <th>Share Percentage</th>
                                        <td id="modalSharePercentage"></td>
                                    </tr>
                                    <tr>
                                        <th>Amount</th>
                                        <td id="modalAmount"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Distribution Date</th>
                                        <td id="modalDistributionDate"></td>
                                    </tr>
                                    <tr>
                                        <th>Type</th>
                                        <td id="modalType"></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td id="modalStatus"></td>
                                    </tr>
                                    <tr>
                                        <th>Notes</th>
                                        <td id="modalNotes"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <h4>Distribution History</h4>
                                <table class="table table-bordered table-striped" id="distributionHistoryTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Action</th>
                                            <th>Status</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="distributionHistoryBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2 for investor dropdown
    $('#investor').select2({
        ajax: {
            url: 'php_action/fetchInvestors.php',
            dataType: 'json',
            delay: 250,
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.name
                        };
                    })
                };
            },
            cache: true
        },
        placeholder: 'Select an investor',
        minimumInputLength: 0
    });

    // Initialize DataTable
    var distributionTable = $('#profitDistributionTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchProfitDistributions.php',
            'method': 'POST',
            'data': function(d) {
                d.start_date = $('#startDate').val();
                d.end_date = $('#endDate').val();
                d.investor_id = $('#investor').val();
                d.status = $('#status').val();
            }
        },
        'order': [[4, 'desc']],
        'columns': [
            { data: 'cycle_number' },
            { data: 'investor_name' },
            { 
                data: 'share_percentage',
                render: function(data) {
                    return parseFloat(data).toFixed(2) + '%';
                }
            },
            { 
                data: 'amount_etb',
                render: function(data) {
                    const amount = parseFloat(data);
                    const color = amount >= 0 ? 'text-success' : 'text-danger';
                    return `<span class="${color}">${amount.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}</span>`;
                }
            },
            { data: 'distribution_date' },
            { 
                data: 'distribution_type',
                render: function(data) {
                    return data.charAt(0).toUpperCase() + data.slice(1);
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    let badge = '';
                    switch(data) {
                        case 'pending':
                            badge = '<span class="label label-warning">Pending</span>';
                            break;
                        case 'distributed':
                            badge = '<span class="label label-success">Distributed</span>';
                            break;
                        case 'cancelled':
                            badge = '<span class="label label-danger">Cancelled</span>';
                            break;
                    }
                    return badge;
                }
            },
            {
                data: 'id',
                render: function(data, type, row) {
                    return `<button class="btn btn-info btn-sm" onclick="viewDistributionDetails(${data})">
                        <i class="fas fa-eye"></i> View
                    </button>`;
                }
            }
        ],
        'drawCallback': function(settings) {
            updateSummaryCards();
        }
    });

    // Filter change handlers
    $('#startDate, #endDate, #investor, #status').on('change', function() {
        distributionTable.ajax.reload();
    });

    // Function to update summary cards
    function updateSummaryCards() {
        $.ajax({
            url: 'php_action/fetchDistributionSummary.php',
            method: 'POST',
            data: {
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val(),
                investor_id: $('#investor').val(),
                status: $('#status').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#totalProfitDistributed').text('ETB ' + response.total_distributed.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                    $('#completedDistributions').text(response.completed_count);
                    $('#pendingDistributions').text(response.pending_count);
                    $('#averageDistribution').text('ETB ' + response.average_distribution.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                }
            }
        });
    }
});

// Function to view distribution details
function viewDistributionDetails(distributionId) {
    $.ajax({
        url: 'php_action/fetchDistributionDetails.php',
        method: 'POST',
        data: { distribution_id: distributionId },
        success: function(response) {
            if (response.success) {
                const data = response.data;
                
                // Update modal fields
                $('#modalCycleNumber').text(data.cycle_number);
                $('#modalInvestor').text(data.investor_name);
                $('#modalSharePercentage').text(data.share_percentage + '%');
                $('#modalAmount').text('ETB ' + parseFloat(data.amount_etb).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                $('#modalDistributionDate').text(data.distribution_date);
                $('#modalType').text(data.distribution_type.charAt(0).toUpperCase() + data.distribution_type.slice(1));
                $('#modalStatus').html(getStatusBadge(data.status));
                $('#modalNotes').text(data.notes || 'No notes available');

                // Update history table
                $('#distributionHistoryBody').empty();
                data.history.forEach(function(item) {
                    $('#distributionHistoryBody').append(`
                        <tr>
                            <td>${item.date}</td>
                            <td>${item.action}</td>
                            <td>${getStatusBadge(item.status)}</td>
                            <td>${item.notes || ''}</td>
                        </tr>
                    `);
                });

                // Show modal
                $('#distributionDetailsModal').modal('show');
            }
        }
    });
}

// Helper function to get status badge HTML
function getStatusBadge(status) {
    let badge = '';
    switch(status) {
        case 'pending':
            badge = '<span class="label label-warning">Pending</span>';
            break;
        case 'distributed':
            badge = '<span class="label label-success">Distributed</span>';
            break;
        case 'cancelled':
            badge = '<span class="label label-danger">Cancelled</span>';
            break;
    }
    return badge;
}
</script>

<?php require_once '../includes/footer.php'; ?> 