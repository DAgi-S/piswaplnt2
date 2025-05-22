<?php 
require_once 'includes/header.php';
require_once 'OptimizedSchemaManager.php';

// Check base permission to view orders
if (!hasPermission('order.view')) {
    $_SESSION['error'] = "Access Denied. Please contact Admin or go to dashboard.";
    header('Location: dashboard.php');
    exit();
}

// Get user permissions for different order actions
$canCreate = hasPermission('order.create');
$canEdit = hasPermission('order.edit');
$canDelete = hasPermission('order.delete');
$canProcess = hasPermission('order.process');
$canManagePayment = hasPermission('order.payment.manage');
$canManageShipping = hasPermission('order.shipping.manage');
$canUpdateStatus = hasPermission('order.status.update');

try {
    // Initialize the schema manager
    $schemaManager = OptimizedSchemaManager::getInstance();

    // Validate required tables exist
    $requiredTables = ['sales_orders', 'sales_order_items', 'clients', 'warehouses', 'production_products', 'sales_payments'];
    $missingTables = [];
    foreach ($requiredTables as $table) {
        if (!$schemaManager->tableExists($table)) {
            $missingTables[] = $table;
        }
    }

    if (!empty($missingTables)) {
        throw new Exception("Required tables missing: " . implode(", ", $missingTables));
    }

    // Get table schemas for validation
    $salesOrderSchema = $schemaManager->getTableSchema('sales_orders');
    $orderItemsSchema = $schemaManager->getTableSchema('sales_order_items');
    $paymentsSchema = $schemaManager->getTableSchema('sales_payments');

    // Get primary and foreign keys for relationships
    $salesOrderPK = $schemaManager->getPrimaryKey('sales_orders');
    $clientFK = $schemaManager->getForeignKeys('sales_orders')['client_id'] ?? null;
    $warehouseFK = $schemaManager->getForeignKeys('sales_orders')['warehouse_id'] ?? null;

    // JavaScript configuration for client-side validation
    $jsConfig = [
        'tables' => [
            'sales_orders' => [
                'pk' => $salesOrderPK,
                'columns' => array_map(function($col) use ($schemaManager) {
                    return $schemaManager->getColumnDefinition($col);
                }, $salesOrderSchema['c'])
            ],
            'sales_order_items' => [
                'columns' => array_map(function($col) use ($schemaManager) {
                    return $schemaManager->getColumnDefinition($col);
                }, $orderItemsSchema['c'])
            ],
            'sales_payments' => [
                'columns' => array_map(function($col) use ($schemaManager) {
                    return $schemaManager->getColumnDefinition($col);
                }, $paymentsSchema['c'])
            ]
        ]
    ];

} catch (Exception $e) {
    die("Schema Error: " . $e->getMessage() . ". Please ensure db_schema_min.json is generated and accessible.");
}
?>

<!-- Add JavaScript configuration -->
<script>
const schemaConfig = <?php echo json_encode($jsConfig); ?>;
</script>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Sales Orders</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-shopping-cart"></i> Sales Orders
                    <?php if($canCreate): ?>
                    <div class="pull-right">
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addSalesOrderModal">
                            <i class="fa fa-plus"></i> New Sales Order
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table id="salesOrdersTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Order Number</th>
                                        <th>Client</th>
                                        <th>Order Date</th>
                                        <th>Total Amount</th>
                                        <th>Paid Amount</th>
                                        <th>Balance</th>
                                        <th>Payment Status</th>
                                        <th>Order Status</th>
                                        <th>Actions</th>
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

