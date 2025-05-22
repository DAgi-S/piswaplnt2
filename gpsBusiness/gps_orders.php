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
                <div class="page-heading"><i class="fas fa-shopping-cart"></i> GPS Orders</div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <div class="div-action pull-right" style="padding-bottom:20px;">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addGpsOrderModal">
                        <i class="fas fa-plus"></i> Add Order
                    </button>
                </div>

                <table class="table table-hover table-striped table-bordered" id="gpsOrdersTable">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Order Date</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th>Total Price</th>
                            <th>Credit Status</th>
                            <th>Credit Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add GPS Order Modal -->
<div class="modal fade" id="addGpsOrderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitGpsOrderForm" action="php_action/createGpsOrder.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Order</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Order Number</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="orderNumber" name="orderNumber" placeholder="Order Number" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Order Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="orderDate" name="orderDate" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Unit Price</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="unitPrice" name="unitPrice" placeholder="Unit Price" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Quantity</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="quantity" name="quantity" placeholder="Quantity" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Total Price</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="totalPrice" name="totalPrice" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Has Credit</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="hasCredit" name="hasCredit" required>
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="creditAmountGroup" style="display: none;">
                        <label class="col-sm-3 control-label">Credit Amount</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="creditAmount" name="creditAmount" placeholder="Credit Amount">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit GPS Order Modal -->
<div class="modal fade" id="editGpsOrderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editGpsOrderForm" action="php_action/editGpsOrder.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Order</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Order Number</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editOrderNumber" name="editOrderNumber" placeholder="Order Number" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Order Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="editOrderDate" name="editOrderDate" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Unit Price</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editUnitPrice" name="editUnitPrice" placeholder="Unit Price" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Quantity</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editQuantity" name="editQuantity" placeholder="Quantity" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Total Price</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editTotalPrice" name="editTotalPrice" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Has Credit</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editHasCredit" name="editHasCredit" required>
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="editCreditAmountGroup" style="display: none;">
                        <label class="col-sm-3 control-label">Credit Amount</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editCreditAmount" name="editCreditAmount" placeholder="Credit Amount">
                        </div>
                    </div>
                    <input type="hidden" id="orderId" name="orderId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/gps_orders.js"></script>

<?php require_once 'includes/footer.php'; ?> 