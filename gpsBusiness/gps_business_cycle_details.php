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

// Get cycle ID from URL
$cycleId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$cycleId) {
    header('location: gps_business_cycles.php');
    exit();
}

// Fetch cycle details
$sql = "SELECT 
        bc.*,
        (SELECT COALESCE(SUM(o.total_price), 0) 
         FROM gps_orders o 
         JOIN gps_business_cycle_orders bco ON o.id = bco.order_id 
         WHERE bco.business_cycle_id = bc.id) as total_purchase_etb,
        (SELECT COALESCE(SUM(s.total), 0) 
         FROM gps_sales s 
         JOIN gps_business_cycle_sales bcs ON s.id = bcs.sale_id 
         WHERE bcs.business_cycle_id = bc.id) as total_sales_etb,
        (SELECT COALESCE(SUM(o.credit_amount), 0) 
         FROM gps_orders o 
         JOIN gps_business_cycle_orders bco ON o.id = bco.order_id 
         WHERE bco.business_cycle_id = bc.id) as total_credit_amount,
        (SELECT COALESCE(SUM(amount_etb), 0) 
         FROM gps_business_expenses 
         WHERE business_cycle_id = bc.id) as total_expenses_etb,
        (SELECT COALESCE(SUM(o.quantity), 0)
         FROM gps_orders o 
         JOIN gps_business_cycle_orders bco ON o.id = bco.order_id 
         WHERE bco.business_cycle_id = bc.id) as total_purchased_quantity,
        (SELECT COALESCE(SUM(s.quantity), 0)
         FROM gps_sales s 
         JOIN gps_business_cycle_sales bcs ON s.id = bcs.sale_id 
         WHERE bcs.business_cycle_id = bc.id) as total_sold_quantity,
        (SELECT COALESCE(SUM(CASE WHEN expense_type = 'damaged_product' THEN amount_etb ELSE 0 END), 0)
         FROM gps_business_expenses 
         WHERE business_cycle_id = bc.id) as total_damaged_quantity
        FROM gps_business_cycles bc
        WHERE bc.id = ?";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $cycleId);
$stmt->execute();
$result = $stmt->get_result();
$cycle = $result->fetch_assoc();

// Calculate net profit
$cycle['net_profit_etb'] = $cycle['total_sales_etb'] - $cycle['total_purchase_etb'] - $cycle['total_expenses_etb'];

// Calculate grand profit
$cycle['grand_profit_etb'] = $cycle['net_profit_etb'] - $cycle['total_credit_amount'];

// Calculate current stock
$cycle['current_stock'] = $cycle['total_purchased_quantity'] - $cycle['total_sold_quantity'] - $cycle['total_damaged_quantity'];

