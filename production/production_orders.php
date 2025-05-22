<?php 
require_once 'includes/header.php';
require_once 'php_action/production_middleware.php';
require_once 'php_action/includes/LogManager.php';

// Initialize production middleware
$productionMiddleware = new ProductionMiddleware($connect);

// Initialize LogManager
$logManager = new LogManager($connect, $_SESSION['userId']);

// Validate base production access
$productionMiddleware->validateAccess('production.view');

// Get user permissions for UI control
$permissions = [
    'view' => $productionMiddleware->checkPermission('production.order.view'),
    'create' => $productionMiddleware->checkPermission('production.order.create'),
    'edit' => $productionMiddleware->checkPermission('production.order.edit'),
    'delete' => $productionMiddleware->checkPermission('production.order.delete'),
    'manage_quality' => $productionMiddleware->checkPermission('production.quality.manage'),
    'inspect_quality' => $productionMiddleware->checkPermission('production.quality.inspect'),
    'manage_schedule' => $productionMiddleware->checkPermission('production.schedule.manage'),
    'view_schedule' => $productionMiddleware->checkPermission('production.schedule.view'),
    'manage_waste' => $productionMiddleware->checkPermission('production.waste.manage'),
    'view_reports' => $productionMiddleware->checkPermission('production.reports.view'),
    'export_reports' => $productionMiddleware->checkPermission('production.reports.export'),
    'generate_reports' => $productionMiddleware->checkPermission('production.reports.generate')
];

