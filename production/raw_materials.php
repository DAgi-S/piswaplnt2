<?php 
require_once 'includes/header.php';

// Initialize production middleware
require_once 'php_action/production_middleware.php';
$productionMiddleware = new ProductionMiddleware($connect);

// Check both new and legacy permissions
$permissions = [
    // New permission format
    'view' => hasPermission('inventory.raw_materials.view'),
    'edit' => hasPermission('inventory.raw_materials.edit'),
    'delete' => hasPermission('inventory.raw_materials.delete'),
    'manage' => hasPermission('inventory.raw_materials.manage'),
    
    // Legacy permissions for backward compatibility
    'legacy_view' => hasPermission('inventory_raw_materials_view'),
    'legacy_edit' => hasPermission('inventory_raw_materials_edit'),
    'legacy_delete' => hasPermission('inventory_raw_materials_delete'),
    'legacy_manage' => hasPermission('inventory_raw_materials_manage')
];

// Check if user has either new or legacy permission to view raw materials
if (!($permissions['view'] || $permissions['legacy_view'])) {
    echo '<div class="alert alert-danger">You do not have permission to view raw materials.</div>';
    exit();
}
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap.min.css">

<!-- Select2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

<!-- Toastr CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<!-- Custom CSS for DataTables Buttons -->
<style>
.dt-buttons .btn {
    margin-right: 5px;
}
.dataTables_wrapper .dt-buttons {
    margin-bottom: 10px;
}
/* Dropdown button fixes */
.dropdown-menu {
    min-width: 160px;
    margin: 2px 0 0;
    box-shadow: 0 6px 12px rgba(0,0,0,.175);
}
.dropdown-menu > li > a {
    padding: 8px 20px;
    clear: both;
    font-weight: 400;
    line-height: 1.42857143;
    color: #333;
    white-space: nowrap;
    cursor: pointer;
}
.dropdown-menu > li > a:hover,
.dropdown-menu > li > a:focus {
    background-color: #f5f5f5;
    color: #262626;
    text-decoration: none;
}
.dropdown-menu > li > a i {
    margin-right: 8px;
    width: 16px;
}
.btn-group.open .dropdown-toggle {
    box-shadow: none;
}
.btn-group > .btn {
    border-radius: 4px;
}
.action-btn {
    min-width: 100px;
    text-align: left;
    position: relative;
}
.action-btn .caret {
    position: absolute;
    right: 8px;
    top: 50%;
    margin-top: -2px;
}
.table .btn-group {
    display: inline-block;
}
.table .dropdown-menu {
    left: auto;
    right: 0;
}
.form-control.error {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25);
}
.form-control.error:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25);
}
.form-group.has-error .select2-container--default .select2-selection--single {
    border-color: #dc3545;
}
.alert {
    margin-bottom: 15px;
    padding: 10px 15px;
    border-radius: 4px;
}
.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}
#edit-raw-material-messages {
    margin-bottom: 15px;
}

/* Stock History Table Styles */
#viewStockHistoryModal .modal-lg {
    width: 90%;
    max-width: 1100px;
}

#viewStockHistoryModal .table-responsive {
    margin: -15px;
    padding: 15px;
    width: calc(100% + 30px);
}

#stockHistoryTable {
    width: 100% !important;
    margin: 0 !important;
}

#stockHistoryTable th, 
#stockHistoryTable td {
    padding: 8px 10px;
    vertical-align: middle;
}

#stockHistoryTable th {
    background-color:rgb(32, 30, 30);
    font-weight: 600;
}

/* Column widths */
#stockHistoryTable .col-date { width: 15%; }
#stockHistoryTable .col-type { width: 10%; }
#stockHistoryTable .col-quantity { width: 15%; }
#stockHistoryTable .col-reference { width: 20%; }
#stockHistoryTable .col-notes { width: 25%; }
#stockHistoryTable .col-by { width: 15%; }

