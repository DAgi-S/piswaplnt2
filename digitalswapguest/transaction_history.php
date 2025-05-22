<?php
require_once 'includes/core.php';
require_once 'includes/header.php';

// Get current user data
$user = getCurrentUser();
if (!$user) {
    setFlashMessage('Please log in to continue', 'warning');
    header('Location: index.php');
    exit();
}
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Transaction History</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-history"></i> Transaction History
                </div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="well">
                            <form id="filterForm" class="form-inline">
                                <div class="form-group">
                                    <label for="startDate">From:</label>
                                    <input type="date" class="form-control" id="startDate" name="startDate">
                                </div>
                                <div class="form-group" style="margin-left: 10px;">
                                    <label for="endDate">To:</label>
                                    <input type="date" class="form-control" id="endDate" name="endDate">
                                </div>
                                <div class="form-group" style="margin-left: 10px;">
                                    <label for="type">Type:</label>
                                    <select class="form-control" id="type" name="type">
                                        <option value="">All</option>
                                        <option value="deposit">Deposit</option>
                                        <option value="withdraw">Withdraw</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary" style="margin-left: 10px;">
                                    <i class="fa fa-search"></i> Filter
                                </button>
                                <button type="button" class="btn btn-success" id="exportBtn" style="margin-left: 10px;">
                                    <i class="fa fa-file-excel-o"></i> Export to Excel
                                </button>
                                <button type="button" class="btn btn-info" id="printBtn" style="margin-left: 10px;">
                                    <i class="fa fa-print"></i> Print View
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Transaction Summary Statistics -->
                <div class="row" id="transactionSummary">
                    <div class="col-md-3">
                        <div class="summary-box bg-primary">
                            <div class="summary-title">Total Transactions</div>
                            <div class="summary-value" id="totalTransactions">0</div>
                            <div class="summary-footer">In selected period</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-box bg-success">
                            <div class="summary-title">Total Deposits</div>
                            <div class="summary-value" id="totalDeposits">0.00</div>
                            <div class="summary-footer">Incoming funds</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-box bg-danger">
                            <div class="summary-title">Total Withdraws</div>
                            <div class="summary-value" id="totalWithdraws">0.00</div>
                            <div class="summary-footer">Outgoing funds</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-box bg-info">
                            <div class="summary-title">Current Balance</div>
                            <div class="summary-value" id="currentBalance">0.00</div>
                            <div class="summary-footer">Available balance</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="transactionTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Platform</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Comment</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Transaction Details</h4>
            </div>
            <div class="modal-body">
                <table class="table">
                    <tr>
                        <th width="150">Transaction ID:</th>
                        <td id="modalTransactionId"></td>
                    </tr>
                    <tr>
                        <th>Date:</th>
                        <td id="modalDate"></td>
                    </tr>
                    <tr>
                        <th>Name:</th>
                        <td id="modalName"></td>
                    </tr>
                    <tr>
                        <th>Platform:</th>
                        <td id="modalPlatform"></td>
                    </tr>
                    <tr>
                        <th>Type:</th>
                        <td id="modalType"></td>
                    </tr>
                    <tr>
                        <th>Amount:</th>
                        <td id="modalAmount"></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td id="modalStatus"></td>
                    </tr>
                    <tr>
                        <th>Comment:</th>
                        <td id="modalComment"></td>
                    </tr>
                    <tr>
                        <th>Receipt:</th>
                        <td id="modalReceipt">
                            <div id="receipt-image-container">
                                <!-- Image will be inserted here -->
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Receipt Image</h4>
            </div>
            <div class="modal-body text-center">
                <img id="previewImage" src="" alt="Preview" style="max-width: 100%; max-height: 80vh;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Print View Modal -->
<div class="modal fade" id="printViewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Print Transaction History</h4>
            </div>
            <div class="modal-body" id="printViewContent">
                <div class="print-header">
                    <h2>Transaction History</h2>
                    <p><strong>Account:</strong> <span id="printAccountInfo"></span></p>
                    <p><strong>Period:</strong> <span id="printPeriod"></span></p>
                </div>
                <div class="print-summary">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Total Transactions:</strong>
                            <span id="printTotalTransactions"></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Total Credits:</strong>
                            <span id="printTotalCredits"></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Total Debits:</strong>
                            <span id="printTotalDebits"></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Net Change:</strong>
                            <span id="printNetChange"></span>
                        </div>
                    </div>
                </div>
                <div class="print-transactions">
                    <table class="table table-bordered" id="printTransactionTable">
                        <!-- Transaction data will be populated here -->
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fa fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap.min.css">

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>

