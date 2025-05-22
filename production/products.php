<?php 
require_once 'includes/header.php';

// Check base permission to view products
if (!hasPermission('product.view')) {
    $_SESSION['error'] = "Access Denied. You don't have permission to view products.";
    header('Location: dashboard.php');
    exit();
}

// Get user permissions for UI control
$permissions = [
    'view' => hasPermission('product.view'),
    'create' => hasPermission('product.create'),
    'edit' => hasPermission('product.edit'),
    'delete' => hasPermission('product.delete'),
    'export' => hasPermission('product.export'),
    'import' => hasPermission('product.import'),
    'bulk_update' => hasPermission('product.bulk_update'),
    'manage_pricing' => hasPermission('product.pricing.manage'),
    'manage_attributes' => hasPermission('product.attributes.manage'),
    'manage_categories' => hasPermission('product.category.manage'),
    'manage_brands' => hasPermission('product.brand.manage'),
    'view_inventory' => hasPermission('product.inventory.view')
];
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Products</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-cube"></i> Manage Products
                    <?php if ($permissions['create']): ?>
                    <button class="btn btn-primary pull-right add-product-btn" data-toggle="modal" data-target="#addProductModal">
                        <i class="fa fa-plus"></i> Add Product
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel-body">
                <div class="remove-messages"></div>

                <table class="table table-striped" id="productsTable">
                    <thead>
                        <tr>
                            <th>Product Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Current Stock</th>
                            <th>Production Cost</th>
                            <th>Selling Price</th>
                            <th>Status</th>
                            <th style="min-width: 150px;">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add JavaScript permissions -->
<script>
const permissions = {
    view: <?php echo json_encode($permissions['view']); ?>,
    create: <?php echo json_encode($permissions['create']); ?>,
    edit: <?php echo json_encode($permissions['edit']); ?>,
    delete: <?php echo json_encode($permissions['delete']); ?>,
    export: <?php echo json_encode($permissions['export']); ?>,
    import: <?php echo json_encode($permissions['import']); ?>,
    bulkUpdate: <?php echo json_encode($permissions['bulk_update']); ?>,
    managePricing: <?php echo json_encode($permissions['manage_pricing']); ?>,
    manageAttributes: <?php echo json_encode($permissions['manage_attributes']); ?>,
    manageCategories: <?php echo json_encode($permissions['manage_categories']); ?>,
    manageBrands: <?php echo json_encode($permissions['manage_brands']); ?>,
    viewInventory: <?php echo json_encode($permissions['view_inventory']); ?>
};
</script>