// Log page access
try {
    $logManager->logActivity(
        "Accessed production orders page",
        "production_orders",
        0
    );
} catch (Exception $e) {
    error_log("Error logging page access: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Production Orders</li>
        </ol>

        <?php if ($permissions['view']): ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-industry"></i> Manage Production Orders
                    <?php if ($permissions['create']): ?>
                    <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addProductionOrderModal">
                        <i class="fa fa-plus"></i> New Production Order
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel-body">
                <div class="remove-messages"></div>

                <table class="table table-bordered table-striped" id="productionOrdersTable">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Product</th>
                            <th>Status</th>
                            <th>Target Qty</th>
                            <th>Completed Qty</th>
                            <th>Start Date</th>
                            <th>Completion Date</th>
                            <th>Created By</th>
                            <?php if ($permissions['edit'] || $permissions['delete']): ?>
                            <th>Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-triangle"></i> You do not have permission to view production orders.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($permissions['create']): ?>
<!-- Add Production Order Modal -->
<div class="modal fade" id="addProductionOrderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form class="form-horizontal" id="submitProductionOrderForm" action="php_action/createProductionOrder.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Production Order</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label col-sm-3">Order Number:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="orderNumber" name="orderNumber" 
                                   placeholder="Auto-generated if left empty" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Product:</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="product_id" name="product" required>
                                <option value="">Select Product</option>
                                <?php
                                $sql = "SELECT id, product_code, name FROM production_products WHERE status = 'active' ORDER BY product_code";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_assoc()) {
                                    echo "<option value='".$row['id']."'>".$row['product_code']." - ".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Target Quantity:</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="targetQuantity" name="targetQuantity" 
                                   min="0.1" step="any" required 
                                   oninput="this.setCustomValidity(this.value <= 0 ? 'Target quantity must be greater than zero' : '')"
                                   oninvalid="this.setCustomValidity('Valid target quantity is required')" />
                            <small class="text-muted">Must be greater than zero</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Start Date:</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="startDate" name="startDate" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Expected Completion:</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="completionDate" name="completionDate" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Required Materials:</label>
                        <div class="col-sm-9">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="materialsTable">
                                    <thead>
                                        <tr>
                                            <th>Material Code</th>
                                            <th>Material Name</th>
                                            <th>Required Quantity</th>
                                            <th>Available Stock</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="5" class="text-center">Please select a product to view required materials</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="materialLoadingStatus" class="alert" style="display: none;"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Notes:</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Warehouse:</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="warehouse_id" name="warehouse">
                                <option value="0">Select Warehouse (Optional)</option>
                                <?php
                                $sql = "SELECT id, name FROM warehouses WHERE status = 'active' ORDER BY name";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_assoc()) {
                                    echo "<option value='".$row['id']."'>".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createProductionOrderBtn">Create Order</button>
                </div>
            </form>
            <?php echo $productionMiddleware->getCSRFTokenInput(); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($permissions['edit']): ?>
<!-- View/Edit Production Order Modal -->
<div class="modal fade" id="editProductionOrderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal content will be loaded dynamically -->
            <?php echo $productionMiddleware->getCSRFTokenInput(); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Custom CSS -->
<style>
    .label {
        display: inline-block;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: bold;
        border-radius: 3px;
    }
    .label-default { background-color: #777; }
    .label-primary { background-color: #337ab7; }
    .label-info { background-color: #5bc0de; }
    .label-success { background-color: #5cb85c; }
    .label-danger { background-color: #d9534f; }

    /* Status Change Popup Styles */
    .status-change-popup {
        max-width: 600px;
    }

    .status-confirmation-content {
        text-align: left;
        padding: 15px;
    }

    .status-confirmation-content ul {
        list-style: none;
        padding: 10px 20px;
        margin: 10px 0;
        background: #f8f9fa;
        border-radius: 4px;
    }

    .status-confirmation-content ul li {
        padding: 5px 0;
        border-bottom: 1px solid #eee;
    }

    .status-confirmation-content ul li:last-child {
        border-bottom: none;
    }

    .status-confirmation-content .text-danger {
        color: #dc3545;
    }

    /* Completion Summary Styles */
    .completion-summary {
        padding: 15px;
    }

    .completion-summary h4 {
        margin-bottom: 20px;
        color: #28a745;
    }

    .completion-summary .table {
        margin-bottom: 0;
    }

    .completion-summary .table th {
        width: 40%;
        background-color: #f8f9fa;
    }

    /* Action Buttons Styles */
    .btn-group {
        display: flex;
        gap: 2px;
    }

    .btn-group .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }

    .btn-group .btn i {
        font-size: 12px;
    }

    /* Modal Styles */
    .modal-lg {
        max-width: 900px;
    }

    .modal-body {
        padding: 20px;
    }

    .table-responsive {
        margin-top: 15px;
        margin-bottom: 15px;
    }

    /* Production Order Details Popup Styles */
    .production-order-modal {
        max-width: 800px !important;
    }

    .production-order-details .panel {
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .production-order-details .panel-heading {
        background-color: #f5f5f5;
        border-bottom: 1px solid #ddd;
        padding: 10px 15px;
    }

    .production-order-details .panel-heading h4 {
        margin: 0;
        font-size: 16px;
        font-weight: bold;
    }

    .production-order-details .panel-body {
        padding: 15px;
    }

    .production-order-details .table {
        margin-bottom: 0;
    }

    .production-order-details .table th {
        background-color: #f9f9f9;
    }

    .production-order-details .progress {
        margin-bottom: 0;
        height: 20px;
    }

    .production-order-details .progress-bar {
        line-height: 20px;
        font-size: 12px;
        min-width: 30px;
    }

    /* Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }
        #printableArea, #printableArea * {
            visibility: visible;
        }
        #printableArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .modal {
            position: absolute;
            left: 0;
            top: 0;
            margin: 0;
            padding: 0;
            min-height: 550px;
            visibility: visible;
            overflow: visible !important;
        }
        .print-header {
            display: block !important;
            margin-bottom: 20px;
        }
        .modal-footer,
        .close,
        .btn {
            display: none !important;
        }
        .table {
            border: 1px solid #ddd;
        }
        .table th,
        .table td {
            border: 1px solid #ddd !important;
        }
        .progress {
            border: 1px solid #ddd;
        }
        .progress-bar {
            background-color: #5bc0de !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

<!-- DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="assets/datatables/css/dataTables.bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="assets/plugin/datatables/jquery.dataTables.css">
<script type="text/javascript" src="assets/datatables/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="assets/datatables/js/dataTables.bootstrap.min.js"></script>

<!-- Include required modules -->
<script src="js/modules/status-handler.js"></script>
<script src="js/modules/production-order.js"></script>
<script src="js/production_orders.js"></script>

<script type="text/javascript">
// Pass permissions and logging info to JavaScript
var userPermissions = <?php echo json_encode($permissions); ?>;
var loggerEnabled = true;

$(document).ready(function() {
    // Initialize DataTable with permissions
    if (typeof ProductionOrder !== 'undefined') {
        ProductionOrder.initDataTable(userPermissions);
        
        // Initialize Add Production Order functionality if allowed
        if (userPermissions.create) {
            ProductionOrder.initAddOrder();
        }
    } else {
        console.error('ProductionOrder module not loaded');
    }

    // Log successful page load
    $.ajax({
        url: 'php_action/logActivity.php',
        type: 'POST',
        data: {
            action: 'Production orders page loaded successfully',
            module: 'production_orders',
            reference_id: 0
        },
        error: function(xhr, status, error) {
            console.error('Error logging activity:', error);
        }
    });
});

// Add logging helper function to global scope
window.logProductionActivity = function(action, referenceId, data = null) {
    if (!loggerEnabled) return;

    $.ajax({
        url: 'php_action/logActivity.php',
        type: 'POST',
        data: {
            action: action,
            module: 'production_orders',
            reference_id: referenceId,
            additional_data: data ? JSON.stringify(data) : null
        },
        error: function(xhr, status, error) {
            console.error('Error logging activity:', error);
        }
    });
};
</script>

<!-- Add JavaScript permissions -->
<script>
const permissions = {
    view: <?php echo json_encode($permissions['view']); ?>,
    create: <?php echo json_encode($permissions['create']); ?>,
    edit: <?php echo json_encode($permissions['edit']); ?>,
    delete: <?php echo json_encode($permissions['delete']); ?>,
    manageQuality: <?php echo json_encode($permissions['manage_quality']); ?>,
    inspectQuality: <?php echo json_encode($permissions['inspect_quality']); ?>,
    manageSchedule: <?php echo json_encode($permissions['manage_schedule']); ?>,
    viewSchedule: <?php echo json_encode($permissions['view_schedule']); ?>,
    manageWaste: <?php echo json_encode($permissions['manage_waste']); ?>,
    viewReports: <?php echo json_encode($permissions['view_reports']); ?>,
    exportReports: <?php echo json_encode($permissions['export_reports']); ?>,
    generateReports: <?php echo json_encode($permissions['generate_reports']); ?>
};
</script>

<script src="custom/js/sales_reports.js"></script>

<?php require_once 'includes/footer.php'; ?> 