<?php
require_once 'includes/header.php';

// Check if user has permission to view payments
if (!hasPermission('purchase.payment.view')) {
    header('Location: index.php');
    exit();
}

// Get user permissions
$canAddPayment = hasPermission('purchase.payment.add');
$canEditPayment = hasPermission('purchase.payment.edit');
$canViewPayment = hasPermission('purchase.payment.view');
?>

<!-- Include Date Range Picker CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"><i class="fa fa-money"></i> Purchase Payments</div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <div class="row">
                    <div class="col-md-4">
                        <!-- Date Range Filter -->
                        <div class="form-group">
                            <label for="daterange" class="control-label">Date Range:</label>
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                <input type="text" class="form-control" id="daterange" name="daterange">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" id="clearDateRange">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <table class="table table-hover table-striped table-bordered" id="managePaymentsTable">
                    <thead>
                        <tr>
                            <th>Payment Date</th>
                            <th>Purchase #</th>
                            <th>Supplier</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Notes</th>
                            <th>Proof</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Payment Proof Preview Modal -->
<div class="modal fade" id="paymentProofModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-file"></i> Payment Proof</h4>
            </div>
            <div class="modal-body text-center">
                <div id="proofPreview">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary" id="downloadProof" target="_blank" download>Download</a>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Payment proof styles */
.payment-proof-preview {
    max-width: 100%;
    max-height: 70vh;
    margin: 0 auto;
}
.proof-link {
    cursor: pointer;
    color: #337ab7;
    display: inline-block;
    margin-right: 10px;
}
.proof-link:hover {
    text-decoration: underline;
}
#proofPreview {
    min-height: 200px;
    max-height: 70vh;
    overflow: auto;
}
#proofPreview img {
    max-width: 100%;
    height: auto;
}
#proofPreview iframe {
    width: 100%;
    height: 70vh;
    border: none;
}
.text-muted {
    color: #777;
}
.btn-group-xs > .btn {
    padding: 1px 5px;
    font-size: 12px;
    line-height: 1.5;
    border-radius: 3px;
}
</style>

<!-- Include Date Range Picker JS -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize date range picker
    $('#daterange').daterangepicker({
        opens: 'left',
        autoUpdateInput: false,
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        locale: {
            cancelLabel: 'Clear',
            format: 'YYYY-MM-DD'
        }
    });

    $('#daterange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        managePaymentsTable.ajax.reload();
    });

    $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        managePaymentsTable.ajax.reload();
    });

    $('#clearDateRange').click(function() {
        $('#daterange').val('');
        managePaymentsTable.ajax.reload();
    });

    // Initialize DataTable
    var managePaymentsTable = $('#managePaymentsTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchAllPurchasePayments.php',
            'type': 'POST',
            'data': function(data) {
                data.dateRange = $('#daterange').val();
            }
        },
        'order': [[0, 'desc']],
        'columns': [
            { 
                'data': 'payment_date',
                'render': function(data) {
                    return moment(data).format('YYYY-MM-DD');
                }
            },
            { 'data': 'purchase_number' },
            { 'data': 'supplier_name' },
            { 
                'data': 'amount',
                'render': function(data) {
                    return parseFloat(data).toFixed(2);
                },
                'className': 'text-right'
            },
            { 'data': 'payment_method' },
            { 'data': 'reference_number' },
            { 'data': 'notes' },
            { 
                'data': 'payment_proof',
                'render': function(data) {
                    if (!data || data === 'NO PROOF') {
                        return '<span class="text-muted"><i class="fa fa-times"></i> No proof attached</span>';
                    }
                    
                    var fileExt = data.split('.').pop().toLowerCase();
                    var icon = fileExt === 'pdf' ? 'file-pdf-o' : 'file-image-o';
                    
                    return '<div class="btn-group btn-group-xs">' +
                           '<button class="btn btn-info" onclick="previewPaymentProof(\'' + data + '\')">' +
                           '<i class="fa fa-' + icon + '"></i> View</button>' +
                           '<a href="' + data + '" class="btn btn-success" download>' +
                           '<i class="fa fa-download"></i> Download</a>' +
                           '</div>';
                }
            },
            { 
                'data': 'created_at',
                'render': function(data) {
                    return moment(data).format('YYYY-MM-DD HH:mm:ss');
                }
            }
        ]
    });
});

// Function to preview payment proof
function previewPaymentProof(proofPath) {
    // Check if user has permission to view payments
    if (!<?php echo $canViewPayment ? 'true' : 'false'; ?>) {
        toastr.error('You do not have permission to view payment proof');
        return;
    }

    if (!proofPath || proofPath === 'NO PROOF') {
        toastr.warning('No proof available for this payment');
        return;
    }

    var fileExt = proofPath.split('.').pop().toLowerCase();
    var previewContent = '';
    
    // Set download link
    $('#downloadProof').attr('href', proofPath);
    
    if (fileExt === 'pdf') {
        previewContent = '<iframe src="' + proofPath + '" class="payment-proof-preview"></iframe>';
    } else {
        previewContent = '<img src="' + proofPath + '" class="payment-proof-preview" />';
    }
    
    $('#proofPreview').html(previewContent);
    $('#paymentProofModal').modal('show');
}

// Pass PHP permissions to JavaScript
var permissions = {
    canViewPayment: <?php echo $canViewPayment ? 'true' : 'false'; ?>
};
</script>

<?php require_once 'includes/footer.php'; ?> 