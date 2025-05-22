<?php
require_once 'includes/header.php';

// Check if user has permission to view purchases
if (!hasPermission('purchase.view')) {
    header('Location: index.php');
    exit();
}

// Get user permissions for different purchase actions
$canCreate = hasPermission('purchase.create');
$canEdit = hasPermission('purchase.edit');
$canDelete = hasPermission('purchase.delete');
$canApprove = hasPermission('purchase.approve');
$canAddPayment = hasPermission('purchase.payment.add');
$canEditPayment = hasPermission('purchase.payment.edit');
$canViewPayment = hasPermission('purchase.payment.view');
$canManageSuppliers = hasPermission('purchase.supplier.manage');
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"><i class="fa fa-shopping-cart"></i> Manage Purchase Orders</div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <?php if($canCreate) { ?>
                <div class="div-action pull-right" style="padding-bottom:20px;">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addPurchaseModal">
                        <i class="fa fa-plus"></i> Add Purchase Order
                    </button>
                </div>
                <?php } ?>

                <table class="table table-hover table-striped table-bordered" id="managePurchaseTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Purchase #</th>
                            <th>Supplier</th>
                            <th>Purchase Date</th>
                            <th>Sub Total</th>
                            <th>VAT</th>
                            <th>Grand Total</th>
                            <th>Payment Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Purchase Modal -->
