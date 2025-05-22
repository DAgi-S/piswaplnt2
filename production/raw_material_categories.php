<?php 
require_once 'includes/header.php';

// Initialize production middleware
require_once 'php_action/production_middleware.php';
$productionMiddleware = new ProductionMiddleware($connect);

// Check both new and legacy permissions
$permissions = [
    // New permission format
    'view' => $productionMiddleware->checkPermission('inventory.raw_materials.view'),
    'edit' => $productionMiddleware->checkPermission('inventory.raw_materials.edit'),
    'delete' => $productionMiddleware->checkPermission('inventory.raw_materials.delete'),
    'manage' => $productionMiddleware->checkPermission('inventory.raw_materials.manage'),
    
    // Legacy permissions for backward compatibility
    'legacy_view' => $productionMiddleware->checkPermission('inventory_raw_materials_view'),
    'legacy_edit' => $productionMiddleware->checkPermission('inventory_raw_materials_edit'),
    'legacy_delete' => $productionMiddleware->checkPermission('inventory_raw_materials_delete'),
    'legacy_manage' => $productionMiddleware->checkPermission('inventory_raw_materials_manage')
];

// Check if user has either new or legacy permission to view categories
if (!($permissions['view'] || $permissions['legacy_view'])) {
    echo '<div class="alert alert-danger">You do not have permission to view raw material categories.</div>';
    exit();
}

// Define the JavaScript permissions before any HTML output
$jsPermissions = json_encode($permissions);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Raw Material Categories</title>
    <script>
        var userPermissions = <?php echo $jsPermissions; ?>;
    </script>
</head>
<body>
    <div class="row">
        <div class="col-md-12">
            <ol class="breadcrumb">
                <li><a href="dashboard.php">Home</a></li>
                <li class="active">Raw Material Categories</li>
            </ol>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading">
                        <i class="fa fa-list"></i> Raw Material Categories
                        <?php if ($permissions['manage'] || $permissions['legacy_manage']): ?>
                        <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addCategoryModal">
                            <i class="fa fa-plus"></i> Add Category
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel-body">
                    <div class="remove-messages"></div>

                    <table class="table table-striped" id="categoriesTable">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Description</th>
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

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <form class="form-horizontal" id="submitCategoryForm" action="php_action/createMaterialCategory.php" method="POST">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><i class="fa fa-plus"></i> Add Raw Material Category</h4>
                    </div>
                    <div class="modal-body">
                        <div id="add-category-messages"></div>

                        <div class="form-group">
                            <label class="control-label col-sm-3">Category Name:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="categoryName" name="categoryName" placeholder="Category Name" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-sm-3">Description:</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Category Description"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-sm-3">Status:</label>
                            <div class="col-sm-9">
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="createCategoryBtn">Create Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <form class="form-horizontal" id="editCategoryForm" action="php_action/editMaterialCategory.php" method="POST">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Raw Material Category</h4>
                    </div>
                    <div class="modal-body">
                        <div id="edit-category-messages"></div>

                        <div class="form-group">
                            <label class="control-label col-sm-3">Category Name:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="editCategoryName" name="editCategoryName" placeholder="Category Name" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-sm-3">Description:</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="editDescription" name="editDescription" rows="3" placeholder="Category Description"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-sm-3">Status:</label>
                            <div class="col-sm-9">
                                <select class="form-control" id="editStatus" name="editStatus" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="categoryId" id="editCategoryId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="editCategoryBtn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Custom CSS -->
    <style>
        .label-active {
            background-color: #5cb85c;
        }
        .label-inactive {
            background-color: #d9534f;
        }
    </style>

    <script src="js/raw_material_categories.js"></script>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>