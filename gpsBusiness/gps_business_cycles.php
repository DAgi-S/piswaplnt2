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
                <div class="page-heading"><i class="fas fa-sync-alt"></i> Business Cycles</div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <div class="div-action pull-right" style="padding-bottom:20px;">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addBusinessCycleModal">
                        <i class="fas fa-plus"></i> New Business Cycle
                    </button>
                </div>

                <table class="table table-hover table-striped table-bordered" id="businessCyclesTable">
                    <thead>
                        <tr>
                            <th>Cycle Number</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Total Purchase (ETB)</th>
                            <th>Total Sales (ETB)</th>
                            <th>Total Expenses (ETB)</th>
                            <th>Total Credit</th>
                            <th>Net Profit (ETB)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Business Cycle Modal -->
<div class="modal fade" id="addBusinessCycleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitBusinessCycleForm" action="php_action/manageCycle.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Start New Business Cycle</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Cycle Number</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="cycleNumber" name="cycle_number" placeholder="Leave blank for auto-generation">
                            <small class="text-muted">Format: BC-YYYY-NNN (e.g., BC-2024-001)</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Start Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="startDate" name="start_date" required>
                        </div>
                    </div>
                    <input type="hidden" name="action" value="create">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createBusinessCycleBtn">Start Cycle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Order to Cycle Modal -->
<div class="modal fade" id="addOrderToCycleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitOrderToCycleForm" action="php_action/addOrderToCycle.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Order to Cycle</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Select Order</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="orderId" name="order_id" required>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                    </div>
                    <input type="hidden" id="cycleIdForOrder" name="cycle_id" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Sale to Cycle Modal -->
<div class="modal fade" id="addSaleToCycleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitSaleToCycleForm" action="php_action/addSaleToCycle.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Sale to Cycle</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Select Sale</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="saleId" name="sale_id" required>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                    </div>
                    <input type="hidden" id="cycleIdForSale" name="cycle_id" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Sale</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complete Cycle Modal -->
<div class="modal fade" id="completeCycleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="completeCycleForm" action="php_action/completeCycle.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-check"></i> Complete Business Cycle</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to complete this business cycle? This action cannot be undone.</p>
                    <input type="hidden" id="cycleIdToComplete" name="cycle_id" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Complete Cycle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Expense to Cycle Modal -->
<div class="modal fade" id="addExpenseToCycleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitExpenseToCycleForm" action="php_action/addExpenseToCycle.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Expense to Cycle</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="expenseDate" name="expense_date" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Description</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="description" name="description" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Amount (ETB)</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="amountEtb" name="amount_etb" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Expense Type</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="expenseType" name="expense_type" required>
                                <option value="">Select Type</option>
                                <option value="operational">Operational</option>
                                <option value="damaged_product">Damaged Product</option>
                                <option value="transportation">Transportation</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Payment Method</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="paymentMethod" name="payment_method" required>
                                <option value="">Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="mobile_banking">Mobile Banking</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Reference #</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="referenceNumber" name="reference_number">
                            <small class="text-muted">Optional: Transaction reference number</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Notes</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <input type="hidden" id="cycleIdForExpense" name="cycle_id" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="custom/js/gps_business_cycles.js"></script>

<?php require_once 'includes/footer.php'; ?> 