<div class="modal fade" id="addPurchaseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form class="form-horizontal" id="submitPurchaseForm" action="php_action/createPurchase.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Purchase Order</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Supplier</label>
                        <div class="col-sm-8">
                            <select class="form-control selectpicker" id="supplier" name="supplier" required data-live-search="true">
                                <option value="">~~SELECT~~</option>
                            </select>
                        </div>
                        <?php if($canManageSuppliers) { ?>
                        <div class="col-sm-1">
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addSupplierModal">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                        <?php } ?>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Purchase Date</label>
                        <div class="col-sm-9">
                            <input type="date" 
                                class="form-control" 
                                id="purchase_date" 
                                name="purchase_date" 
                                value="<?php echo date('Y-m-d'); ?>"
                                required
                                pattern="\d{4}-\d{2}-\d{2}"
                                max="<?php echo date('Y-m-d'); ?>"
                                onchange="validatePurchaseDate(this)"
                            />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Warehouse</label>
                        <div class="col-sm-9">
                            <select class="form-control selectpicker" id="warehouse" name="warehouse" required data-live-search="true">
                                <option value="">~~SELECT~~</option>
                                <?php
                                $sql = "SELECT id, name FROM warehouses WHERE status = 'active'";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_array()) {
                                    echo "<option value='".$row['id']."'>".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <table class="table table-bordered" id="productTable">
                        <thead>
                            <tr>
                                <th style="width:35%">Raw Material</th>
                                <th style="width:15%">Quantity</th>
                                <th style="width:15%">Rate</th>
                                <th style="width:15%">Total</th>
                                <th style="width:5%"><button type="button" class="btn btn-default" onclick="addRow()"><i class="fa fa-plus"></i></button></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="row1">
                                <td>
                                    <select class="form-control selectpicker" name="productName[]" id="productName1" required data-live-search="true">
                                        <option value="">~~SELECT~~</option>
                                    </select>
                                </td>
                                <td><input type="number" class="form-control" name="quantity[]" id="quantity1" onkeyup="getTotal(1)" min="1" required /></td>
                                <td><input type="number" class="form-control" name="rate[]" id="rate1" onkeyup="getTotal(1)" min="0" step="0.01" required /></td>
                                <td><input type="text" class="form-control" name="total[]" id="total1" disabled /></td>
                                <td><button type="button" class="btn btn-default" onclick="removeRow(1)"><i class="fa fa-trash"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Sub Total</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="subTotal" name="subTotal" disabled />
                            <input type="hidden" class="form-control" id="subTotalValue" name="subTotalValue" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">VAT (%)</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="vat" name="vat" onkeyup="calculateGrandTotal()" min="0" step="0.01" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Withholding Tax</label>
                        <div class="col-sm-9">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" id="withholding_enabled" name="withholding_enabled" onchange="calculateGrandTotal()"> Enable Withholding Tax
                                </label>
                            </div>
                            <input type="number" class="form-control" id="withholding_amount" name="withholding_amount" onkeyup="calculateGrandTotal()" min="0" step="0.01" disabled />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Grand Total</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="grandTotal" name="grandTotal" disabled />
                            <input type="hidden" class="form-control" id="grandTotalValue" name="grandTotalValue" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Note</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="note" name="note" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createPurchaseBtn">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitSupplierForm" action="php_action/createSupplier.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Quick Add Supplier</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Company Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="companyName" name="companyName" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Contact Person</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="contactPerson" name="contactPerson" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Phone</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="phone" name="phone" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Address</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createSupplierBtn">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Purchase Modal -->
<div class="modal fade" id="editPurchaseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form class="form-horizontal" id="editPurchaseForm" action="php_action/editPurchase.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Purchase Order</h4>
                </div>
                <div class="modal-body">
                    <div id="edit-purchase-messages"></div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Supplier</label>
                        <div class="col-sm-8">
                            <select class="form-control selectpicker" id="editSupplier" name="supplier" required data-live-search="true">
                                <option value="">~~SELECT~~</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Purchase Date</label>
                        <div class="col-sm-9">
                            <input type="date" 
                                class="form-control" 
                                id="editPurchaseDate" 
                                name="purchaseDate" 
                                required
                                pattern="\d{4}-\d{2}-\d{2}"
                                max="<?php echo date('Y-m-d'); ?>"
                                onchange="validatePurchaseDate(this)"
                            />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Warehouse</label>
                        <div class="col-sm-9">
                            <select class="form-control selectpicker" id="editWarehouse" name="warehouse" required data-live-search="true">
                                <option value="">~~SELECT~~</option>
                                <?php
                                $sql = "SELECT id, name FROM warehouses WHERE status = 'active'";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_array()) {
                                    echo "<option value='".$row['id']."'>".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <table class="table table-bordered" id="editProductTable">
                        <thead>
                            <tr>
                                <th style="width:35%">Raw Material</th>
                                <th style="width:15%">Quantity</th>
                                <th style="width:15%">Rate</th>
                                <th style="width:15%">Total</th>
                                <th style="width:5%"><button type="button" class="btn btn-default" onclick="addEditRow()"><i class="fa fa-plus"></i></button></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Sub Total</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editSubTotal" name="subTotal" disabled />
                            <input type="hidden" class="form-control" id="editSubTotalValue" name="subTotalValue" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">VAT (%)</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editVat" name="vat" onkeyup="calculateEditGrandTotal()" min="0" step="0.01" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Withholding Tax</label>
                        <div class="col-sm-9">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" id="editWithholdingEnabled" name="withholding_enabled" onchange="calculateEditGrandTotal()"> Enable Withholding Tax
                                </label>
                            </div>
                            <input type="number" class="form-control" id="editWithholdingAmount" name="withholding_amount" onkeyup="calculateEditGrandTotal()" min="0" step="0.01" disabled />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Grand Total</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editGrandTotal" name="grandTotal" disabled />
                            <input type="hidden" class="form-control" id="editGrandTotalValue" name="grandTotalValue" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Note</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="editNote" name="note" rows="3"></textarea>
                        </div>
                    </div>
                    <input type="hidden" name="purchaseId" id="editPurchaseId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="editPurchaseBtn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Purchase Modal -->
<div class="modal fade" id="removePurchaseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-trash"></i> Remove Purchase</h4>
            </div>
            <div class="modal-body">
                <p>Do you really want to remove this purchase?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="removePurchaseBtn">Save changes</button>
            </div>
        </div>
    </div>
</div>

<!-- View Purchase Modal -->
<div class="modal fade" id="viewPurchaseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-eye"></i> Purchase Details</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table">
                            <tr>
                                <th>Purchase Number</th>
                                <td id="view_purchase_number"></td>
                            </tr>
                            <tr>
                                <th>Supplier</th>
                                <td id="view_supplier"></td>
                            </tr>
                            <tr>
                                <th>Purchase Date</th>
                                <td id="view_purchase_date"></td>
                            </tr>
                            <tr>
                                <th>Warehouse</th>
                                <td id="view_warehouse"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table">
                            <tr>
                                <th>Sub Total</th>
                                <td id="view_sub_total"></td>
                            </tr>
                            <tr>
                                <th>VAT Amount</th>
                                <td id="view_vat_amount"></td>
                            </tr>
                            <tr>
                                <th>Withholding Tax</th>
                                <td id="view_withholding"></td>
                            </tr>
                            <tr>
                                <th>Grand Total</th>
                                <td id="view_grand_total"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <h4>Purchase Items</h4>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Raw Material</th>
                                    <th>Quantity</th>
                                    <th>Rate</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="purchaseItemsTable">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <h4>Note</h4>
                        <p id="view_note"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="paymentForm" action="php_action/updatePurchasePayment.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-money"></i> Update Payment Status</h4>
                </div>
                <div class="modal-body">
                    <div id="payment-messages"></div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Purchase Number</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="payment_purchase_number" readonly />
                            <input type="hidden" name="purchase_number" id="purchase_number" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Grand Total</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="payment_grand_total" readonly />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Current Paid Amount</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="current_paid_amount" readonly />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Payment Status</label>
                        <div class="col-sm-8">
                            <select class="form-control" id="payment_status" name="payment_status" required>
                                <option value="">~~SELECT~~</option>
                                <option value="unpaid">Unpaid</option>
                                <option value="partially_paid">Partially Paid</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="paid_amount_group" style="display:none;">
                        <label class="col-sm-4 control-label">Paid Amount</label>
                        <div class="col-sm-8">
                            <input type="number" class="form-control" id="paid_amount" name="paid_amount" step="0.01" min="0" />
                            <small class="help-block">Amount must be greater than current paid amount and less than grand total</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Payment Date</label>
                        <div class="col-sm-8">
                            <input type="date" class="form-control" id="payment_date" name="payment_date" required max="<?php echo date('Y-m-d'); ?>" />
                            <small class="help-block">Payment date cannot be in the future</small>
                        </div>
                    </div>
                    <div class="form-group payment-details">
                        <label class="col-sm-4 control-label">Payment Method</label>
                        <div class="col-sm-8">
                            <select class="form-control" id="payment_method" name="payment_method">
                                <option value="">~~SELECT~~</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group payment-details">
                        <label class="col-sm-4 control-label">Reference Number</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="Check/Transaction number" />
                        </div>
                    </div>
                    <div class="form-group payment-details">
                        <label class="col-sm-4 control-label">Payment Proof</label>
                        <div class="col-sm-8">
                            <input type="file" class="form-control" id="payment_proof" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" />
                            <small class="help-block">Allowed file types: JPG, JPEG, PNG, PDF</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Notes</label>
                        <div class="col-sm-8">
                            <textarea class="form-control" id="payment_notes" name="payment_notes" rows="3"></textarea>
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

<!-- Payment History Modal -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-history"></i> Payment History</h4>
            </div>
            <div class="modal-body">
                <div class="payment-summary">
                    <h4>Payment Summary</h4>
                    <table class="table table-bordered">
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Amount</th>
                        </tr>
                        <tr>
                            <td>Grand Total</td>
                            <td class="text-right" id="summary_grand_total">0.00</td>
                        </tr>
                        <tr>
                            <td>Total Paid</td>
                            <td class="text-right text-success" id="summary_total_paid">0.00</td>
                        </tr>
                        <tr>
                            <td>Remaining Balance</td>
                            <td class="text-right text-danger" id="summary_remaining">0.00</td>
                        </tr>
                        
                    </table>
                    
                </div>
                <h4>Payment Records</h4>
                <table class="table table-bordered table-striped" id="paymentHistoryTable">
                    <thead>
                        <tr>
                            <th style="width:12%">Date</th>
                            <th style="width:13%">Amount</th>
                            <th style="width:13%">Method</th>
                            <th style="width:15%">Reference</th>
                            <th style="width:22%">Notes</th>
                            <th style="width:25%">Proof</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
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
/* Add these styles to your existing CSS */
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
.payment-history-table th {
    background-color: #f5f5f5;
    font-weight: 600;
}
.payment-history-table td {
    vertical-align: middle !important;
}
#paymentHistoryTable .btn {
    margin: 0 2px;
}
</style>

