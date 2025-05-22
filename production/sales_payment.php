<?php 
require_once 'includes/header.php';
require_once 'OptimizedSchemaManager.php';

try {
    // Initialize the schema manager
    $schemaManager = OptimizedSchemaManager::getInstance();

    // Validate required tables exist
    $requiredTables = ['sales_payments', 'sales_orders', 'clients', 'accounts'];
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
    $paymentsSchema = $schemaManager->getTableSchema('sales_payments');
    $ordersSchema = $schemaManager->getTableSchema('sales_orders');
    $accountsSchema = $schemaManager->getTableSchema('accounts');

    // Get primary and foreign keys for relationships
    $paymentsPK = $schemaManager->getPrimaryKey('sales_payments');
    $orderFK = $schemaManager->getForeignKeys('sales_payments')['order_id'] ?? null;
    $accountFK = $schemaManager->getForeignKeys('sales_payments')['account_id'] ?? null;

    // JavaScript configuration for client-side validation
    $jsConfig = [
        'tables' => [
            'sales_payments' => [
                'pk' => $paymentsPK,
                'columns' => array_map(function($col) use ($schemaManager) {
                    return $schemaManager->getColumnDefinition($col);
                }, $paymentsSchema['c'])
            ],
            'sales_orders' => [
                'columns' => array_map(function($col) use ($schemaManager) {
                    return $schemaManager->getColumnDefinition($col);
                }, $ordersSchema['c'])
            ],
            'accounts' => [
                'columns' => array_map(function($col) use ($schemaManager) {
                    return $schemaManager->getColumnDefinition($col);
                }, $accountsSchema['c'])
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
            <li class="active">Sales Payments</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-money"></i> Sales Payments
                    <div class="pull-right">
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addPaymentModal">
                            <i class="fa fa-plus"></i> New Payment
                        </button>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <!-- Filter Section -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h4 class="panel-title"><i class="fa fa-filter"></i> Filter Options</h4>
                            </div>
                            <div class="panel-body">
                                <form id="filterForm" class="form-horizontal">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Date Range</label>
                                                <select class="form-control" id="dateRange" name="dateRange">
                                                    <option value="today">Today</option>
                                                    <option value="yesterday">Yesterday</option>
                                                    <option value="last7days">Last 7 Days</option>
                                                    <option value="last30days">Last 30 Days</option>
                                                    <option value="thisMonth">This Month</option>
                                                    <option value="lastMonth">Last Month</option>
                                                    <option value="custom">Custom Range</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6" id="customDateRange" style="display:none;">
                                            <div class="form-group">
                                                <label class="control-label">From</label>
                                                <input type="date" class="form-control" id="startDate" name="startDate">
                                                <label class="control-label">To</label>
                                                <input type="date" class="form-control" id="endDate" name="endDate">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Payment Status</label>
                                                <select class="form-control" id="paymentStatus" name="paymentStatus">
                                                    <option value="">All Statuses</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="confirmed">Confirmed</option>
                                                    <option value="rejected">Rejected</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Payment Method</label>
                                                <select class="form-control" id="paymentMethod" name="paymentMethod">
                                                    <option value="">All Methods</option>
                                                    <option value="Cash">Cash</option>
                                                    <option value="Bank Transfer">Bank Transfer</option>
                                                    <option value="Check">Check</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Account</label>
                                                <select class="form-control" id="account" name="account">
                                                    <option value="">All Accounts</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Client</label>
                                                <select class="form-control" id="client" name="client">
                                                    <option value="">All Clients</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">&nbsp;</label>
                                                <button type="submit" class="btn btn-primary form-control">
                                                    <i class="fa fa-search"></i> Apply Filters
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h3 class="panel-title">Total Payments</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="totalPayments">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <h3 class="panel-title">Total Amount</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="totalAmount">0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h3 class="panel-title">Pending Payments</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="pendingPayments">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <h3 class="panel-title">Pending Amount</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="pendingAmount">0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payments Table -->
                <div class="table-responsive">
                    <table id="paymentsTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Order Number</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Account</th>
                                <th>Status</th>
                                <th>Reference</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addPaymentForm" action="php_action/createSalesPayment.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add New Payment</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="order_id">Order Number <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="order_id" name="order_id" required>
                                    <option value="">Select Order</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                            </div>
                            <div class="form-group">
                                <label for="amount">Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                                <small class="help-block">Maximum amount: <span id="max_amount">0.00</span></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="account_id">Account <span class="text-danger">*</span></label>
                                <select class="form-control" id="account_id" name="account_id" required>
                                    <option value="">Select Account</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-control" id="payment_method" name="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Check">Check</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="reference_number">Reference Number</label>
                                <input type="text" class="form-control" id="reference_number" name="reference_number">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="payment_proof">Payment Proof (Image/PDF)</label>
                                <input type="file" class="form-control" id="payment_proof" name="payment_proof" accept="image/*,.pdf">
                            </div>
                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Payment Modal -->
<div class="modal fade" id="viewPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Payment Details</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Payment Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th style="width:40%">Payment ID:</th>
                                <td id="view_payment_id"></td>
                            </tr>
                            <tr>
                                <th>Order Number:</th>
                                <td id="view_order_number"></td>
                            </tr>
                            <tr>
                                <th>Client:</th>
                                <td id="view_client_name"></td>
                            </tr>
                            <tr>
                                <th>Payment Date:</th>
                                <td id="view_payment_date"></td>
                            </tr>
                            <tr>
                                <th>Amount:</th>
                                <td id="view_amount"></td>
                            </tr>
                            <tr>
                                <th>Method:</th>
                                <td id="view_payment_method"></td>
                            </tr>
                            <tr>
                                <th>Account:</th>
                                <td id="view_account"></td>
                            </tr>
                            <tr>
                                <th>Reference:</th>
                                <td id="view_reference"></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td id="view_status"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Payment Proof</h5>
                        <div id="view_payment_proof" class="text-center">
                            <img src="" alt="Payment Proof" class="img-responsive" style="max-height: 300px;">
                        </div>
                        <h5>Notes</h5>
                        <div id="view_notes" class="well well-sm"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <div class="btn-group">
                    <button type="button" class="btn btn-success confirmPaymentBtn">
                        <i class="fa fa-check"></i> Confirm Payment
                    </button>
                    <button type="button" class="btn btn-danger rejectPaymentBtn">
                        <i class="fa fa-times"></i> Reject Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Payment Modal -->
<div class="modal fade" id="updatePaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="updatePaymentForm" action="php_action/updateSalesPayment.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Update Payment</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="update_payment_id" name="payment_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="update_payment_date">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="update_payment_date" name="payment_date" required>
                            </div>
                            <div class="form-group">
                                <label for="update_amount">Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="update_amount" name="amount" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label for="update_account_id">Account <span class="text-danger">*</span></label>
                                <select class="form-control" id="update_account_id" name="account_id" required>
                                    <option value="">Select Account</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="update_payment_method">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-control" id="update_payment_method" name="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Check">Check</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="update_reference_number">Reference Number</label>
                                <input type="text" class="form-control" id="update_reference_number" name="reference_number">
                            </div>
                            <div class="form-group">
                                <label for="update_payment_proof">Payment Proof (Image/PDF)</label>
                                <input type="file" class="form-control" id="update_payment_proof" name="payment_proof" accept="image/*,.pdf">
                                <small class="help-block">Current file: <span id="current_proof"></span></small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="update_notes">Notes</label>
                                <textarea class="form-control" id="update_notes" name="notes" rows="3"></textarea>
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

<!-- Custom CSS -->
<style>
    .select2-container {
        width: 100% !important;
    }
    .table > tbody > tr > td {
        vertical-align: middle;
    }
    .payment-proof {
        max-width: 100%;
        max-height: 200px;
    }
    .status-badge {
        padding: 5px 10px;
        border-radius: 3px;
        font-weight: bold;
    }
    .status-pending {
        background-color: #f0ad4e;
        color: white;
    }
    .status-confirmed {
        background-color: #5cb85c;
        color: white;
    }
    .status-rejected {
        background-color: #d9534f;
        color: white;
    }
</style>

<!-- Include Required Scripts -->
<script src="custom/js/sales_payment.js"></script>

<?php require_once 'includes/footer.php'; ?> 