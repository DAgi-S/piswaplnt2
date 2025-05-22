<?php 
require_once 'includes/header.php';
require_once 'php_action/classes/LowStockManager.php';

$lowStockManager = new LowStockManager($connect);

// Handle notification check
if (isset($_POST['check_notifications'])) {
    $result = $lowStockManager->checkAndNotifyLowStock();
    if ($result['success']) {
        $notificationMessage = $result['message'];
    } else {
        $errorMessage = $result['message'];
    }
}

// Get stock summary
$summary = $lowStockManager->getLowStockSummary();
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Low Stock</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-exclamation-triangle"></i> Low Stock Items
                    <div class="pull-right">
                        <button type="button" id="checkNotifyBtn" class="btn btn-warning">
                            <i class="fa fa-bell"></i> Check & Notify
                        </button>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#thresholdSettingsModal">
                            <i class="fa fa-cog"></i> Threshold Settings
                        </button>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <?php if (isset($notificationMessage)): ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i> <?php echo $notificationMessage; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($errorMessage)): ?>
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>

                <!-- Stock Status Summary -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="alert alert-danger">
                            <h4>Out of Stock</h4>
                            <span id="outOfStockCount"><?php echo $summary['out_of_stock']; ?></span> items
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-warning">
                            <h4>Critical Stock</h4>
                            <span id="criticalStockCount"><?php echo $summary['critical_stock']; ?></span> items
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-info">
                            <h4>Low Stock</h4>
                            <span id="lowStockCount"><?php echo $summary['low_stock']; ?></span> items
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-success">
                            <h4>Total Items</h4>
                            <span id="totalItemCount"><?php echo $summary['total_items']; ?></span> items
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="lowStockTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Warehouse</th>
                                <th>Current Stock</th>
                                <th>Minimum Stock</th>
                                <th>Reorder Point</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addStockForm" action="php_action/createStockMovement.php" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Stock</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Selected Item*</label>
                        <div class="well well-sm" style="margin-bottom: 10px; padding: 10px; background-color: #f9f9f9;">
                            <div id="itemName" style="font-size: 14px; color: #333;">No item selected</div>
                        </div>
                        <input type="hidden" id="itemId" name="item_id" required>
                        <input type="hidden" id="itemType" name="item_type" required>
                    </div>
                    <div class="form-group">
                        <label for="destinationId">Destination Warehouse*</label>
                        <select class="form-control" id="destinationId" name="destination_id" required>
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
                    <div class="form-group">
                        <label for="referenceType">Reference Type*</label>
                        <select class="form-control" id="referenceType" name="reference_type" required>
                            <option value="">Select Type</option>
                            <option value="purchase">Purchase</option>
                            <option value="production">Production</option>
                            <option value="adjustment">Adjustment</option>
                            <option value="return">Return</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="referenceId">Reference ID*</label>
                        <input type="text" class="form-control" id="referenceId" name="reference_id" placeholder="Enter reference ID" required>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity*</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" placeholder="Enter quantity" required min="0.01" step="0.01">
                        <small class="help-block">Must be greater than 0</small>
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Enter notes (optional)"></textarea>
                    </div>
                    <input type="hidden" name="movement_type" value="in">
                    <input type="hidden" name="source_type" value="supplier">
                    <input type="hidden" name="source_id" value="0">
                    <input type="hidden" name="destination_type" value="warehouse">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Threshold Settings Modal -->
<div class="modal fade" id="thresholdSettingsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-cog"></i> Threshold Settings</h4>
            </div>
            <div class="modal-body">
                <form id="thresholdSettingsForm">
                    <div class="form-group">
                        <label>Threshold Buffer (%)</label>
                        <input type="number" class="form-control" id="thresholdBuffer" name="threshold_buffer" min="0" step="5" value="20">
                        <small class="help-block">Buffer percentage above minimum stock level for alerts</small>
                    </div>
                    <div class="form-group">
                        <label>Auto-calculate Thresholds</label>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="autoCalculateThresholds" name="auto_calculate_thresholds">
                                Enable automatic threshold calculation based on usage history
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Notifications</label>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="emailNotifications" name="email_notifications">
                                Send email alerts for low stock items
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Alert Frequency</label>
                        <select class="form-control" id="alertFrequency" name="alert_frequency">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="immediate">Immediate</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveThresholdSettings">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Stock Trend Modal -->
<div class="modal fade" id="stockTrendModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-line-chart"></i> Stock Trend Analysis</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <canvas id="stockTrendChart"></canvas>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h4>Usage Statistics</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th>Average Daily Usage</th>
                                <td id="avgDailyUsage">-</td>
                            </tr>
                            <tr>
                                <th>Recommended Reorder Point</th>
                                <td id="recommendedReorderPoint">-</td>
                            </tr>
                            <tr>
                                <th>Days Until Stock Out</th>
                                <td id="daysUntilStockout">-</td>
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

<!-- Custom CSS -->
<style>
    .label {
        display: inline-block;
        min-width: 80px;
        text-align: center;
        margin-bottom: 3px;
    }
    .label-critical {
        background-color: #d9534f;
    }
    .label-warning {
        background-color: #f0ad4e;
    }
    .label-info {
        background-color: #5bc0de;
    }
    .btn-group {
        display: flex;
        justify-content: center;
    }
    .btn-group .btn {
        margin: 0 2px;
    }
    .table > tbody > tr > td {
        vertical-align: middle;
    }
    .mb-3 {
        margin-bottom: 20px;
    }
    .mt-3 {
        margin-top: 20px;
    }
</style>

<!-- Include Required Libraries -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/dt-1.10.25/b-1.7.1/b-html5-1.7.1/b-print-1.7.1/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/bs/dt-1.10.25/b-1.7.1/b-html5-1.7.1/b-print-1.7.1/datatables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Include Custom JS -->
<script src="js/low_stock.js"></script>

<script type="text/javascript">
$(document).ready(function() {
    // Handle Check & Notify button click
    $('#checkNotifyBtn').click(function() {
        Swal.fire({
            title: 'Under Development',
            text: 'This feature is currently under development. We will let you know when it\'s ready!',
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3085d6'
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 