if (!$cycle) {
    header('location: gps_business_cycles.php');
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
        <ol class="breadcrumb">
            <li><a href="gps_business_cycles.php">Business Cycles</a></li>
            <li class="active">Cycle #<?php echo $cycle['id']; ?></li>
        </ol>

        <!-- Basic Information -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fas fa-info-circle"></i> Cycle Information
                </div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>Cycle Number</th>
                                <td>Cycle #<?php echo $cycle['id']; ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    switch($cycle['status']) {
                                        case 'active':
                                            $statusClass = 'label-success';
                                            break;
                                        case 'completed':
                                            $statusClass = 'label-info';
                                            break;
                                        case 'cancelled':
                                            $statusClass = 'label-danger';
                                            break;
                                        default:
                                            $statusClass = 'label-default';
                                    }
                                    echo '<span class="label '.$statusClass.'">'.$cycle['status'].'</span>';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Start Date</th>
                                <td><?php echo $cycle['start_date']; ?></td>
                            </tr>
                            <tr>
                                <th>End Date</th>
                                <td><?php echo $cycle['end_date'] ?: 'Active'; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>Total Purchase (ETB)</th>
                                <td><?php echo number_format($cycle['total_purchase_etb'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Total Sales (ETB)</th>
                                <td><?php echo number_format($cycle['total_sales_etb'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Total Expenses (ETB)</th>
                                <td><?php echo number_format($cycle['total_expenses_etb'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Total Credit</th>
                                <td><?php echo number_format($cycle['total_credit_amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Net Profit (ETB)</th>
                                <td class="<?php echo $cycle['net_profit_etb'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo number_format($cycle['net_profit_etb'], 2); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Grand Profit (ETB)</th>
                                <td class="<?php echo $cycle['grand_profit_etb'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo number_format($cycle['grand_profit_etb'], 2); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Current Stock (Devices)</th>
                                <td><?php echo number_format($cycle['current_stock']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fas fa-shopping-cart"></i> Orders in this Cycle
                </div>
            </div>
            <div class="panel-body">
                <table class="table table-hover table-striped table-bordered" id="cycleOrdersTable">
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Order Date</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>Credit Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!-- Sales -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fas fa-dollar-sign"></i> Sales in this Cycle
                </div>
            </div>
            <div class="panel-body">
                <table class="table table-hover table-striped table-bordered" id="cycleSalesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Buyer</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!-- Expenses -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fas fa-money-bill"></i> Expenses in this Cycle
                    <?php if ($cycle['status'] === 'active'): ?>
                    <button class="btn btn-primary btn-sm pull-right" onclick="addExpenseToCycle(<?php echo $cycleId; ?>)">
                        <i class="fas fa-plus"></i> Add Expense
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-body">
                <table class="table table-hover table-striped table-bordered" id="cycleExpensesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount (ETB)</th>
                            <th>Type</th>
                            <th>Payment Method</th>
                            <th>Reference #</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <?php if ($cycle['status'] === 'completed'): ?>
        <!-- Profit Distribution -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fas fa-chart-pie"></i> Profit Distribution
                </div>
            </div>
            <div class="panel-body">
                <table class="table table-hover table-striped table-bordered" id="profitDistributionTable">
                    <thead>
                        <tr>
                            <th>Investor</th>
                            <th>Share %</th>
                            <th>Amount (ETB)</th>
                            <th>Distribution Date</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-shopping-cart"></i> Order Details</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>Order Number</th>
                                <td id="view_order_number"></td>
                            </tr>
                            <tr>
                                <th>Order Date</th>
                                <td id="view_order_date"></td>
                            </tr>
                            <tr>
                                <th>Quantity</th>
                                <td id="view_quantity"></td>
                            </tr>
                            <tr>
                                <th>Unit Price</th>
                                <td id="view_unit_price"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>Total Purchased</th>
                                <td id="view_total_price"></td>
                            </tr>
                            <tr>
                                <th>Credit Status</th>
                                <td id="view_credit_status"></td>
                            </tr>
                            <tr>
                                <th>Credit Amount</th>
                                <td id="view_credit_amount"></td>
                            </tr>
                            <tr>
                                <th>Created Date</th>
                                <td id="view_created_at"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Sale Details Modal -->
<div class="modal fade" id="viewSaleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-dollar-sign"></i> Sale Details</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>Buyer Name</th>
                                <td id="view_buyer_name"></td>
                            </tr>
                            <tr>
                                <th>Contact</th>
                                <td id="view_contact"></td>
                            </tr>
                            <tr>
                                <th>Sale Date</th>
                                <td id="view_sale_date"></td>
                            </tr>
                            <tr>
                                <th>Sale Type</th>
                                <td id="view_sales_type"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>Quantity</th>
                                <td id="view_sale_quantity"></td>
                            </tr>
                            <tr>
                                <th>Unit Price</th>
                                <td id="view_sale_unit_price"></td>
                            </tr>
                            <tr>
                                <th>Total Amount</th>
                                <td id="view_total_amount"></td>
                            </tr>
                            <tr>
                                <th>Currency</th>
                                <td id="view_currency"></td>
                            </tr>
                            <tr>
                                <th>-</th>
                                <td id="view_rate"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Expense Details Modal -->
<div class="modal fade" id="viewExpenseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-money-bill"></i> Expense Details</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>Date</th>
                                <td id="view_expense_date"></td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td id="view_expense_description"></td>
                            </tr>
                            <tr>
                                <th>Amount (ETB)</th>
                                <td id="view_expense_amount"></td>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <td id="view_expense_type"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>Payment Method</th>
                                <td id="view_payment_method"></td>
                            </tr>
                            <tr>
                                <th>Reference Number</th>
                                <td id="view_reference_number"></td>
                            </tr>
                            <tr>
                                <th>Notes</th>
                                <td id="view_expense_notes"></td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td id="view_expense_created_at"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
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
                    <input type="hidden" id="cycleIdForExpense" name="cycle_id" value="<?php echo $cycleId; ?>" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var cycleId = <?php echo $cycleId; ?>;
</script>
<script src="custom/js/gps_business_cycle_details.js"></script>

<?php require_once 'includes/footer.php'; ?> 