<!-- Add Sales Order Modal -->
<div class="modal fade" id="addSalesOrderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addSalesOrderForm" action="php_action/createSalesOrder.php" method="post" 
                  data-schema-table="sales_orders">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Create New Sales Order</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="client_id">Client <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="client_id" name="client_id" required
                                        data-schema-field="client_id"
                                        data-foreign-key="<?php echo htmlspecialchars($clientFK); ?>">
                                    <option value="">Select Client</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="order_date">Order Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="order_date" name="order_date" required>
                            </div>
                            <div class="form-group">
                                <label for="delivery_date">Expected Delivery Date</label>
                                <input type="date" class="form-control" id="delivery_date" name="delivery_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="warehouse_id">Warehouse <span class="text-danger">*</span></label>
                                <select class="form-control" id="warehouse_id" name="warehouse_id" required
                                        data-schema-field="warehouse_id"
                                        data-foreign-key="<?php echo htmlspecialchars($warehouseFK); ?>">
                                    <option value="">Select Warehouse</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4" 
                                          data-schema-field="notes"
                                          placeholder="Enter any additional notes or special instructions"></textarea>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h4>Order Items</h4>
                    <div class="table-responsive">
                        <table id="orderItemsTable" class="table table-bordered table-hover" 
                               data-schema-table="sales_order_items">
                            <thead>
                                <tr>
                                    <th style="width: 45%;">Product</th>
                                    <th style="width: 15%;">Available</th>
                                    <th style="width: 15%;">Quantity</th>
                                    <th style="width: 15%;">Unit Price</th>
                                    <th style="width: 10%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="emptyRow">
                                    <td colspan="5" class="text-center">No items added</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5">
                                        <button type="button" class="btn btn-info btn-sm" id="addItemBtn">
                                            <i class="fa fa-plus"></i> Add Item
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                                    <td colspan="2" class="text-right"><span id="subtotal">0.00</span></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-right">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" id="applyWithholding"> Apply Withholding Tax (2%)
                                            </label>
                                        </div>
                                    </td>
                                    <td colspan="2" class="text-right"><span id="withholdingAmount">0.00</span></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-right">
                                        <div class="input-group" style="float: right; width: 150px;">
                                            <span class="input-group-addon">Discount %</span>
                                            <input type="number" class="form-control" id="discountPercent" min="0" max="100" step="0.01" value="0">
                                        </div>
                                    </td>
                                    <td colspan="2" class="text-right"><span id="discountAmount">0.00</span></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-right">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" id="applyVAT" checked> Apply VAT (15%)
                                            </label>
                                        </div>
                                    </td>
                                    <td colspan="2" class="text-right"><span id="taxAmount">0.00</span></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-right"><strong>Total Amount:</strong></td>
                                    <td colspan="2" class="text-right"><strong><span id="totalAmount">0.00</span></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createOrderBtn">Create Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Sales Order Modal -->
