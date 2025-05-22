<?php 
require_once 'php_action/core.php';
require_once 'php_action/functions.php';
require_once 'includes/header.php';
?>

<style>
    /* Professional table styling */
    #managePurchaseTable {
        font-size: 10px !important;
        color: #333;
    }
    
    #managePurchaseTable thead th {
        background-color: #1976D2 !important;
        color: white !important;
        font-weight: 600;
        border: none !important;
        padding: 10px 8px;
        vertical-align: middle;
    }
    
    #managePurchaseTable tbody td {
        padding: 8px;
        vertical-align: middle;
        border-bottom: 1px solid #e0e0e0;
    }
    
    #managePurchaseTable tbody tr:hover {
        background-color: #f5f5f5;
    }
    
    .table-striped > tbody > tr:nth-of-type(odd) {
        background-color: #fafafa;
    }
    
    /* Status labels */
    .label-pending {
        background-color: #FFA726;
        font-weight: normal;
        font-size: 10px;
        padding: 3px 8px;
    }
    
    .label-completed {
        background-color: #66BB6A;
        font-weight: normal;
        font-size: 10px;
        padding: 3px 8px;
    }
    
    /* Payment status labels */
    .label-success {
        background-color: #43A047;
        font-weight: normal;
        font-size: 10px;
        padding: 3px 8px;
    }
    
    .label-warning {
        background-color: #FB8C00;
        font-weight: normal;
        font-size: 10px;
        padding: 3px 8px;
    }
    
    .label-danger {
        background-color: #E53935;
        font-weight: normal;
        font-size: 10px;
        padding: 3px 8px;
    }
    
    /* Action buttons */
    .btn-group .btn {
        padding: 3px 8px;
        font-size: 10px;
    }
    
    .dropdown-menu {
        font-size: 10px;
        min-width: 120px;
    }
    
    .dropdown-menu > li > a {
        padding: 4px 12px;
    }
    
    /* DataTable controls */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        font-size: 10px;
    }
    
    /* Add Purchase button */
    .div-action .btn {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    /* Numeric columns alignment */
    .text-right {
        text-align: right !important;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"><i class="glyphicon glyphicon-shopping-cart"></i> Manage Purchases</div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <div class="div-action pull-right" style="padding-bottom:20px;">
                    <button class="btn btn-success" data-toggle="modal" data-target="#addPurchaseModal">
                        <i class="glyphicon glyphicon-plus"></i> Add Purchase
                    </button>
                </div>

                <table class="table table-hover table-striped table-bordered" id="managePurchaseTable">
                    <thead>
                        <tr>
                            <th>Purchase #</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th class="text-right">Sub Total</th>
                            <th class="text-right">VAT (15%)</th>
                            <th class="text-right">WHT (2%)</th>
                            <th class="text-right">Grand Total</th>
                            <th>Payment</th>
                            <th style="width: 90px;">Action</th>
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
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading"><i class="glyphicon glyphicon-shopping-cart"></i> Add Purchase</div>
                </div>
                <div class="panel-body">
                    <div id="messages"></div>

                    <form id="purchaseForm" class="form-horizontal">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Supplier</label>
                            <div class="col-sm-4">
                                <select class="form-control" id="supplier" name="supplier" required>
                                    <option value="">Select Supplier</option>
                                    <?php
                                    $sql = "SELECT id, company_name FROM suppliers WHERE active = 1";
                                    $result = $connect->query($sql);
                                    while($row = $result->fetch_array()) {
                                        echo "<option value='".$row['id']."'>".$row['company_name']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <label class="col-sm-2 control-label">Warehouse</label>
                            <div class="col-sm-4">
                                <select class="form-control" id="warehouse" name="warehouse" required>
                                    <option value="">Select Warehouse</option>
                                    <?php
                                    $sql = "SELECT id, name FROM warehouses WHERE status = 'active'";
                                    $result = $connect->query($sql);
                                    while($row = $result->fetch_array()) {
                                        echo "<option value='".$row['id']."'>".$row['name']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <label class="col-sm-2 control-label">Purchase Date</label>
                            <div class="col-sm-4">
                                <input type="date" class="form-control" id="purchaseDate" name="purchaseDate" 
                                    value="<?php echo date('Y-m-d'); ?>"
                                    required>
                            </div>
                        </div>

                        <table class="table table-bordered table-hover" id="purchaseItems">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Rate</th>
                                    <th>Amount</th>
                                    <th>
                                        <button type="button" class="btn btn-primary btn-sm" id="addItemBtn">
                                            <i class="glyphicon glyphicon-plus"></i>
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select class="form-control product-select" name="productId[]" required>
                                            <option value="">Select Product</option>
                                            <?php
                                            $sql = "SELECT product_id, name FROM products WHERE status = 'active'";
                                            $result = $connect->query($sql);
                                            while($row = $result->fetch_array()) {
                                                echo "<option value='".$row['product_id']."'>".$row['name']."</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control quantity" name="quantity[]" min="0.01" step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control rate" name="rate[]" min="0.01" step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control amount" name="amount[]" readonly>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm removeItem">
                                            <i class="glyphicon glyphicon-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Note</label>
                                    <div class="col-sm-8">
                                        <textarea class="form-control" id="note" name="note" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Sub Total</label>
                                    <div class="col-sm-8">
                                        <input type="number" class="form-control" id="subTotal" name="subTotal" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">VAT (15%)</label>
                                    <div class="col-sm-8">
                                        <input type="number" class="form-control" id="vat" name="vat" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Withholding Tax</label>
                                    <div class="col-sm-8">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" id="withholdingTaxEnabled" name="withholdingTaxEnabled">
                                                Enable 2% Withholding Tax
                                            </label>
                                        </div>
                                        <input type="number" class="form-control" id="withholdingAmount" name="withholdingAmount" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Grand Total</label>
                                    <div class="col-sm-8">
                                        <input type="number" class="form-control" id="grandTotal" name="grandTotal" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" class="btn btn-success" id="saveBtn">
                                    <i class="glyphicon glyphicon-save"></i> Save Purchase
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
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
                    <div id="add-supplier-messages"></div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Company Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="companyName" name="companyName" placeholder="Company Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Contact Person</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="contactPerson" name="contactPerson" placeholder="Contact Person">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Email</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Phone</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="Phone">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Address</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="address" name="address" placeholder="Address" rows="3"></textarea>
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
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="glyphicon glyphicon-edit"></i> Edit Purchase
                </h4>
            </div>
            <div class="modal-body">
                <!-- Form will be loaded here dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Remove Purchase Modal -->
<div class="modal fade" id="removePurchaseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-trash"></i> Remove Purchase</h4>
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

<!-- Print Purchase Modal -->
<div class="modal fade" id="printPurchaseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-print"></i> Print Purchase</h4>
            </div>
            <div class="modal-body" id="printPurchaseBody">
                <!-- Print content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Partial Payment Modal -->
<div class="modal fade" id="partialPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Add Partial Payment</h4>
            </div>
            <form id="partialPaymentForm">
                <div class="modal-body">
                    <input type="hidden" id="purchaseId" name="purchaseId">
                    
                    <div class="form-group">
                        <label for="paymentDate">Payment Date</label>
                        <input type="date" class="form-control" id="paymentDate" name="paymentDate" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="paymentAmount">Amount</label>
                        <input type="number" step="0.01" class="form-control" id="paymentAmount" name="paymentAmount" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="paymentMethod">Payment Method</label>
                        <select class="form-control" id="paymentMethod" name="paymentMethod" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="referenceNumber">Reference Number</label>
                        <input type="text" class="form-control" id="referenceNumber" name="referenceNumber">
                    </div>
                    
                    <div class="form-group">
                        <label for="paymentNotes">Notes</label>
                        <textarea class="form-control" id="paymentNotes" name="paymentNotes" rows="3"></textarea>
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
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Payment History</h4>
            </div>
            <div class="modal-body" id="paymentHistoryBody">
                <!-- Payment history will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="custom/js/purchase.js"></script>
<script src="custom/js/purchase-edit.js"></script>

<?php require_once 'includes/footer.php'; ?> 