<script>
$(document).ready(function() {
    var transactionTable = $('#transactionTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchTransactions.php',
            'type': 'POST',
            'data': function(d) {
                d.startDate = $('#startDate').val();
                d.endDate = $('#endDate').val();
                d.type = $('#type').val();
            }
        },
        'processing': true,
        'serverSide': false,
        'order': [[1, 'desc']],
        'columns': [
            { 'data': 'id' },
            { 'data': 'transaction_date' },
            { 'data': 'name' },
            { 
                'data': 'type',
                'render': function(data, type, row) {
                    return formatTransactionType(data);
                }
            },
            { 'data': 'platform' },
            { 
                'data': 'amount',
                'render': function(data, type, row) {
                    return formatAmount(data);
                }
            },
            { 
                'data': 'status',
                'render': function(data, type, row) {
                    return formatStatus(data);
                }
            },
            { 'data': 'comment' },
            {
                'data': null,
                'orderable': false,
                'searchable': false,
                'render': function(data, type, row) {
                    return '<button class="btn btn-info btn-sm view-transaction">' +
                           '<i class="fa fa-eye"></i> View</button>';
                }
            }
        ],
        'drawCallback': function(settings) {
            var api = this.api();
            var data = api.ajax.json();
            if (data && data.summary) {
                updateSummaryStatistics(data.summary);
            }
        }
    });

    // Listen for account switch events
    $(document).on('accountSwitched', function() {
        transactionTable.ajax.reload();
    });

    // Update view transaction button click handler
    $('#transactionTable').on('click', '.view-transaction', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var data = transactionTable.row($(this).closest('tr')).data();
        
        // Update modal fields
        $('#modalTransactionId').text(data.id || 'N/A');
        $('#modalDate').text(data.transaction_date || 'N/A');
        $('#modalName').text(data.name || 'N/A');
        $('#modalPlatform').text(data.platform || 'N/A');
        $('#modalType').html(formatTransactionType(data.type) || 'N/A');
        $('#modalAmount').text(formatAmount(data.amount) || 'N/A');
        $('#modalStatus').html(formatStatus(data.status) || 'N/A');
        $('#modalComment').text(data.comment || 'N/A');

        // Handle receipt image
        var receiptContainer = $('#receipt-image-container');
        receiptContainer.empty();

        if (data.image) {
            var imagePath = data.image.startsWith('assets/images/digitalswap/') ? 
                '../' + data.image : 
                '../assets/images/digitalswap/' + data.image;

            var img = $('<img>')
                .attr('src', imagePath)
                .css({
                    'max-width': '200px',
                    'cursor': 'pointer',
                    'margin-bottom': '5px'
                })
                .on('error', function() {
                    $(this).hide();
                    receiptContainer.text('Image not found');
                })
                .on('load', function() {
                    $(this).show();
                });

            var hint = $('<small>').text('Click image to view full size');
            
            img.click(function() {
                $('#previewImage')
                    .attr('src', imagePath)
                    .on('load', function() {
                        $('#transactionDetailsModal').modal('hide');
                        $('#imagePreviewModal').modal('show');
                    })
                    .on('error', function() {
                        alert('Error loading image');
                    });
            });
            
            receiptContainer.append(img).append('<br>').append(hint);
        } else {
            receiptContainer.text('No receipt available');
        }

        $('#transactionDetailsModal').modal('show');
    });

    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        transactionTable.ajax.reload();
    });

    $('#exportBtn').on('click', function() {
        var params = $.param({
            startDate: $('#startDate').val(),
            endDate: $('#endDate').val(),
            type: $('#type').val(),
            export: true
        });
        window.location.href = 'php_action/exportTransactions.php?' + params;
    });

    $('#printBtn').on('click', function() {
        showPrintView();
    });

    // Handle image preview modal close
    $('#imagePreviewModal').on('hidden.bs.modal', function () {
        $('#transactionDetailsModal').modal('show');
    });

    // Handle image preview modal open
    $('#imagePreviewModal').on('show.bs.modal', function () {
        var img = $('#previewImage');
        if (!img.attr('src')) {
            $('#imagePreviewModal').modal('hide');
            alert('No image to display');
            return false;
        }
    });
});