<div class="modal fade" id="viewSalesOrderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Sales Order Details</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Order Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Order Number</th>
                                <td id="view_order_number"></td>
                            </tr>
                            <tr>
                                <th>Client</th>
                                <td id="view_client_name"></td>
                            </tr>
                            <tr>
                                <th>Order Date</th>
                                <td id="view_order_date"></td>
                            </tr>
                            <tr>
                                <th>Delivery Date</th>
                                <td id="view_delivery_date"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Status Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Order Status</th>
                                <td>
                                    <span id="view_order_status"></span>
                                    <button type="button" class="btn btn-info btn-xs toggle-status-modal-btn" style="margin-left: 10px;">
                                        <i class="fa fa-exchange"></i> Toggle Status
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <th>Payment Status</th>
                                <td>
                                    <span id="view_payment_status"></span>
                                    <button type="button" class="btn btn-success btn-xs toggle-payment-status-btn" style="margin-left: 10px;">
                                        <i class="fa fa-check"></i> Mark as Paid
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <th>Created By</th>
                                <td id="view_created_by"></td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td id="view_created_at"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <h5>Order Items</h5>
                <div class="table-responsive">
                    <table id="viewOrderItemsTable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Tax Rate (%)</th>
                                <th>Tax Amount</th>
                                <th>Discount (%)</th>
                                <th>Discount Amount</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="text-right"><strong>Subtotal:</strong></td>
                                <td id="view_subtotal" class="text-right">0.00</td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-right"><strong>VAT (15%):</strong></td>
                                <td id="view_tax_amount" class="text-right">0.00</td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-right"><strong>Total:</strong></td>
                                <td id="view_total_before_withholding" class="text-right">0.00</td>
                            </tr>
                            <tr id="view_withholding_row">
                                <td colspan="7" class="text-right"><strong>Withholding (2%):</strong></td>
                                <td id="view_withholding_amount" class="text-right">0.00</td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-right"><strong>Grand Total:</strong></td>
                                <td id="view_grand_total" class="text-right">0.00</td>
                            </tr>
                            <tr id="view_discount_row">
                                <td colspan="7" class="text-right"><strong>Discount Amount:</strong></td>
                                <td id="view_discount_amount" class="text-right">0.00</td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-right"><strong>Payable Amount:</strong></td>
                                <td id="view_total_amount" class="text-right">0.00</td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-right"><strong>Paid Amount:</strong></td>
                                <td id="view_paid_amount" class="text-right">0.00</td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-right"><strong>Balance:</strong></td>
                                <td id="view_balance" class="text-right">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <h5>Payment History</h5>
                <div class="table-responsive">
                    <table id="viewPaymentsTable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div id="view_notes" class="well well-sm"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <div class="btn-group">
                    <?php if($canManagePayment): ?>
                    <button type="button" class="btn btn-success payment-btn" id="addPaymentBtn" 
                            data-toggle="modal" 
                            data-target="#addPaymentModal">
                        <i class="fa fa-money"></i> Add Payment
                    </button>
                    <?php endif; ?>
                    <?php if($canUpdateStatus): ?>
                    <button type="button" class="btn btn-primary" id="updateStatusBtn">
                        <i class="fa fa-refresh"></i> Update Status
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Add Payment</h4>
            </div>
            <form id="addPaymentForm" action="php_action/addSalesPayment.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="payment_order_id" id="payment_order_id">
                    
                    <div class="form-group">
                        <label for="account_id">Account</label>
                        <select class="form-control" id="account_id" name="account_id" required>
                            <option value="">Select Account</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_amount">Amount</label>
                        <input type="number" 
                               class="form-control" 
                               id="payment_amount" 
                               name="payment_amount" 
                               step="0.01" 
                               min="0.01"
                               required>
                        <small class="help-block">Maximum amount: <span id="max_payment_amount">0.00</span></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <select class="form-control" id="payment_method" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Check">Check</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_date">Payment Date</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="reference_number">Reference Number</label>
                        <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="Enter reference number">
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_notes">Notes</label>
                        <textarea class="form-control" id="payment_notes" name="payment_notes" rows="3" placeholder="Enter payment notes"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_proof">Payment Proof</label>
                        <input type="file" class="form-control" id="payment_proof" name="payment_proof" accept="image/*,.pdf">
                        <small class="help-block">Accepted formats: Images (JPG, PNG, etc.) and PDF files</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitPaymentBtn">Add Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="updateStatusForm" action="php_action/updateSalesOrder.php" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Update Order Status</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="status_order_id" name="order_id">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" id="order_status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" id="status_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
    .select2-container {
        width: auto;
    }
    .table > tbody > tr > td {
        vertical-align: middle;
        width: auto;
    }
    .product-select {
        width: 100%;
    }
    .quantity-input::-webkit-inner-spin-button,
    .quantity-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .quantity-input {
        text-align: right;
    }
    .remove-item {
        color: #d9534f;
        cursor: pointer;
    }
    .remove-item:hover {
        color: #c9302c;
    }
    .stock-warning {
        color: #d9534f;
        font-size: 12px;
        display: block;
    }

    /* Enhanced DataTable Styles */
    #salesOrdersTable {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        border-radius: 3px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        font-size: 13px;
    }

    #salesOrdersTable thead th {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        color: #495057;
        font-weight: 600;
        padding: 8px 6px;
        white-space: nowrap;
        font-size: 12px;
        text-transform: uppercase;
    }

    #salesOrdersTable tbody tr {
        transition: background 0.2s ease;
    }

    #salesOrdersTable tbody tr:hover {
        background-color: #f5f5f5;
    }

    #salesOrdersTable tbody td {
        padding: 6px;
        border-top: 1px solid #eee;
        vertical-align: middle;
        line-height: 1.2;
    }

    #salesOrdersTable .label {
        font-size: 11px;
        font-weight: 500;
        padding: 3px 6px;
        border-radius: 2px;
        text-transform: capitalize;
        display: inline-block;
        line-height: 1;
    }

    #salesOrdersTable .btn-group {
        white-space: nowrap;
    }

    #salesOrdersTable .btn-group .btn {
        padding: 2px 6px;
        font-size: 12px;
    }

    #salesOrdersTable_wrapper .dataTables_length select {
        min-width: 55px;
        height: 30px;
        padding: 2px;
        font-size: 12px;
        border: 1px solid #ddd;
        border-radius: 3px;
    }

    #salesOrdersTable_wrapper .dataTables_filter input {
        height: 30px;
        padding: 4px 8px;
        font-size: 12px;
        border: 1px solid #ddd;
        border-radius: 3px;
    }

    #salesOrdersTable_wrapper .dataTables_paginate .paginate_button {
        padding: 4px 8px;
        margin-left: 2px;
        font-size: 12px;
        border: 1px solid #ddd;
        border-radius: 3px;
    }

    #salesOrdersTable_wrapper .dataTables_info {
        font-size: 12px;
        padding-top: 4px;
    }

    /* Optimize number columns alignment */
    #salesOrdersTable td:nth-child(4),
    #salesOrdersTable td:nth-child(5),
    #salesOrdersTable td:nth-child(6) {
        text-align: right;
        white-space: nowrap;
    }

    /* Status column optimization */
    #salesOrdersTable td:nth-child(7),
    #salesOrdersTable td:nth-child(8) {
        text-align: center;
    }

    /* Action buttons optimization */
    #salesOrdersTable td:last-child {
        padding: 4px;
        width: 1%;
        white-space: nowrap;
    }

    /* Top controls spacing optimization */
    #salesOrdersTable_wrapper .dt-buttons {
        margin-bottom: 10px;
    }

    #salesOrdersTable_wrapper .dt-buttons .btn {
        padding: 4px 8px;
        font-size: 12px;
    }

    /* Responsive optimization */
    @media screen and (max-width: 767px) {
        #salesOrdersTable_wrapper .dataTables_length,
        #salesOrdersTable_wrapper .dataTables_filter {
            margin-bottom: 8px;
        }
        
        #salesOrdersTable_wrapper .dataTables_paginate {
            margin-top: 8px;
        }

        #salesOrdersTable td,
        #salesOrdersTable th {
            padding: 4px;
        }
    }