/* Movement type badges */
#stockHistoryTable .label {
    display: inline-block;
    min-width: 60px;
    text-align: center;
    padding: 4px 8px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #viewStockHistoryModal .modal-lg {
        width: 95%;
    }
    
    #stockHistoryTable th, 
    #stockHistoryTable td {
        padding: 6px 8px;
        font-size: 12px;
    }
}
</style>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Raw Materials</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-cubes"></i> Raw Materials Management
                    <div class="pull-right">
                        <?php if ($permissions['manage'] || $permissions['legacy_manage']): ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-plus"></i> Add New <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a href="#" data-toggle="modal" data-target="#addRawMaterialModal"><i class="fa fa-cube"></i> Raw Material</a></li>
                            </ul>
                        </div>
                        <?php endif; ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-list"></i> View <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <?php if ($permissions['view'] || $permissions['legacy_view']): ?>
                                <li><a href="stock_movements.php"><i class="fa fa-exchange"></i> Stock Movements</a></li>
                                <li><a href="low_stock.php"><i class="fa fa-warning"></i> Low Stock Alert</a></li>
                                <li><a href="material_usage_report.php"><i class="fa fa-bar-chart"></i> Usage Report</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <table class="table table-striped table-bordered table-hover" id="rawMaterialsTable">
                    <thead>
                        <tr>
                            <th>Material Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Current Stock</th>
                            <th>Min Stock Level</th>
                            <th>Cost/Unit</th>
                            <th>Status</th>
                            <?php if ($permissions['edit'] || $permissions['delete'] || $permissions['legacy_edit'] || $permissions['legacy_delete']): ?>
                            <th>Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Raw Material Modal -->
<div class="modal fade" id="addRawMaterialModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitRawMaterialForm" action="php_action/createRawMaterial.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Raw Material</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Material Code</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="materialCode" name="material_code" placeholder="Material Code" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="materialName" name="name" placeholder="Material Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Category</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="categoryId" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php
                                $sql = "SELECT id, name FROM raw_material_categories WHERE status = 'active'";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_assoc()) {
                                    echo "<option value='".$row['id']."'>".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Unit</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="unit" name="unit" required>
                                <option value="">Select Unit</option>
                                <option value="pcs">Pieces (PCS)</option>
                                <option value="kg">Kilogram (KG)</option>
                                <option value="g">Gram (G)</option>
                                <option value="l">Liter (L)</option>
                                <option value="ml">Milliliter (ML)</option>
                                <option value="m">Meter (M)</option>
                                <option value="cm">Centimeter (CM)</option>
                                <option value="box">Box</option>
                                <option value="roll">Roll</option>
                                <option value="pack">Pack</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Min Stock Level</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="minStockLevel" name="min_stock_level" placeholder="Minimum Stock Level" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Cost Per Unit</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="costPerUnit" name="cost_per_unit" placeholder="Cost Per Unit" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Description</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="description" name="description" placeholder="Description" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Warehouse</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="warehouseId" name="warehouse_id" required>
                                <option value="">Select Warehouse</option>
                                <?php
                                $sql = "SELECT id, name, code FROM warehouses WHERE status = 'active' AND (type = 'raw_material' OR type = 'both')";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_assoc()) {
                                    echo "<option value='".$row['id']."'>".$row['code']." - ".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createRawMaterialBtn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Raw Material Modal -->
<div class="modal fade" id="editRawMaterialModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editRawMaterialForm" action="php_action/updateRawMaterial.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Raw Material</h4>
                </div>
                <div class="modal-body">
                    <div id="edit-raw-material-messages"></div>
                    <input type="hidden" name="editRawMaterialId" id="editRawMaterialId">
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Material Code</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editMaterialCode" name="material_code" placeholder="Material Code" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editMaterialName" name="name" placeholder="Material Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Category</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editCategoryId" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php
                                $sql = "SELECT id, name FROM raw_material_categories WHERE status = 'active'";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_assoc()) {
                                    echo "<option value='".$row['id']."'>".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Unit</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editUnit" name="unit" required>
                                <option value="">Select Unit</option>
                                <option value="pcs">Pieces (PCS)</option>
                                <option value="kg">Kilogram (KG)</option>
                                <option value="g">Gram (G)</option>
                                <option value="l">Liter (L)</option>
                                <option value="ml">Milliliter (ML)</option>
                                <option value="m">Meter (M)</option>
                                <option value="cm">Centimeter (CM)</option>
                                <option value="box">Box</option>
                                <option value="roll">Roll</option>
                                <option value="pack">Pack</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Min Stock Level</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editMinStockLevel" name="min_stock_level" placeholder="Minimum Stock Level" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Cost Per Unit</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editCostPerUnit" name="cost_per_unit" placeholder="Cost Per Unit" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Description</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="editDescription" name="description" placeholder="Description" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Warehouse</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editWarehouseId" name="warehouse_id" required>
                                <option value="">Select Warehouse</option>
                                <?php
                                $sql = "SELECT id, name, code FROM warehouses WHERE status = 'active' AND (type = 'raw_material' OR type = 'both')";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_assoc()) {
                                    echo "<option value='".$row['id']."'>".$row['code']." - ".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
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

<!-- Adjust Stock Modal -->
<div class="modal fade" id="adjustStockModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="adjustStockForm" action="php_action/adjustRawMaterialStock.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-exchange"></i> Adjust Stock</h4>
                </div>
                <div class="modal-body">
                    <div id="adjust-stock-messages"></div>
                    <input type="hidden" name="material_id" id="adjustMaterialId">
                    
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Material Name</label>
                        <div class="col-sm-8">
                            <p class="form-control-static" id="adjustMaterialName"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Current Stock</label>
                        <div class="col-sm-8">
                            <p class="form-control-static" id="adjustCurrentStock"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Adjustment Type</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="movement_type" required>
                                <option value="in">Stock In (+)</option>
                                <option value="out">Stock Out (-)</option>
                                <option value="adjustment">Adjustment</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Quantity</label>
                        <div class="col-sm-8">
                            <input type="number" step="0.01" min="0.01" class="form-control" name="quantity" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Reference Type</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="reference_type" required>
                                <option value="purchase">Purchase</option>
                                <option value="production">Production</option>
                                <option value="return">Return</option>
                                <option value="adjustment">Adjustment</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Notes</label>
                        <div class="col-sm-8">
                            <textarea class="form-control" name="notes" rows="3" placeholder="Enter notes about this adjustment"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Warehouse</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="warehouse_id" id="adjustWarehouseId" required>
                                <option value="">Select Warehouse</option>
                                <?php
                                $sql = "SELECT id, name, code FROM warehouses WHERE status = 'active' AND (type = 'raw_material' OR type = 'both')";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_assoc()) {
                                    echo "<option value='".$row['id']."'>".$row['code']." - ".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
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

<!-- View Stock History Modal -->
<div class="modal fade" id="viewStockHistoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-history"></i> Stock Movement History</h4>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="stockHistoryTable">
                        <thead>
                            <tr>
                                <th class="col-date">Date</th>
                                <th class="col-type">Type</th>
                                <th class="col-quantity">Quantity</th>
                                <th class="col-reference">Reference</th>
                                <th class="col-notes">Notes</th>
                                <th class="col-by">By</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Core JS Files -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>

<!-- DataTables Core -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<!-- DataTables Dependencies -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Toastr -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Moment.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<!-- Configure Toastr -->
<script>
toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};

