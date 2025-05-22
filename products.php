<?php 
// First include all necessary PHP files
require_once 'php_action/db_connect.php';
require_once 'php_action/core.php';
require_once 'php_action/functions.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize product-related permissions
$permissions = array(
    // Core Product Permissions
    'view' => hasPermission('product.view'),
    'create' => hasPermission('product.create'),
    'edit' => hasPermission('product.edit'),
    'delete' => hasPermission('product.delete'),
    
    // Categories and Brands
    'category' => array(
        'view' => hasPermission('product.category.view'),
        'manage' => hasPermission('product.category.manage')
    ),
    'brand' => array(
        'view' => hasPermission('product.brand.view'),
        'manage' => hasPermission('product.brand.manage')
    ),
    
    // Inventory and Pricing
    'inventory' => array(
        'view' => hasPermission('product.inventory.view'),
        'adjust' => hasPermission('product.inventory.adjust')
    ),
    'pricing' => array(
        'view' => hasPermission('product.pricing.view'),
        'manage' => hasPermission('product.pricing.manage')
    ),
    
    // Data Management
    'data' => array(
        'import' => hasPermission('product.import'),
        'export' => hasPermission('product.export'),
        'bulk_update' => hasPermission('product.bulk_update'),
        'attributes' => hasPermission('product.attributes.manage')
    ),
    
    // Legacy Support
    'legacy' => array(
        'view_product' => hasPermission('view_product'),
        'manage_production' => hasPermission('manage_production'),
        'manage_categories' => hasPermission('manage_categories'),
        'manage_brands' => hasPermission('manage_brands')
    )
);