<script src="custom/js/purchase.js"></script>

<script>
function validatePurchaseDate(input) {
    var selectedDate = new Date(input.value);
    var today = new Date();
    today.setHours(0,0,0,0);
    
    if (selectedDate > today) {
        toastr.error('Purchase date cannot be in the future');
        input.value = today.toISOString().split('T')[0];
    }
}
</script>

<script type="text/javascript">
// Pass permissions to JavaScript
var permissions = {
    canEdit: <?php echo json_encode($canEdit); ?>,
    canDelete: <?php echo json_encode($canDelete); ?>,
    canApprove: <?php echo json_encode($canApprove); ?>,
    canAddPayment: <?php echo json_encode($canAddPayment); ?>,
    canEditPayment: <?php echo json_encode($canEditPayment); ?>,
    canViewPayment: <?php echo json_encode($canViewPayment); ?>,
    canManageSuppliers: <?php echo json_encode($canManageSuppliers); ?>
};
</script>

<script>
// Add this to your existing JavaScript
$(document).ready(function() {
    // Payment date validation
    $('#payment_date').on('change', function() {
        var selectedDate = new Date(this.value);
        var today = new Date();
        today.setHours(0,0,0,0);
        
        if (selectedDate > today) {
            toastr.error('Payment date cannot be in the future');
            this.value = today.toISOString().split('T')[0];
        }
    });

    // File input validation
    $('#payment_proof').on('change', function() {
        var file = this.files[0];
        if (file) {
            var fileType = file.type;
            var validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!validTypes.includes(fileType)) {
                toastr.error('Invalid file type. Only JPG, JPEG, PNG, and PDF files are allowed.');
                this.value = '';
            }
            
            // Check file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                toastr.error('File size too large. Maximum size is 5MB.');
                this.value = '';
            }
        }
    });
});
</script>