// Initialize Select2
$(document).ready(function() {
    $('.select2').select2();
});
</script>

<!-- Custom JS -->
<script src="custom/js/raw_materials.js"></script>

<!-- Quick Add Category Modal -->
<div class="modal fade" id="quickAddCategoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="quickAddCategoryForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Quick Add Category</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="quickCategoryName" placeholder="Category Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Description</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="quickCategoryDescription" placeholder="Category Description" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitQuickAddCategory()">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Add Supplier Modal -->
<div class="modal fade" id="quickAddSupplierModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="quickAddSupplierForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Quick Add Supplier</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Company Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="quickSupplierName" placeholder="Company Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Contact Person</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="quickSupplierContact" placeholder="Contact Person">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Email</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control" id="quickSupplierEmail" placeholder="Email">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Phone</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="quickSupplierPhone" placeholder="Phone">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitQuickAddSupplier()">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add this JavaScript right before the closing body tag -->
<script>
$(document).ready(function() {
    // Initialize all dropdowns
    $('.dropdown-toggle').dropdown();
    
    // Prevent dropdown from closing when clicking inside
    $(document).on('click', '.dropdown-menu', function(e) {
        e.stopPropagation();
    });
    
    // Close dropdown after action
    $(document).on('click', '.dropdown-menu li a', function() {
        $(this).closest('.dropdown-menu').prev('.dropdown-toggle').dropdown('toggle');
    });
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Ensure dropdowns work in DataTables
    $('#rawMaterialsTable').on('draw.dt', function() {
        $('.dropdown-toggle').dropdown();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 