<?php if ($permissions['create']): ?>
<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitProductForm" action="php_action/createProduct.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Product</h4>
                </div>
                <div class="modal-body">
                    <div id="add-product-messages"></div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Product Code:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="productCode" name="productCode" placeholder="Enter product code" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Name:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter product name" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Category:</label>
                        <div class="col-sm-9">
                            <select class="form-control select2" id="categoryId" name="categoryId" required>
                                <option value="">Select Category</option>
                                <?php
                                $sql = "SELECT id, name FROM production_categories WHERE status = 'active' ORDER BY name ASC";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_array()) {
                                    echo "<option value='".$row['id']."'>".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Brand:</label>
                        <div class="col-sm-9">
                            <select class="form-control select2" id="brandId" name="brandId" required>
                                <option value="">Select Brand</option>
                                <?php
                                $sql = "SELECT id, name FROM production_brands WHERE status = 'active' ORDER BY name ASC";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_array()) {
                                    echo "<option value='".$row['id']."'>".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Unit:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="unit" name="unit" placeholder="e.g., pcs, kg, ltr" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Initial Stock:</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="currentStock" name="currentStock" step="0.01" min="0" value="0" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Min Stock Level:</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="minStockLevel" name="minStockLevel" step="0.01" min="0" value="0" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Production Cost:</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="productionCost" name="productionCost" step="0.01" min="0" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Selling Price:</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="sellingPrice" name="sellingPrice" step="0.01" min="0" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Description:</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
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
                    <button type="submit" class="btn btn-primary" id="createProductBtn">Create Product</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($permissions['edit']): ?>
<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editProductForm" action="php_action/editProduct.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Product</h4>
                </div>
                <div class="modal-body">
                    <div id="edit-product-messages"></div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Product Code:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editProductCode" name="editProductCode" placeholder="Product Code" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Product Name:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editProductName" name="editProductName" placeholder="Product Name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Category:</label>
                        <div class="col-sm-9">
                            <select class="form-control select2" id="editCategoryId" name="editCategoryId" required>
                                <option value="">Select Category</option>
                                <?php
                                $sql = "SELECT id, name FROM production_categories WHERE status = 'active' ORDER BY name ASC";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_array()) {
                                    echo "<option value='".$row['id']."'>".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Brand:</label>
                        <div class="col-sm-9">
                            <select class="form-control select2" id="editBrandId" name="editBrandId" required>
                                <option value="">Select Brand</option>
                                <?php
                                $sql = "SELECT id, name FROM production_brands WHERE status = 'active' ORDER BY name ASC";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_array()) {
                                    echo "<option value='".$row['id']."'>".$row['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Unit:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editUnit" name="editUnit" placeholder="Unit (e.g., pcs, kg)" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Min Stock Level:</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editMinStockLevel" name="editMinStockLevel" placeholder="Minimum Stock Level" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Production Cost:</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editProductionCost" name="editProductionCost" placeholder="Production Cost" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Selling Price:</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editSellingPrice" name="editSellingPrice" placeholder="Selling Price" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Description:</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="editDescription" name="editDescription" rows="3" placeholder="Product Description"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Status:</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editStatus" name="editStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
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

<?php if ($permissions['view_inventory']): ?>
<!-- Stock Movement History Modal -->
<div class="modal fade" id="stockMovementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="fa fa-exchange"></i> Stock Movement History</h4>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="btn-group">
                            <button class="btn btn-success" onclick="openStockInModal()">
                                <i class="fa fa-plus"></i> Add Stock In
                            </button>
                            <button class="btn btn-warning" onclick="openStockOutModal()">
                                <i class="fa fa-minus"></i> Add Stock Out
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="productStockMovementTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Reference</th>
                                <th>Notes</th>
                                <th>Created By</th>
                                <th>Current Stock</th>
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

<!-- Add Stock In Modal -->
<div class="modal fade" id="addStockInModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addStockInForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Stock In</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Warehouse</label>
                        <select class="form-control" name="warehouse_id" required>
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
                        <label>Quantity</label>
                        <input type="number" class="form-control" name="quantity" min="0.01" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Reference Type</label>
                        <select class="form-control" name="reference_type" required>
                            <option value="production">Production</option>
                            <option value="purchase">Purchase</option>
                            <option value="return">Return</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference ID</label>
                        <input type="text" class="form-control" name="reference_id" required>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    <input type="hidden" name="item_id" id="stockInItemId">
                    <input type="hidden" name="item_type" value="product">
                    <input type="hidden" name="movement_type" value="in">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Stock Out Modal -->
<div class="modal fade" id="addStockOutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addStockOutForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-minus"></i> Add Stock Out</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Warehouse</label>
                        <select class="form-control" name="warehouse_id" required>
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
                        <label>Current Stock</label>
                        <p class="form-control-static" id="currentStockDisplay">0</p>
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" class="form-control" name="quantity" min="0.01" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Reference Type</label>
                        <select class="form-control" name="reference_type" required>
                            <option value="sale">Sale</option>
                            <option value="damage">Damage</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference ID</label>
                        <input type="text" class="form-control" name="reference_id" required>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    <input type="hidden" name="item_id" id="stockOutItemId">
                    <input type="hidden" name="item_type" value="product">
                    <input type="hidden" name="movement_type" value="out">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Remove Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Move all scripts to the bottom, just before closing body tag -->

<!-- Include jQuery first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include Bootstrap JS -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

<!-- Include DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>

<!-- Include Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Initialize DataTables and Select2 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Toastr options
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    // Initialize Select2 with proper parent
    $('.select2').select2({
        dropdownParent: document.body,
        width: '100%'
    });

    // Initialize DataTables with safe object handling
    var table = $('#productsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'php_action/fetchProducts.php',
            type: 'POST',
            dataType: 'json',
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error);
            }
        },
        columns: [
            { data: 'product_code' },
            { data: 'name' },
            { data: 'category_name' },
            { data: 'brand_name' },
            { data: 'current_stock' },
            { data: 'production_cost' },
            { data: 'selling_price' },
            { data: 'status' },
            { data: 'action', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 10,
        responsive: true,
        language: {
            processing: 'Loading...',
            emptyTable: 'No products found',
            zeroRecords: 'No matching products found'
        },
        drawCallback: function() {
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    // Handle edit button click with safe event delegation
    $(document).on('click', '.btn-edit', function(e) {
        e.preventDefault();
        var productId = $(this).data('id');
        
        // Reset form and clear messages
        $('#editProductForm')[0].reset();
        $('#edit-product-messages').empty();
        
        // Show loading message
        $('#edit-product-messages').html('<div class="alert alert-info">' +
            '<i class="fa fa-spinner fa-spin"></i> Loading product details...</div>');
        
        // Show modal
        $('#editProductModal').modal('show');
        
        // Fetch product details
        $.ajax({
            url: 'php_action/fetchSingleProduct.php',
            type: 'POST',
            data: { id: productId },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Clear loading message
                    $('#edit-product-messages').empty();
                    
                    // Safely populate form fields
                    var data = response.data;
                    $('#productId').val(data.id || '');
                    $('#editProductCode').val(data.product_code || '');
                    $('#editProductName').val(data.name || '');
                    $('#editCategoryId').val(data.category_id || '').trigger('change');
                    $('#editBrandId').val(data.brand_id || '').trigger('change');
                    $('#editUnit').val(data.unit || '');
                    $('#editMinStockLevel').val(data.min_stock_level || '0');
                    $('#editProductionCost').val(data.production_cost || '0');
                    $('#editSellingPrice').val(data.selling_price || '0');
                    $('#editDescription').val(data.description || '');
                    $('#editStatus').val(data.status || 'active');
                } else {
                    $('#edit-product-messages').html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-times"></i></strong> ' + (response.messages || 'Error loading product details') +
                        '</div>');
                }
            },
            error: function() {
                $('#edit-product-messages').html('<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="fa fa-times"></i></strong> Error loading product details' +
                    '</div>');
            }
        });
    });

    // Handle edit form submission
    $('#editProductForm').on('submit', function(e) {
        e.preventDefault();
        
        $('#edit-product-messages').html('<div class="alert alert-info">' +
            '<i class="fa fa-spinner fa-spin"></i> Updating product...</div>');
        
        $('#editProductBtn').prop('disabled', true);
        
        $.ajax({
            url: 'php_action/editProduct.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#edit-product-messages').html('<div class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-check"></i></strong> ' + response.messages +
                        '</div>');
                    
                    setTimeout(function() {
                        $('#editProductModal').modal('hide');
                        table.ajax.reload(null, false);
                    }, 1500);
                } else {
                    $('#edit-product-messages').html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-times"></i></strong> ' + (response.messages || 'Error updating product') +
                        '</div>');
                    $('#editProductBtn').prop('disabled', false);
                }
            },
            error: function() {
                $('#edit-product-messages').html('<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="fa fa-times"></i></strong> Error updating product' +
                    '</div>');
                $('#editProductBtn').prop('disabled', false);
            }
        });
    });

    // Handle status change
    window.changeStatus = function(productId, newStatus) {
        if(confirm('Are you sure you want to change the status?')) {
            $.ajax({
                url: 'php_action/changeProductStatus.php',
                type: 'POST',
                data: {
                    id: productId,
                    status: newStatus
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        table.ajax.reload(null, false);
                        $('.remove-messages').html('<div class="alert alert-success">' +
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                            '<strong><i class="fa fa-check"></i></strong> Product status changed successfully</div>');
                    } else {
                        alert(response.message || 'Error changing product status');
                    }
                },
                error: function() {
                    alert('Error changing product status');
                }
            });
        }
    };

    // Modal cleanup
    $('#editProductModal').on('hidden.bs.modal', function() {
        $('#editProductForm')[0].reset();
        $('#edit-product-messages').empty();
        $('#editProductBtn').prop('disabled', false);
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    });

    // Add Product Form Submit Handler
    $('#submitProductForm').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous messages
        $('#add-product-messages').empty();
        
        // Disable submit button
        $('#createProductBtn').prop('disabled', true);
        
        $.ajax({
            url: 'php_action/createProduct.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success notification
                    toastr.success(response.messages, 'Success');
                    
                    // Reset form
                    $('#submitProductForm')[0].reset();
                    
                    // Reload the products table
                    $('#productsTable').DataTable().ajax.reload();
                    
                    // Close modal after a short delay
                    setTimeout(function() {
                        $('#addProductModal').modal('hide');
                        // Cleanup after modal is hidden
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }, 1000);
                } else {
                    // Show error notification
                    toastr.error(response.messages || 'Error adding product', 'Error');
                    // Re-enable submit button
                    $('#createProductBtn').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Add Product Error:', error);
                console.error('Server Response:', xhr.responseText);
                
                // Show error notification
                toastr.error('An error occurred while adding the product. Please try again.', 'Error');
                
                // Re-enable submit button
                $('#createProductBtn').prop('disabled', false);
            }
        });
    });
});
</script>