function updateSummaryStatistics(summary) {
    if (!summary) return;
    
    $('#totalTransactions').text(summary.totalTransactions || 0);
    // Format deposits
    $('#totalDeposits').text(summary.totalDeposits);
    // Format withdraws
    $('#totalWithdraws').text(summary.totalWithdraws);
    // Format current balance
    $('#currentBalance').text(summary.netChange);
}

function showPrintView() {
    var startDate = $('#startDate').val() || 'All time';
    var endDate = $('#endDate').val() || 'Present';
    $('#printPeriod').text(startDate + ' to ' + endDate);
    
    // Copy summary statistics
    $('#printTotalTransactions').text($('#totalTransactions').text());
    $('#printTotalCredits').text($('#totalDeposits').text());
    $('#printTotalDebits').text($('#totalWithdraws').text());
    $('#printNetChange').text($('#currentBalance').text());
    
    // Clear existing table content first
    $('#printTransactionTable').empty();
    
    // Get all data from DataTable (not just current page)
    var allData = $('#transactionTable').DataTable().data().toArray();
    
    // Create table header
    var tableHtml = '<thead><tr>' +
        '<th style="width: 2%;">ID</th>' +
        '<th style="width: 3%;">Date</th>' +
        '<th style="width: 5%;">Name</th>' +
        '<th style="width: 6%;">Type</th>' +
        '<th style="width: 5%;">Platform</th>' +
        '<th style="width: 5%;">Amount</th>' +
        '<th style="width: 5%;">Status</th>' +
        '<th style="width: 15%;">Comment</th>' +
        '</tr></thead><tbody>';

    // Add all transactions
    allData.forEach(function(transaction) {
        tableHtml += '<tr>';
        tableHtml += '<td>' + (transaction.id || 'N/A') + '</td>';
        tableHtml += '<td>' + (transaction.transaction_date || 'N/A') + '</td>';
        tableHtml += '<td>' + (transaction.name || 'N/A') + '</td>';
        tableHtml += '<td>' + formatTransactionType(transaction.type).replace(/label/g, 'print-label') + '</td>';
        tableHtml += '<td>' + (transaction.platform || 'N/A') + '</td>';
        tableHtml += '<td>' + (transaction.amount ? 'ETB ' + parseFloat(transaction.amount).toFixed(2) : 'N/A') + '</td>';
        tableHtml += '<td>' + formatStatus(transaction.status).replace(/label/g, 'print-label') + '</td>';
        tableHtml += '<td style="max-width: 100px; word-wrap: break-word;">' + (transaction.comment || '') + '</td>';
        tableHtml += '</tr>';
    });
    tableHtml += '</tbody>';
    $('#printTransactionTable').html(tableHtml);
    
    $('#printViewModal').modal('show');
}

function formatTransactionType(type) {
    if (!type) return 'N/A';
    if (type.toLowerCase() === 'withdraw') {
        return '<span class="label label-warning">Withdraw</span>';
    } else if (type.toLowerCase() === 'deposit') {
        return '<span class="label label-success">Deposit</span>';
    }
    return type;
}

function formatStatus(status) {
    if (status === 1 || status === '1' || status === 'Completed') {
        return '<span class="label label-success">Completed</span>';
    } else if (status === 0 || status === '0' || status === 'Pending') {
        return '<span class="label label-warning">Pending</span>';
    } else if (status === 2 || status === '2' || status === 'Failed') {
        return '<span class="label label-danger">Failed</span>';
    }
    return status || 'N/A';
}

function formatAmount(amount) {
    if (!amount) return 'N/A';
    return 'ETB ' + parseFloat(amount).toFixed(2);
}
</script>

<style>
/* General Styles */
.well {
    background-color: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 25px;
}

/* Filter Form Styles */
#filterForm {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
    padding: 15px;
}

#filterForm .form-group {
    margin: 0;
    flex-grow: 1;
    min-width: 200px;
}

#filterForm label {
    display: block;
    margin-bottom: 5px;
    color: #555;
    font-weight: 600;
}