</style>

<!-- Include Required Scripts -->
<script src="custom/js/sales.js"></script>

<!-- Initialize DataTable -->
<script>
$(document).ready(function() {
    // Pass PHP permissions to JavaScript
    const permissions = {
        canCreate: <?php echo json_encode($canCreate); ?>,
        canEdit: <?php echo json_encode($canEdit); ?>,
        canDelete: <?php echo json_encode($canDelete); ?>,
        canProcess: <?php echo json_encode($canProcess); ?>,
        canManagePayment: <?php echo json_encode($canManagePayment); ?>,
        canManageShipping: <?php echo json_encode($canManageShipping); ?>,
        canUpdateStatus: <?php echo json_encode($canUpdateStatus); ?>
    };

    // Initialize DataTable
    if (typeof window.salesTable === 'undefined') {
        window.salesTable = $('#salesOrdersTable').DataTable({
            "ajax": {
                "url": "php_action/fetchSalesOrders.php",
                "type": "POST"
            },
            "columns": [
                {"data": "order_number"},
                {"data": "client_name"},
                {"data": "order_date"},
                {
                    "data": "total_amount",
                    "className": "text-right",
                    "render": function(data) {
                        return parseFloat(data).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                },
                {
                    "data": "paid_amount",
                    "className": "text-right",
                    "render": function(data) {
                        return parseFloat(data).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                },
                {
                    "data": "balance",
                    "className": "text-right",
                    "render": function(data) {
                        return parseFloat(data).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                },
                {
                    "data": "payment_status",
                    "render": function(data) {
                        let badgeClass = '';
                        switch(data.toLowerCase()) {
                            case 'paid': badgeClass = 'success'; break;
                            case 'partial': badgeClass = 'warning'; break;
                            default: badgeClass = 'danger';
                        }
                        return '<span class="label label-' + badgeClass + '">' + 
                               data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                    }
                },
                {
                    "data": "order_status",
                    "render": function(data) {
                        let badgeClass = '';
                        switch(data.toLowerCase()) {
                            case 'completed': badgeClass = 'success'; break;
                            case 'processing': badgeClass = 'warning'; break;
                            case 'cancelled': badgeClass = 'danger'; break;
                            default: badgeClass = 'info';
                        }
                        return '<span class="label label-' + badgeClass + '">' + 
                               data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                    }
                },
                {"data": "actions", "orderable": false}
            ],
            "order": [[2, "desc"]],
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "processing": true,
            "serverSide": true,
            "dom": '<"top"Blfrtip>',
            "buttons": [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            "language": {
                "processing": "Loading...",
                "lengthMenu": "_MENU_ records per page",
                "zeroRecords": "No matching records found",
                "info": "Showing _START_ to _END_ of _TOTAL_ records",
                "infoEmpty": "No records available",
                "infoFiltered": "(filtered from _MAX_ total records)",
                "search": "Search:",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Previous"
                }
            }
        });

        // Refresh table every 5 minutes
        setInterval(function() {
            window.salesTable.ajax.reload(null, false);
        }, 300000);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 