<script>
// Function to show payment history
function showPaymentHistory(purchaseNumber, grandTotal) {
    // Check if user has permission to view payments
    if (!permissions.canViewPayment) {
        toastr.error('You do not have permission to view payment history');
        return;
    }

    // Show loading state
    $('#paymentHistoryTable tbody').html('<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading payment history...</td></tr>');
    $('#paymentHistoryModal').modal('show');
    
    $.ajax({
        url: 'php_action/fetchPurchasePayments.php',
        type: 'post',
        data: {
            purchaseNumber: purchaseNumber
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var tbody = '';
                var totalPaid = 0;

                response.payments.forEach(function(payment) {
                    totalPaid += parseFloat(payment.amount);
                    
                    // Handle payment proof display
                    var proofCell;
                    if (!payment.payment_proof || payment.payment_proof === 'NO PROOF') {
                        proofCell = '<span class="text-muted"><i class="fa fa-times"></i> No proof attached</span>';
                    } else {
                        var fileExt = payment.payment_proof.split('.').pop().toLowerCase();
                        var icon = fileExt === 'pdf' ? 'file-pdf-o' : 'file-image-o';
                        proofCell = '<div class="btn-group btn-group-xs">' +
                                  '<button class="btn btn-info" onclick="previewPaymentProof(\'' + payment.payment_proof + '\')">' +
                                  '<i class="fa fa-' + icon + '"></i> View</button>' +
                                  '<a href="' + payment.payment_proof + '" class="btn btn-success" download>' +
                                  '<i class="fa fa-download"></i> Download</a>' +
                                  '</div>';
                    }

                    tbody += '<tr>' +
                        '<td>' + payment.payment_date + '</td>' +
                        '<td class="text-right">' + formatCurrency(payment.amount) + '</td>' +
                        '<td>' + payment.payment_method + '</td>' +
                        '<td>' + payment.reference_number + '</td>' +
                        '<td>' + payment.notes + '</td>' +
                        '<td>' + proofCell + '</td>' +
                        '</tr>';
                });

                $('#paymentHistoryTable tbody').html(tbody);
                
                // Update payment summary with formatted numbers
                $('#summary_grand_total').text(formatCurrency(grandTotal));
                $('#summary_total_paid').text(formatCurrency(totalPaid));
                $('#summary_remaining').text(formatCurrency(parseFloat(grandTotal) - totalPaid));
            } else {
                $('#paymentHistoryTable tbody').html(
                    '<tr><td colspan="6" class="text-center">' +
                    '<i class="fa fa-info-circle"></i> ' +
                    (response.messages || 'No payment records found') +
                    '</td></tr>'
                );
                
                // Clear payment summary
                $('#summary_grand_total').text(formatCurrency(grandTotal));
                $('#summary_total_paid').text(formatCurrency(0));
                $('#summary_remaining').text(formatCurrency(parseFloat(grandTotal)));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            $('#paymentHistoryTable tbody').html(
                '<tr><td colspan="6" class="text-center text-danger">' +
                '<i class="fa fa-exclamation-triangle"></i> ' +
                'Error loading payment history. Please try again.' +
                '</td></tr>'
            );
            toastr.error('Error fetching payment history');
        }
    });
}

// Function to preview payment proof
function previewPaymentProof(proofPath) {
    // Check if user has permission to view payments
    if (!permissions.canViewPayment) {
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

// Close proof preview when parent modal is closed
$('#paymentHistoryModal').on('hidden.bs.modal', function () {
    $('#paymentProofModal').modal('hide');
});

// Helper function to format currency
function formatCurrency(amount) {
    return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}
</script>

<?php require_once 'includes/footer.php'; ?> 