#filterForm .form-control {
    width: 100%;
    height: 38px;
}

#filterForm button {
    height: 38px;
    min-width: 120px;
    margin: 0;
}

/* Summary Box Styles */
.summary-box {
    padding: 25px;
    border-radius: 8px;
    color: white;
    margin-bottom: 25px;
    transition: transform 0.2s;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.summary-box:hover {
    transform: translateY(-2px);
}

.summary-title {
    font-size: 16px;
    margin-bottom: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-value {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 8px;
    letter-spacing: 0.5px;
}

.summary-footer {
    font-size: 13px;
    opacity: 0.9;
}

/* Table Styles */
.table-responsive {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    margin-top: 25px;
}

.table {
    margin-bottom: 0;
}

.table > thead > tr > th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 13px;
    padding: 12px;
}

.table > tbody > tr > td {
    padding: 12px;
    vertical-align: middle;
}

/* Status Labels */
.label {
    padding: 5px 10px;
    font-size: 12px;
    border-radius: 4px;
    font-weight: 500;
}

/* Modal Styles */
.modal-content {
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 8px 8px 0 0;
    padding: 15px 20px;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    border-radius: 0 0 8px 8px;
    padding: 15px 20px;
}

/* Receipt Image Styles */
#receipt-image-container {
    text-align: center;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

#receipt-image-container img {
    border: 2px solid #dee2e6;
    padding: 5px;
    border-radius: 8px;
    background-color: white;
    transition: transform 0.2s;
    max-width: 100%;
    height: auto;
}

#receipt-image-container img:hover {
    transform: scale(1.02);
}

#receipt-image-container small {
    color: #666;
    display: block;
    margin-top: 8px;
    font-style: italic;
}

/* Print View Styles */
@media print {
    body * {
        visibility: hidden;
        margin: 0;
        padding: 0;
    }
    
    #printViewContent, #printViewContent * {
        visibility: visible;
    }
    
    #printViewContent {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    
    .modal {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }
    
    .modal-dialog {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        max-width: none !important;
    }
    
    .modal-content {
        border: none !important;
        box-shadow: none !important;
    }
    
    .print-header {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #000;
    }
    
    .print-summary {
        margin: 15px 0;
        padding: 10px;
        page-break-inside: avoid;
    }
    
    #printTransactionTable {
        width: 100% !important;
        margin: 15px 0;
        border-collapse: collapse;
        table-layout: fixed;
    }
    
    #printTransactionTable th,
    #printTransactionTable td {
        padding: 8px;
        border: 1px solid #000;
        font-size: 11px !important;
        page-break-inside: avoid;
        word-wrap: break-word;
        max-width: none !important;
        overflow: visible !important;
    }
    
    #printTransactionTable th {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        font-weight: bold;
    }
    
    .print-label {
        padding: 2px 4px;
        border-radius: 2px;
        font-size: 10px !important;
        background-color: #f8f9fa !important;
        border: 1px solid #ddd;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    @page {
        size: landscape;
        margin: 0.5cm;
    }
    
    /* Ensure long comments wrap properly */
    #printTransactionTable td:last-child {
        white-space: normal !important;
        word-wrap: break-word !important;
        max-width: none !important;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    #filterForm {
        flex-direction: column;
    }

    #filterForm .form-group {
        width: 100%;
    }

    .summary-box {
        margin-bottom: 15px;
    }

    .table-responsive {
        border: none;
        margin-bottom: 0;
    }

    .modal-dialog {
        margin: 10px;
    }
}

/* Add this to your existing CSS */
.print-label {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: normal;
}

.print-label-success {
    background-color: #dff0d8;
    color: #3c763d;
    border: 1px solid #d6e9c6;
}

.print-label-warning {
    background-color: #fcf8e3;
    color: #8a6d3b;
    border: 1px solid #faebcc;
}

.print-label-danger {
    background-color: #f2dede;
    color: #a94442;
    border: 1px solid #ebccd1;
}

@media print {
    .print-label {
        padding: 1px 4px;
        border-radius: 2px;
        font-size: 10px !important;
    }
    
    #printViewContent {
        position: relative !important;
        left: auto !important;
        top: auto !important;
    }
    
    .modal {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .modal-dialog {
        width: 120% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .modal-content {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 