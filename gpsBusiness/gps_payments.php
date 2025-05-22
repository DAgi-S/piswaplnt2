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
                <div class="page-heading"><i class="glyphicon glyphicon-edit"></i> Manage Payments</div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <div class="div-action pull pull-right" style="padding-bottom:20px;">
                    <button class="btn btn-success button1" data-toggle="modal" id="addPaymentModalBtn" data-target="#addPaymentModal">
                        <i class="glyphicon glyphicon-plus-sign"></i> Add Payment
                    </button>
                </div>

                <table class="table" id="managePaymentTable">
                    <thead>
                        <tr>
                            <th>Payment #</th>
                            <th>Order #</th>
                            <th>Payment Date</th>
                            <th>Payment Type</th>
                            <th>Paid By</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>Rate</th>
                            <th>Bank</th>
                            <th>Balance</th>
                            <th style="width:15%;">Options</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="addPaymentForm" action="php_action/createGpsPayment.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add New Payment</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-sm-4">Payment Number</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="paymentNumber" name="payment_number" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">GPS Order</label>
                                <div class="col-sm-8">
                                    <select class="form-control select2" name="gps_order_id" required>
                                        <option value="">Select Order</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Payment Date</label>
                                <div class="col-sm-8">
                                    <input type="date" class="form-control" id="paymentDate" name="payment_date" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Payment Type</label>
                                <div class="col-sm-8">
                                    <select class="form-control" name="payment_type" required>
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Check">Check</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-sm-4">Amount</label>
                                <div class="col-sm-8">
                                    <input type="number" step="0.01" class="form-control" name="paid_amount" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Currency</label>
                                <div class="col-sm-8">
                                    <select class="form-control" name="currency" id="currency" required>
                                        <option value="ETB">ETB</option>
                                        <option value="USD">USD</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" id="rateGroup" style="display:none;">
                                <label class="control-label col-sm-4">Rate</label>
                                <div class="col-sm-8">
                                    <input type="number" step="0.01" class="form-control" name="rate" id="rate">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Bank</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="bank">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label col-sm-2">Paid By</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="paid_by" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2">Deposited To</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="deposited_to">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2">Payment Image</label>
                                <div class="col-sm-10">
                                    <input type="file" class="form-control" name="payment_image" accept="image/*" onchange="previewImage(this)">
                                    <img id="imagePreview" class="payment-image-preview" style="display:none;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Payment Modal -->
<div class="modal fade" id="viewPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-eye"></i> Payment Details</h4>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr>
                        <th>Payment Number</th>
                        <td id="viewPaymentNumber"></td>
                    </tr>
                    <tr>
                        <th>Order Number</th>
                        <td id="viewOrderNumber"></td>
                    </tr>
                    <tr>
                        <th>Payment Date</th>
                        <td id="viewPaymentDate"></td>
                    </tr>
                    <tr>
                        <th>Payment Type</th>
                        <td id="viewPaymentType"></td>
                    </tr>
                    <tr>
                        <th>Paid By</th>
                        <td id="viewPaidBy"></td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td id="viewAmount"></td>
                    </tr>
                    <tr>
                        <th>Currency</th>
                        <td id="viewCurrency"></td>
                    </tr>
                    <tr>
                        <th>Exchange Rate</th>
                        <td id="viewRate"></td>
                    </tr>
                    <tr>
                        <th>Bank</th>
                        <td id="viewBank"></td>
                    </tr>
                    <tr>
                        <th>Deposited To</th>
                        <td id="viewDepositedTo"></td>
                    </tr>
                    <tr>
                        <th>Payment Image</th>
                        <td id="viewPaymentImage">
                            <div id="viewImagePreview"></div>
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

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editPaymentForm" action="php_action/updateGpsPayment.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Payment</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="payment_id" id="editPaymentId">
                    <input type="hidden" name="old_image_location" id="editOldImageLocation">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-sm-4">Payment Number</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="editPaymentNumber" name="payment_number" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">GPS Order</label>
                                <div class="col-sm-8">
                                    <select class="form-control select2" name="gps_order_id" id="editGpsOrderId" required>
                                        <option value="">Select Order</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Payment Date</label>
                                <div class="col-sm-8">
                                    <input type="date" class="form-control" id="editPaymentDate" name="payment_date" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Payment Type</label>
                                <div class="col-sm-8">
                                    <select class="form-control" name="payment_type" id="editPaymentType" required>
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Check">Check</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-sm-4">Amount</label>
                                <div class="col-sm-8">
                                    <input type="number" step="0.01" class="form-control" name="paid_amount" id="editPaidAmount" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Currency</label>
                                <div class="col-sm-8">
                                    <select class="form-control" name="currency" id="editCurrency" required>
                                        <option value="ETB">ETB</option>
                                        <option value="USD">USD</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" id="editRateGroup">
                                <label class="control-label col-sm-4">Rate</label>
                                <div class="col-sm-8">
                                    <input type="number" step="0.01" class="form-control" name="rate" id="editRate">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-4">Bank</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="bank" id="editBank">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label col-sm-2">Paid By</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="paid_by" id="editPaidBy" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2">Deposited To</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="deposited_to" id="editDepositedTo">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2">Payment Image</label>
                                <div class="col-sm-10">
                                    <input type="file" class="form-control" name="payment_image" accept="image/*,application/pdf" onchange="previewEditImage(this)">
                                    <img id="editImagePreview" class="payment-image-preview" style="display:none;">
                                    <div id="currentImageDisplay"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include Moment.js for date formatting -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<!-- Include DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<!-- Custom JS -->
<script src="js/gps_payments.js"></script>

<script>
    // Image preview function
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview')
                    .attr('src', e.target.result)
                    .show();
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function previewEditImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#editImagePreview')
                    .attr('src', e.target.result)
                    .show();
                $('#currentImageDisplay').hide();
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?> 