<!-- Include CSS files -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

<!-- Custom CSS -->
<style>
    .select2-container {
        width: 100% !important;
    }
    .label {
        display: inline-block;
        min-width: 60px;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: normal;
        text-align: center;
    }
    .btn-group-sm > .btn {
        margin-right: 2px;
    }
    .modal-body {
        padding: 20px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .alert {
        margin-bottom: 15px;
    }
    .current-stock {
        white-space: nowrap;
    }
    /* Fix for sort icons */
    table.dataTable thead .sorting,
    table.dataTable thead .sorting_asc,
    table.dataTable thead .sorting_desc {
        background-image: none;
    }
    table.dataTable thead .sorting:after {
        content: "\f0dc";
        font-family: FontAwesome;
        margin-left: 5px;
        color: #ddd;
    }
    table.dataTable thead .sorting_asc:after {
        content: "\f0de";
        font-family: FontAwesome;
        margin-left: 5px;
    }
    table.dataTable thead .sorting_desc:after {
        content: "\f0dd";
        font-family: FontAwesome;
        margin-left: 5px;
    }
</style>

<?php require_once 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize all dropdowns
    $('.dropdown-toggle').dropdown();
    
    // Ensure dropdowns work on mobile
    $('.navbar-toggle').on('click', function() {
        $('.navbar-collapse').toggleClass('in');
    });
    
    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });
});
</script> 