<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';

// Initialize production middleware
require_once 'php_action/production_middleware.php';
$productionMiddleware = new ProductionMiddleware($connect);

// Check both new and legacy permissions
$permissions = [
    // New permission format
    'view' => $productionMiddleware->checkPermission('inventory.warehouse.view'),
    'manage' => $productionMiddleware->checkPermission('inventory.warehouse.manage'),
    
    // Legacy permissions for backward compatibility
    'legacy_view' => $productionMiddleware->checkPermission('inventory_warehouse_view'),
    'legacy_manage' => $productionMiddleware->checkPermission('inventory_warehouse_manage')
];

// Check if user has either new or legacy permission to view warehouses
if (!($permissions['view'] || $permissions['legacy_view'])) {
    echo '<div class="alert alert-danger">You do not have permission to view warehouses.</div>';
    exit();
}

// Define the JavaScript permissions before any HTML output
$jsPermissions = json_encode($permissions);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Warehouses Management</title>
    <script>
        var userPermissions = <?php echo $jsPermissions; ?>;
    </script>

    <!-- Add CSS in head -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <style>
        .stock-badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        .stock-badge-raw { background: #5bc0de; color: white; }
        .stock-badge-finished { background: #5cb85c; color: white; }
        .stock-badge-both { background: #f0ad4e; color: white; }
        .modal { overflow-y: auto !important; }
    </style>
</head>
<body>
    <div class="row">
        <div class="col-md-12">
            <ol class="breadcrumb">
                <li><a href="dashboard.php">Home</a></li>
                <li class="active">Warehouses</li>
            </ol>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading">
                        <i class="fas fa-warehouse"></i> Manage Warehouses
                        <?php if ($permissions['manage'] || $permissions['legacy_manage']): ?>
                        <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addWarehouseModal">
                            <i class="fa fa-plus"></i> Add Warehouse
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel-body">
                    <div class="remove-messages"></div>
                    <table class="table" id="warehousesTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Stock Items</th>
                                <?php if ($permissions['manage'] || $permissions['legacy_manage']): ?>
                                <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Warehouse Modal -->
    <div class="modal fade" id="addWarehouseModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addWarehouseForm" action="php_action/createWarehouse.php" method="post">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><i class="fa fa-plus"></i> Add Warehouse</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="code">Code</label>
                            <input type="text" class="form-control" id="code" name="code" placeholder="Warehouse Code" required>
                        </div>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Warehouse Name" required>
                        </div>
                        <div class="form-group">
                            <label for="type">Type</label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="raw_material">Raw Material</option>
                                <option value="finished_good">Finished Good</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" class="form-control" id="location" name="location" placeholder="Warehouse Location">
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Warehouse Description"></textarea>
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

    <!-- Edit Warehouse Modal -->
    <div class="modal fade" id="editWarehouseModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editWarehouseForm" action="php_action/editWarehouse.php" method="post">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Warehouse</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="editCode">Code</label>
                            <input type="text" class="form-control" id="editCode" name="code" placeholder="Warehouse Code" required>
                        </div>
                        <div class="form-group">
                            <label for="editName">Name</label>
                            <input type="text" class="form-control" id="editName" name="name" placeholder="Warehouse Name" required>
                        </div>
                        <div class="form-group">
                            <label for="editType">Type</label>
                            <select class="form-control" id="editType" name="type" required>
                                <option value="">Select Type</option>
                                <option value="raw_material">Raw Material</option>
                                <option value="finished_good">Finished Good</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editLocation">Location</label>
                            <input type="text" class="form-control" id="editLocation" name="location" placeholder="Warehouse Location">
                        </div>
                        <div class="form-group">
                            <label for="editDescription">Description</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="3" placeholder="Warehouse Description"></textarea>
                        </div>
                        <input type="hidden" id="editWarehouseId" name="id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Stock View Modal -->
    <div class="modal fade" id="viewStockModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Warehouse Stock Items</h4>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="warehouseStockTable">
                            <thead>
                                <tr>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Type</th>
                                    <th>Unit</th>
                                    <th>Quantity</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Stock Modal -->
    <div class="modal fade" id="editStockModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editStockForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">Edit Stock Quantity</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="editQuantity">Quantity</label>
                            <input type="number" class="form-control" id="editQuantity" name="quantity" step="0.01" required>
                            <input type="hidden" id="editStockId" name="stockId">
                        </div>
                        <div class="form-group">
                            <label for="editNotes">Notes</label>
                            <textarea class="form-control" id="editNotes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Load Scripts at the end -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js" defer></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js" defer></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js" defer></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js" defer></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js" defer></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js" defer></script>
    <script src="js/warehouses.js" defer></script>
</body>
</html> 