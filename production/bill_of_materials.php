<?php 
require_once 'includes/header.php';

// Check base permission to view BOM
if (!hasPermission('inventory.bom.view')) {
    $_SESSION['error'] = "Access Denied. You don't have permission to view Bill of Materials.";
    header('Location: dashboard.php');
    exit();
}

// Get user permissions for UI control
$permissions = [
    'view' => hasPermission('inventory.bom.view'),
    'create' => hasPermission('inventory.bom.create'),
    'edit' => hasPermission('inventory.bom.edit'),
    'delete' => hasPermission('inventory.bom.delete')
];
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Bill of Materials</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-list"></i> Bill of Materials
                    <?php if ($permissions['create']): ?>
                    <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addBOMModal">
                        <i class="fa fa-plus"></i> Create BOM
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel-body">
                <div class="remove-messages"></div>

                <table class="table table-striped" id="bomTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Raw Material</th>
                            <th>Quantity Required</th>
                            <th>Unit</th>
                            <th>Wastage %</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($permissions['create']): ?>
<!-- Add BOM Modal -->
<div class="modal fade" id="addBOMModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form class="form-horizontal" id="submitBOMForm" action="php_action/createBOM.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Create Bill of Materials</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label col-sm-3">Product:</label>
                        <div class="col-sm-9">
                            <select class="form-control product-select" name="product_id" required>
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
                        <label class="control-label col-sm-3">Materials Required:</label>
                        <div class="col-sm-9">
                            <table class="table table-bordered" id="bomMaterialsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 40%">Raw Material</th>
                                        <th style="width: 20%">Quantity Required</th>
                                        <th style="width: 15%">Unit</th>
                                        <th style="width: 15%">Wastage %</th>
                                        <th style="width: 10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select class="form-control material-select" name="raw_material_id[]" required>
                                                <option value="">Select Raw Material</option>
                                                <?php
                                                $sql = "SELECT id, material_code, name, unit, current_stock FROM raw_materials WHERE status = 'active' ORDER BY material_code ASC";
                                                $result = $connect->query($sql);
                                                while($row = $result->fetch_assoc()) {
                                                    echo "<option value='".$row['id']."' data-unit='".$row['unit']."'>".$row['material_code']." - ".$row['name']." (Stock: ".$row['current_stock']." ".$row['unit'].")</option>";
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control quantity-required" name="quantity[]" step="0.01" min="0.01" required />
                                        </td>
                                        <td class="material-unit text-center">-</td>
                                        <td>
                                            <input type="number" class="form-control wastage-percent" name="wastage[]" step="0.01" min="0" max="100" value="0" required />
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm remove-material">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-success" id="addMaterialRow">
                                <i class="fa fa-plus"></i> Add Material
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createBOMBtn">Create BOM</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($permissions['edit']): ?>
<!-- Edit BOM Modal -->
<div class="modal fade" id="editBOMModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal content will be loaded dynamically -->
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Custom CSS -->
<style>
    .material-unit {
        line-height: 34px;
        font-weight: bold;
        vertical-align: middle !important;
    }
    .wastage-percent {
        width: 100%;
    }
    .select2-container {
        width: 100% !important;
    }
    .material-item {
        padding: 5px;
    }
    .material-item strong {
        display: block;
        margin-bottom: 3px;
    }
    .material-item small {
        color: #666;
    }
    .text-danger {
        color: #d9534f;
    }
    .text-success {
        color: #5cb85c;
    }
    #bomMaterialsTable td {
        vertical-align: middle;
    }
    .select2-container--default .select2-selection--single {
        height: 34px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 32px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px;
    }
    .modal-lg {
        width: 90%;
        max-width: 1200px;
    }
</style>

<!-- Include Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Include custom JavaScript -->
<script>
// Pass permissions to JavaScript
const permissions = {
    view: <?php echo json_encode($permissions['view']); ?>,
    create: <?php echo json_encode($permissions['create']); ?>,
    edit: <?php echo json_encode($permissions['edit']); ?>,
    delete: <?php echo json_encode($permissions['delete']); ?>
};
</script>
<script src="js/bill_of_materials.js"></script>

<?php require_once 'includes/footer.php'; ?> 