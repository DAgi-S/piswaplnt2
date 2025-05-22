<?php 
require_once 'php_action/core.php';
require_once 'includes/header.php';
?>

<style>
    /* Full width container for sales detail page only */
    .container, .container-fluid {
        width: 100% !important;
        max-width: 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Panel takes full width */
    .panel {
        margin-bottom: 0;
        border-radius: 0;
        width: 100%;
    }
    
    /* Professional table styling */
    #salesDetailTable {
        font-size: 10px !important;
        color: #333;
        width: 100% !important;
    }
    
    #salesDetailTable thead th {
        background-color: #1976D2 !important;
        color: white !important;
        font-weight: 600;
        border: none !important;
        padding: 10px 8px;
        vertical-align: middle;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 1;
    }
    
    #salesDetailTable tbody td {
        padding: 8px;
        vertical-align: middle;
        border-bottom: 1px solid #e0e0e0;
    }
    
    #salesDetailTable tbody tr:hover {
        background-color: #f5f5f5;
    }
    
    .table-striped > tbody > tr:nth-of-type(odd) {
        background-color: #fafafa;
    }
    
    /* Status labels */
    .label {
        font-weight: normal;
        font-size: 10px;
        padding: 3px 8px;
        border-radius: 3px;
    }
    
    .label-pending { background-color: #FFA726; }
    .label-completed { background-color: #66BB6A; }
    .label-success { background-color: #43A047; }
    .label-warning { background-color: #FB8C00; }
    .label-danger { background-color: #E53935; }
    
    /* Action buttons */
    .btn-group .btn {
        padding: 3px 8px;
        font-size: 10px;
    }
    
    /* DataTable controls */
    .dataTables_wrapper {
        padding: 0 15px;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        font-size: 10px;
        padding: 8px 0;
    }
    
    /* Numeric columns alignment */
    .text-right {
        text-align: right !important;
    }

    /* Export buttons styling */
    .dt-buttons {
        margin-bottom: 15px;
        padding-top: 15px;
    }

    .dt-buttons .btn {
        padding: 4px 8px;
        font-size: 10px;
        margin-right: 5px;
    }

    /* Search box styling */
    .dataTables_filter input {
        margin-left: 5px;
        padding: 3px 6px;
        border: 1px solid #ddd;
        border-radius: 3px;
    }

    /* Responsive table */
    @media screen and (max-width: 767px) {
        #salesDetailTable {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            float: none;
            text-align: left;
        }

        .dataTables_wrapper .dataTables_filter {
            margin-top: 0.5em;
        }
    }

    /* Spinner animation for loading indicators */
    .glyphicon-spin {
        animation: spin 1s infinite linear;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="glyphicon glyphicon-list-alt"></i> Detailed Sales Report
                </div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <table class="table table-hover table-striped table-bordered" id="salesDetailTable">
                    <thead>
                        <tr>
                            <th>Sale #</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Products</th>
                            <th class="text-right">Sub Total</th>
                            <th class="text-right">VAT (15%)</th>
                            <th class="text-right">WHT (2%)</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Grand Total</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Balance</th>
                            <th>Payment Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Sale Details Modal -->
<div class="modal fade" id="viewSaleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-eye-open"></i> Sale Details</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Sale Information</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 30%;">Sale Number</th>
                                <td id="view_sale_number"></td>
                            </tr>
                            <tr>
                                <th>Client</th>
                                <td id="view_client_name"></td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td id="view_sale_date"></td>
                            </tr>
                            <tr>
                                <th>Created By</th>
                                <td id="view_created_by"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h4>Payment Information</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 30%;">Status</th>
                                <td id="view_payment_status"></td>
                            </tr>
                            <tr>
                                <th>Total Amount</th>
                                <td id="view_total_amount"></td>
                            </tr>
                            <tr>
                                <th>Paid Amount</th>
                                <td id="view_paid_amount"></td>
                            </tr>
                            <tr>
                                <th>Balance</th>
                                <td id="view_balance"></td>
                            </tr>
                            <tr>
                                <th>Actions</th>
                                <td>
                                    <a href="#" class="btn btn-primary btn-xs" id="update_payment_btn">
                                        <i class="glyphicon glyphicon-plus"></i> Add Payment
                                    </a>
                                    <a href="#" class="btn btn-info btn-xs" id="view_payments_btn" target="_blank">
                                        <i class="glyphicon glyphicon-list"></i> All Payments
                                    </a>
                                    <a href="#" class="btn btn-warning btn-xs" id="adjust_overpayment_btn" style="display:none;">
                                        <i class="glyphicon glyphicon-transfer"></i> Adjust Overpayment
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <h5>Product Details</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="productDetailsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Sub Total:</strong></td>
                                <td id="view_subtotal"></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right"><strong>VAT (15%):</strong></td>
                                <td id="view_vat"></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right"><strong>WHT (2%):</strong></td>
                                <td id="view_wht"></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Discount:</strong></td>
                                <td id="view_discount"></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Grand Total:</strong></td>
                                <td id="view_grand_total"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <h5>Payment History</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="paymentHistoryTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Notes</th>
                                <th>Payment Proof</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="printSaleBtn">
                    <i class="glyphicon glyphicon-print"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Update Payment Modal -->
<div class="modal fade" id="updatePaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-usd"></i> Add New Payment</h4>
            </div>
            <form id="addPaymentForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Sale Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%;">Sale Number</th>
                                    <td id="update_sale_number"></td>
                                </tr>
                                <tr>
                                    <th>Client</th>
                                    <td id="update_client_name"></td>
                                </tr>
                                <tr>
                                    <th>Date</th>
                                    <td id="update_sale_date"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Payment Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%;">Status</th>
                                    <td id="update_payment_status"></td>
                                </tr>
                                <tr>
                                    <th>Total Amount</th>
                                    <td id="update_total_amount"></td>
                                </tr>
                                <tr>
                                    <th>Paid Amount</th>
                                    <td id="update_paid_amount"></td>
                                </tr>
                                <tr>
                                    <th>Balance</th>
                                    <td id="update_balance"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Add New Payment</h5>
                            <div class="form-group">
                                <label for="select_account">Account <span class="text-danger">*</span></label>
                                <select class="form-control" id="select_account" name="account_id" required>
                                    <!-- Will be populated dynamically via AJAX -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-control" id="payment_method" name="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Check">Check</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="PayPal">PayPal</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_amount">Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="payment_amount" name="amount" required step="0.01" min="0.01">
                                <small class="text-muted">Maximum amount: <span id="max_amount">0.00</span></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="reference_number">Reference Number</label>
                                <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="Reference number, transaction ID, etc.">
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_proof">Payment Proof (Image/PDF)</label>
                                <input type="file" id="payment_proof" name="payment_proof" accept="image/*,application/pdf">
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_notes">Notes</label>
                                <textarea class="form-control" id="payment_notes" name="notes" rows="3" placeholder="Additional notes or comments"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="savePaymentBtn">
                        <i class="glyphicon glyphicon-plus"></i> Add Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Adjust Overpayment Modal -->
<div class="modal fade" id="adjustOverpaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-transfer"></i> Adjust Overpayment</h4>
            </div>
            <form id="adjustOverpaymentForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-warning">
                                <p><strong>Warning:</strong> This sale has been overpaid. Use this form to adjust the overpayment.</p>
                            </div>
                            
                            <h5>Sale Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%;">Sale Number</th>
                                    <td id="overpayment_sale_number"></td>
                                </tr>
                                <tr>
                                    <th>Client</th>
                                    <td id="overpayment_client_name"></td>
                                </tr>
                                <tr>
                                    <th>Total Amount</th>
                                    <td id="overpayment_total_amount"></td>
                                </tr>
                                <tr>
                                    <th>Paid Amount</th>
                                    <td id="overpayment_paid_amount"></td>
                                </tr>
                                <tr>
                                    <th>Overpayment</th>
                                    <td id="overpayment_amount" class="text-danger"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Adjustment Details</h5>
                            <div class="form-group">
                                <label for="adjustment_type">Adjustment Type <span class="text-danger">*</span></label>
                                <select class="form-control" id="adjustment_type" name="adjustment_type" required>
                                    <option value="refund">Refund to Client</option>
                                    <option value="adjustment">Balance Adjustment</option>
                                </select>
                                <small class="text-muted">Select "Refund" if you've returned money to the client, or "Adjustment" to correct the records.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="adjustment_amount">Adjustment Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="adjustment_amount" name="adjustment_amount" required step="0.01" min="0.01">
                                <small class="text-muted">Maximum amount: <span id="max_adjustment">0.00</span></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="adjustment_notes">Notes</label>
                                <textarea class="form-control" id="adjustment_notes" name="notes" rows="3" placeholder="Reason for adjustment or reference details"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="saveAdjustmentBtn">
                        <i class="glyphicon glyphicon-ok"></i> Process Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Required JavaScript dependencies -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="custom/js/sales_detail.js"></script>

<script>
// Initialize print button in modal
$(document).ready(function() {
    $('#printSaleBtn').on('click', function() {
        // Get the sale ID from the current view
        var saleId = $(this).data('sale-id');
        if (saleId) {
            printSale(saleId);
        } else {
            alert('Sale ID not found. Please try viewing the sale details again.');
        }
    });
    
    // When the modal is shown, make sure the print button has the correct sale ID
    $('#viewSaleModal').on('shown.bs.modal', function() {
        var currentSaleId = $('#viewSaleModal').data('sale-id');
        if (currentSaleId) {
            $('#printSaleBtn').data('sale-id', currentSaleId);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 
</body>
</html> 