// Check base access permission
if (!$permissions['view'] && !$permissions['legacy']['view_product']) {
    // Log unauthorized access attempt
    $user_id = isset($_SESSION['userId']) ? $_SESSION['userId'] : 'Guest';
    $page = $_SERVER['REQUEST_URI'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    
    error_log("Unauthorized product access attempt - User: $user_id, Page: $page, IP: $ip, Time: $timestamp");
    
    $_SESSION['error'] = 'You do not have permission to access the products page.';
    header('Location: access_denied.php');
    exit();
}

// Only include header after permission check
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading"><i class="glyphicon glyphicon-edit"></i> Manage Products</div>
                </div>
                <div class="panel-body">
                    <div class="remove-messages"></div>
                    <div id="error-messages" class="alert alert-danger" style="display:none;"></div>

                    <?php if($permissions['create']): ?>
                    <div class="div-action pull pull-right" style="padding-bottom:20px;">
                        <button class="btn btn-default button1" data-toggle="modal" id="addProductModalBtn" data-target="#addProductModal">
                            <i class="glyphicon glyphicon-plus-sign"></i> Add Product
                        </button>
                    </div>
                    <?php endif; ?>

                    <table class="table" id="manageProductTable">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Product Name</th>
                                <?php if($permissions['pricing']['manage']): ?>
                                <th>Price</th>
                                <?php endif; ?>
                                <?php if($permissions['inventory']['view']): ?>
                                <th>Current Stock</th>
                                <th>Total Purchased</th>
                                <?php endif; ?>
                                <?php if($permissions['brand']['manage']): ?>
                                <th>Brand</th>
                                <?php endif; ?>
                                <?php if($permissions['category']['manage']): ?>
                                <th>Category</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <?php if($permissions['edit'] || $permissions['delete']): ?>
                                <th>Options</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if($permissions['create']): ?>
<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitProductForm" action="php_action/createProduct.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Product</h4>
                </div>
                <div class="modal-body">
                    <div id="add-product-messages"></div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Product Code</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="productCode" name="productCode" placeholder="Product Code" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Product Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="productName" name="productName" placeholder="Product Name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Description</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="description" name="description" placeholder="Description"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Cost</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="cost" name="cost" placeholder="Cost" step="0.01">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Selling Price</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="sellingPrice" name="sellingPrice" placeholder="Selling Price" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Unit</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="unit" name="unit" placeholder="Unit" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Current Stock</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="currentStock" name="currentStock" placeholder="Current Stock" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Min Stock Level</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="minStockLevel" name="minStockLevel" placeholder="Min Stock Level" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Production Cost</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="productionCost" name="productionCost" placeholder="Production Cost" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Brand</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="brandName" name="brandName" required>
                                <option value="">~~SELECT~~</option>
                                <?php
                                $sql = "SELECT brand_id, name FROM brands WHERE status = 1 AND deleted = 0";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_array()) {
                                    echo "<option value='".$row['brand_id']."'>".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Category</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="categoryName" name="categoryName" required>
                                <option value="">~~SELECT~~</option>
                                <?php
                                $sql = "SELECT category_id, name FROM categories WHERE status = 1 AND deleted = 0";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_array()) {
                                    echo "<option value='".$row['category_id']."'>".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Status</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="productStatus" name="productStatus">
                                <option value="active">Available</option>
                                <option value="inactive">Not Available</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Product Image</label>
                        <div class="col-sm-9">
                            <input type="file" class="form-control" id="productImage" name="productImage">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createProductBtn">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if($permissions['edit']): ?>
<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editProductForm" action="php_action/editProduct.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Product</h4>
                </div>
                <div class="modal-body">
                    <div id="edit-product-messages"></div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Product Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editProductName" name="editProductName" placeholder="Product Name" required>
                        </div>
                    </div>

                    <?php if($permissions['pricing']['manage']): ?>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Price</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editPrice" name="editPrice" placeholder="Price" step="0.01" required>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($permissions['inventory']['manage']): ?>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Quantity</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editQuantity" name="editQuantity" placeholder="Quantity" required>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($permissions['brand']['manage']): ?>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Brand</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editBrandName" name="editBrandName" required>
                                <option value="">~~SELECT~~</option>
                                <?php
                                $sql = "SELECT brand_id, name FROM brands WHERE status = 1";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_array()) {
                                    echo "<option value='".$row['brand_id']."'>".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($permissions['category']['manage']): ?>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Category</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editCategoryName" name="editCategoryName" required>
                                <option value="">~~SELECT~~</option>
                                <?php
                                $sql = "SELECT category_id, name FROM categories WHERE status = 1";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_array()) {
                                    echo "<option value='".$row['category_id']."'>".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Status</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editProductStatus" name="editProductStatus">
                                <option value="1">Available</option>
                                <option value="0">Not Available</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Product Image</label>
                        <div class="col-sm-9">
                            <input type="file" class="form-control" id="editProductImage" name="editProductImage">
                        </div>
                    </div>
                    <input type="hidden" name="productId" id="productId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="editProductBtn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if($permissions['inventory']['view']): ?>
<!-- Purchase History Modal -->
<div class="modal fade" id="purchaseHistoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-time"></i> Purchase History</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-striped table-bordered" id="purchaseHistoryTable">
                            <thead>
                                <tr>
                                    <th>Purchase Date</th>
                                    <th>Purchase #</th>
                                    <th>Supplier</th>
                                    <th class="text-right">Quantity</th>
                                    <?php if($permissions['pricing']['manage']): ?>
                                    <th class="text-right">Rate</th>
                                    <th class="text-right">Total</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
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
<?php endif; ?>

<?php if($permissions['delete']): ?>
<!-- Remove Product Modal -->
<div class="modal fade" id="removeProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="glyphicon glyphicon-trash"></i> Remove Product
                </h4>
            </div>
            <div class="modal-body">
                <div class="removeProductMessages"></div>
                <p>Do you really want to remove this product?</p>
                <p class="text-warning">
                    <small>This action cannot be undone.</small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="removeProductBtn">
                    <i class="glyphicon glyphicon-trash"></i> Remove
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Pass permissions to JavaScript -->
<script>
var userPermissions = <?php echo json_encode($permissions); ?>;
</script>

<script src="custom/js/product-main.js"></script>

<?php require_once 'includes/footer.php'; ?> 