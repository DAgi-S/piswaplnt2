<?php
require_once 'php_action/core.php';

// Debug information
error_log("Debug: Starting settings.php");
error_log("Debug: Session data - " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    error_log("Debug: No user ID in session");
    header('Location: login.php');
    exit();
}

// Get user's role and permissions from database
$userId = $_SESSION['userId'];
$sql = "SELECT u.user_id, u.username, u.role_id, ur.role_name, p.permission_name, p.module 
        FROM users u 
        JOIN user_roles ur ON u.role_id = ur.role_id 
        JOIN role_permissions rp ON ur.role_id = rp.role_id 
        JOIN permissions p ON rp.permission_id = p.permission_id 
        WHERE u.user_id = ? AND u.status = 1";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$userPermissions = array();
$userRoles = array();
while ($row = $result->fetch_assoc()) {
    $userPermissions[] = $row['permission_name'];
    $userRoles['role_name'] = $row['role_name'];
    $userRoles['role_id'] = $row['role_id'];
}

error_log("Debug: User ID: " . $userId);
error_log("Debug: User Role: " . print_r($userRoles, true));
error_log("Debug: User Permissions: " . print_r($userPermissions, true));

// Check for settings access
$hasAccess = false;
foreach ($userPermissions as $perm) {
    error_log("Debug: Checking permission: " . $perm);
    if (strpos($perm, 'settings.') === 0 || $perm === 'settings_access' || $perm === 'system.settings.access') {
        $hasAccess = true;
        error_log("Debug: Access granted by permission: " . $perm);
        break;
    }
}

if (!$hasAccess) {
    // Try direct database check as fallback
    $checkSql = "SELECT 1 FROM users u 
                 JOIN user_roles ur ON u.role_id = ur.role_id 
                 JOIN role_permissions rp ON ur.role_id = rp.role_id 
                 JOIN permissions p ON rp.permission_id = p.permission_id 
                 WHERE u.user_id = ? AND u.status = 1 
                 AND (p.module = 'Settings' OR p.permission_name LIKE 'settings.%' 
                     OR p.permission_name IN ('settings_access', 'system.settings.access'))
                 LIMIT 1";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $hasAccess = $checkResult->num_rows > 0;
    error_log("Debug: Direct DB check result: " . ($hasAccess ? 'Yes' : 'No'));
}

error_log("Debug: Final Has Access: " . ($hasAccess ? 'Yes' : 'No'));

if (!$hasAccess) {
    $_SESSION['error'] = "Access denied. Settings privileges required.";
    error_log("Debug: Access denied for user " . $userId);
    header('Location: dashboard.php');
    exit();
}

require_once 'includes/header.php';

// Debug information
error_log("User ID: " . $_SESSION['userId']);
error_log("Has Settings Access: " . (hasPermission('settings.access') || hasPermission('settings_access') ? 'Yes' : 'No'));
?>

<script>
    // Pass PHP permissions to JS for UI control
    var canManageCategories = <?php echo json_encode(hasPermission('settings.categories.manage')); ?>;
    var canManageBrands = <?php echo json_encode(hasPermission('settings.brands.manage')); ?>;
    var canManageUnits = <?php echo json_encode(hasPermission('settings.units.manage')); ?>;
    var canManageTax = <?php echo json_encode(hasPermission('settings.tax.manage')); ?>;
    var canManageCurrency = <?php echo json_encode(hasPermission('settings.currency.manage')); ?>;
    var canManageCompany = <?php echo json_encode(hasPermission('settings.company.manage')); ?>;
</script>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Settings</li>
        </ol>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"> <i class="glyphicon glyphicon-wrench"></i> Settings</div>
            </div>

            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#categories" aria-controls="categories" role="tab" data-toggle="tab">
                                    <i class="fa fa-list"></i> Categories
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#brands" aria-controls="brands" role="tab" data-toggle="tab">
                                    <i class="fa fa-trademark"></i> Brands
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#units" aria-controls="units" role="tab" data-toggle="tab">
                                    <i class="fa fa-balance-scale"></i> Units
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#tax" aria-controls="tax" role="tab" data-toggle="tab">
                                    <i class="fa fa-percent"></i> Tax Settings
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#currency" aria-controls="currency" role="tab" data-toggle="tab">
                                    <i class="fa fa-money"></i> Currency Settings
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#company" aria-controls="company" role="tab" data-toggle="tab">
                                    <i class="fa fa-building"></i> Company Information
                                </a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <!-- Categories Tab -->
                            <div role="tabpanel" class="tab-pane active" id="categories">
                                <br>
                                <div class="row">
                                    <div class="col-md-12">
                                        <?php if (hasPermission('settings.categories.manage')): ?>
                                        <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addCategoryModal">
                                            <i class="fa fa-plus"></i> Add Category
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <br>
                                <table id="categoriesTable" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                            <th>Table Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>

                            <!-- Brands Tab -->
                            <div role="tabpanel" class="tab-pane" id="brands">
                                <br>
                                <div class="row">
                                    <div class="col-md-12">
                                        <?php if (hasPermission('settings.brands.manage')): ?>
                                        <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addBrandModal">
                                            <i class="fa fa-plus"></i> Add Brand
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <br>
                                <table id="brandsTable" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                            <th>Table Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>

                            <!-- Units Tab -->
                            <div role="tabpanel" class="tab-pane" id="units">
                                <br>
                                <div class="row">
                                    <div class="col-md-12">
                                        <?php if (hasPermission('settings.units.manage')): ?>
                                        <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addUnitModal">
                                            <i class="fa fa-plus"></i> Add Unit
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <br>
                                <table id="manageUnitsTable" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Abbreviation</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>

                            <!-- Tax Settings Tab -->
                            <div role="tabpanel" class="tab-pane" id="tax">
                                <br>
                                <div class="row">
                                    <div class="col-md-12">
                                        <?php if (hasPermission('settings.tax.manage')): ?>
                                        <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addTaxModal">
                                            <i class="fa fa-plus"></i> Add Tax Rate
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <br>
                                <table id="taxTable" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Rate (%)</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>

                            <!-- Currency Settings Tab -->
                            <div role="tabpanel" class="tab-pane" id="currency">
                                <br>
                                <?php if (hasPermission('settings.currency.manage')): ?>
                                <form id="currencySettingsForm" action="php_action/updateCurrencySettings.php" method="POST">
                                    <div class="form-group">
                                        <label>Default Currency</label>
                                        <select name="default_currency" class="form-control" required>
                                            <option value="USD">US Dollar (USD)</option>
                                            <option value="ETB">Ethiopian Birr (ETB)</option>
                                            <option value="GBP">British Pound (GBP)</option>
                                            <option value="JPY">Japanese Yen (JPY)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Currency Position</label>
                                        <select name="currency_position" class="form-control" required>
                                            <option value="left">Left (br99.99)</option>
                                            <option value="right">Right (99.99br)</option>
                                            <option value="left_space">Left with space (br 99.99)</option>
                                            <option value="right_space">Right with space (99.99 br)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Thousand Separator</label>
                                        <input type="text" name="thousand_separator" class="form-control" maxlength="1" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Decimal Separator</label>
                                        <input type="text" name="decimal_separator" class="form-control" maxlength="1" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Number of Decimals</label>
                                        <input type="number" name="decimals" class="form-control" min="0" max="4" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Save Currency Settings</button>
                                </form>
                                <?php else: ?>
                                <div class="alert alert-warning">You do not have permission to manage currency settings.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Company Information Tab -->
                            <div role="tabpanel" class="tab-pane" id="company">
                                <br>
                                <?php if (hasPermission('settings.company.manage')): ?>
                                <form id="companyInfoForm" action="php_action/updateCompanyInfo.php" method="post" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="company_name">Company Name</label>
                                        <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Enter company name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="company_address">Company Address</label>
                                        <textarea class="form-control" id="company_address" name="company_address" rows="3" placeholder="Enter company address"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="company_phone">Phone Number</label>
                                        <input type="text" class="form-control" id="company_phone" name="company_phone" placeholder="Enter phone number">
                                    </div>
                                    <div class="form-group">
                                        <label for="company_email">Email Address</label>
                                        <input type="email" class="form-control" id="company_email" name="company_email" placeholder="Enter email address">
                                    </div>
                                    <div class="form-group">
                                        <label for="company_website">Website</label>
                                        <input type="url" class="form-control" id="company_website" name="company_website" placeholder="Enter website URL">
                                    </div>
                                    <div class="form-group">
                                        <label for="company_tin">TIN Number</label>
                                        <input type="text" class="form-control" id="company_tin" name="company_tin" placeholder="Enter TIN number">
                                    </div>
                                    <div class="form-group">
                                        <label for="company_logo">Company Logo</label>
                                        <input type="file" class="form-control" id="company_logo" name="company_logo" accept="image/*">
                                        <p class="help-block">Maximum file size: 5MB. Allowed types: JPG, PNG, GIF</p>
                                        <div class="mt-3">
                                            <img src="../assets/images/company/default-logo.png" class="company-logo" alt="Company Logo" style="max-width: 200px;">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </form>
                                <?php else: ?>
                                <div class="alert alert-warning">You do not have permission to manage company information.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include all modal forms -->

<!-- Add Category Modal (Dynamic) -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addCategoryForm" method="POST" action="php_action/createCategoryOnSetting.php">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-plus"></i> Add Category</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="categoryTableSelect">Select Category Type</label>
            <select class="form-control" id="categoryTableSelect" name="category_table" required>
              <option value="categories">Product/Service Categories</option>
              <option value="digital_categories">Digital Categories</option>
              <option value="payment_categories">Payment Categories</option>
              <option value="production_categories">Production Categories</option>
              <option value="raw_material_categories">Raw Material Categories</option>
              <option value="system_config_categories">System Config Categories</option>
            </select>
          </div>

          <!-- Dynamic form sections -->
          <div id="form-categories" class="category-form-section" style="display:none;">
            <div class="form-group">
              <label>Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Type</label>
              <select name="type" class="form-control" required>
                <option value="product">Product</option>
                <option value="service">Service</option>
              </select>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control" required>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>

          <div id="form-digital_categories" class="category-form-section" style="display:none;">
            <div class="form-group">
              <label>Category Name</label>
              <input type="text" name="category_name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>
          </div>

          <div id="form-payment_categories" class="category-form-section" style="display:none;">
            <div class="form-group">
              <label>Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="form-group">
              <label>Type</label>
              <input type="text" name="type" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control" required>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>

          <div id="form-production_categories" class="category-form-section" style="display:none;">
            <div class="form-group">
              <label>Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control" required>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>

          <div id="form-raw_material_categories" class="category-form-section" style="display:none;">
            <div class="form-group">
              <label>Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control" required>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>

          <div id="form-system_config_categories" class="category-form-section" style="display:none;">
            <div class="form-group">
              <label>Category Name</label>
              <input type="text" name="category_name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="form-group">
              <label>Display Order</label>
              <input type="number" name="display_order" class="form-control" min="1">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Add Category</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Category Modal (Dynamic) -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editCategoryForm" method="POST" action="php_action/editCategory.php">
        <input type="hidden" name="id">
        <input type="hidden" name="category_table">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Category</h4>
        </div>
        <div class="modal-body">
          <!-- Dynamic form sections for edit, same as add -->
          <div id="edit-form-categories" class="category-form-section" style="display:none;">
            <div class="form-group">
              <label>Name</label>
              <input type="text" name="name" class="form-control">
            </div>
            <div class="form-group">
              <label>Type</label>
              <select name="type" class="form-control">
                <option value="product">Product</option>
                <option value="service">Service</option>
              </select>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
          <div id="edit-form-digital_categories" class="category-form-section" style="display:none;">
            <div class="form-group">
              <label>Category Name</label>
              <input type="text" name="category_name" class="form-control">
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>
          </div>
          <div id="edit-form-payment_categories" class="category-form-section" style="display:none;">
            <div class="form-group">
              <label>Name</label>
              <input type="text" name="name" class="form-control">
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="form-group">
              <label>Type</label>
              <input type="text" name="type" class="form-control">
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
          <div id="edit-form-production_categories" class="category-form-section" style="display:none;">
            <div class="form-group">
              <label>Name</label>
              <input type="text" name="name" class="form-control">
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
          <div id="edit-form-raw_material_categories" class="category-form-section" style="display:none;">
            <div class="form-group">
              <label>Name</label>
              <input type="text" name="name" class="form-control">
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
          <div id="edit-form-system_config_categories" class="category-form-section" style="display:none;">
            <div class="form-group">
              <label>Category Name</label>
              <input type="text" name="category_name" class="form-control">
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="form-group">
              <label>Display Order</label>
              <input type="number" name="display_order" class="form-control" min="1">
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

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addBrandForm" method="POST" action="php_action/createBrand.php">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-plus"></i> Add Brand</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control"></textarea>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" required>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Add Brand</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editBrandForm" method="POST" action="php_action/editBrand.php">
        <input type="hidden" name="id">
        <input type="hidden" name="table_name">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Brand</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control"></textarea>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" required>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
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

<!-- Add Unit Modal -->
<div class="modal fade" id="addUnitModal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><i class="fa fa-plus"></i> Add Unit</h4>
      </div>
      <form id="addUnitForm" action="php_action/createUnit.php" method="POST">
        <div class="modal-body">
          <div id="add-unit-messages"></div>
          <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Enter unit name" required>
          </div>
          <div class="form-group">
            <label for="abbreviation">Abbreviation</label>
            <input type="text" class="form-control" id="abbreviation" name="abbreviation" placeholder="Enter abbreviation" required>
          </div>
          <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter description"></textarea>
          </div>
          <div class="form-group">
            <label for="status">Status</label>
            <select class="form-control" id="status" name="status" required>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Add Unit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Unit Modal -->
<div class="modal fade" id="editUnitModal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Unit</h4>
      </div>
      <form id="editUnitForm" action="php_action/editUnit.php" method="POST">
        <div class="modal-body">
          <div id="edit-unit-messages"></div>
          <input type="hidden" id="editUnitId" name="editUnitId">
          <div class="form-group">
            <label for="editName">Name</label>
            <input type="text" class="form-control" id="editName" name="editName" placeholder="Enter unit name" required>
          </div>
          <div class="form-group">
            <label for="editAbbreviation">Abbreviation</label>
            <input type="text" class="form-control" id="editAbbreviation" name="editAbbreviation" placeholder="Enter abbreviation" required>
          </div>
          <div class="form-group">
            <label for="editDescription">Description</label>
            <textarea class="form-control" id="editDescription" name="editDescription" rows="3" placeholder="Enter description"></textarea>
          </div>
          <div class="form-group">
            <label for="editStatus">Status</label>
            <select class="form-control" id="editStatus" name="editStatus" required>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
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

<!-- Add Tax Modal -->
<div class="modal fade" id="addTaxModal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addTaxForm" method="POST" action="php_action/createTax.php">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-plus"></i> Add Tax Rate</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Rate (%)</label>
            <input type="number" name="rate" class="form-control" step="0.01" min="0" max="100" required>
          </div>
          <div class="form-group">
            <label>Type</label>
            <select name="type" class="form-control" required>
              <option value="fixed">Fixed</option>
              <option value="percentage">Percentage</option>
            </select>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" required>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Add Tax Rate</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Tax Modal -->
<div class="modal fade" id="editTaxModal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editTaxForm" method="POST" action="php_action/editTax.php">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Tax Rate</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id">
          <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Rate (%)</label>
            <input type="number" name="rate" class="form-control" step="0.01" min="0" max="100" required>
          </div>
          <div class="form-group">
            <label>Type</label>
            <select name="type" class="form-control" required>
              <option value="fixed">Fixed</option>
              <option value="percentage">Percentage</option>
            </select>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" required>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
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

<script>
$(document).ready(function() {
  function showCategoryFormSection() {
    var selected = $('#categoryTableSelect').val();
    $('.category-form-section').hide().find(':input').prop('disabled', true).prop('required', false);
    var $visibleSection = $('#form-' + selected);
    $visibleSection.show().find(':input').prop('disabled', false).prop('required', true);
  }
  $('#categoryTableSelect').on('change', showCategoryFormSection);
  $('#addCategoryModal').on('show.bs.modal', showCategoryFormSection);
});

function showEditCategoryFormSection(table, data) {
  // Hide and disable all
  $('.category-form-section').hide().find(':input').prop('disabled', true);
  var $section = $('#edit-form-' + table);
  $section.show().find(':input').prop('disabled', false);
  // Populate fields
  if (table === 'categories') {
    $section.find('input[name="name"]').val(data.name);
    $section.find('select[name="type"]').val(data.type);
    $section.find('textarea[name="description"]').val(data.description);
    $section.find('select[name="status"]').val(data.status);
  } else if (table === 'digital_categories') {
    $section.find('input[name="category_name"]').val(data.category_name);
    $section.find('textarea[name="description"]').val(data.description);
  } else if (table === 'payment_categories') {
    $section.find('input[name="name"]').val(data.name);
    $section.find('textarea[name="description"]').val(data.description);
    $section.find('input[name="type"]').val(data.type);
    $section.find('select[name="status"]').val(data.status);
  } else if (table === 'production_categories' || table === 'raw_material_categories') {
    $section.find('input[name="name"]').val(data.name);
    $section.find('textarea[name="description"]').val(data.description);
    $section.find('select[name="status"]').val(data.status);
  } else if (table === 'system_config_categories') {
    $section.find('input[name="category_name"]').val(data.category_name);
    $section.find('textarea[name="description"]').val(data.description);
    $section.find('input[name="display_order"]').val(data.display_order);
  }
}
// Patch editCategory to show correct modal and fields
function editCategory(id, table) {
  $.ajax({
    url: 'php_action/editCategory.php',
    type: 'GET',
    data: { id: id, category_table: table },
    dataType: 'json',
    success: function(response) {
      if(response.success) {
        $('#editCategoryForm input[name="id"]').val(id);
        $('#editCategoryForm input[name="category_table"]').val(table);
        showEditCategoryFormSection(table, response.data);
        $('#editCategoryModal').modal('show');
      } else {
        showAlert('error', response.message);
      }
    },
    error: function() {
      showAlert('error', 'Error occurred while fetching category data');
    }
  });
}
// On modal close, reset form
$('#editCategoryModal').on('hidden.bs.modal', function() {
  $('#editCategoryForm')[0].reset();
  $('.category-form-section').hide().find(':input').prop('disabled', true);
});

$(document).ready(function() {
    // Initialize DataTable for units
    var manageUnitsTable = $('#manageUnitsTable').DataTable({
        'ajax': 'php_action/fetchUnits.php',
        'order': [],
        'columns': [
            { 'data': 'name' },
            { 'data': 'abbreviation' },
            { 'data': 'description' },
            { 'data': 'status_label' },
            { 'data': 'action', 'orderable': false }
        ],
        'responsive': true,
        'pageLength': 10,
        'dom': '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-5"i><"col-sm-7"p>>',
        'processing': true,
        'language': {
            'processing': 'Loading...',
            'emptyTable': 'No units found',
            'zeroRecords': 'No matching units found'
        }
    });

    // Handle Add Unit Form Submission
    $('#addUnitForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Hide modal and reset form
                    $('#addUnitModal').modal('hide');
                    $('#addUnitForm')[0].reset();
                    
                    // Refresh the table
                    manageUnitsTable.ajax.reload(null, false);
                    
                    // Show success message
                    $('.alert').remove();
                    $('.page-header').after('<div class="alert alert-success alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        response.messages + '</div>');
                } else {
                    // Show error message
                    $('#add-unit-messages').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        response.messages + '</div>');
                }
            },
            error: function() {
                // Show error message
                $('#add-unit-messages').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                    'Error occurred while adding unit.</div>');
            }
        });
    });
});

// Function to edit unit
function editUnit(id) {
    $.ajax({
        url: 'php_action/fetchSelectedUnit.php',
        type: 'POST',
        data: {id: id},
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Fill the form with unit data
                $('#editUnitId').val(response.data.id);
                $('#editName').val(response.data.name);
                $('#editAbbreviation').val(response.data.abbreviation);
                $('#editDescription').val(response.data.description);
                $('#editStatus').val(response.data.status);
                
                // Show the modal
                $('#editUnitModal').modal('show');
            } else {
                // Show error message
                $('.alert').remove();
                $('.page-header').after('<div class="alert alert-danger alert-dismissible" role="alert">' +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                    response.messages + '</div>');
            }
        },
        error: function() {
            // Show error message
            $('.alert').remove();
            $('.page-header').after('<div class="alert alert-danger alert-dismissible" role="alert">' +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                'Error occurred while fetching unit data.</div>');
        }
    });
}

// Handle Edit Unit Form Submission
$(document).ready(function() {
    $('#editUnitForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Hide modal
                    $('#editUnitModal').modal('hide');
                    
                    // Refresh the table
                    $('#manageUnitsTable').DataTable().ajax.reload(null, false);
                    
                    // Show success message
                    $('.alert').remove();
                    $('.page-header').after('<div class="alert alert-success alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        response.messages + '</div>');
                } else {
                    // Show error message
                    $('#edit-unit-messages').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        response.messages + '</div>');
                }
            },
            error: function() {
                // Show error message
                $('#edit-unit-messages').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                    'Error occurred while updating unit.</div>');
            }
        });
    });
});

// Function to delete unit
function deleteUnit(id) {
    if(confirm('Are you sure you want to delete this unit?')) {
        $.ajax({
            url: 'php_action/deleteUnit.php',
            type: 'POST',
            data: {id: id},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Refresh the table
                    $('#manageUnitsTable').DataTable().ajax.reload(null, false);
                    
                    // Show success message
                    $('.alert').remove();
                    $('.page-header').after('<div class="alert alert-success alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        response.messages + '</div>');
                } else {
                    // Show error message
                    $('.alert').remove();
                    $('.page-header').after('<div class="alert alert-danger alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        response.messages + '</div>');
                }
            },
            error: function() {
                // Show error message
                $('.alert').remove();
                $('.page-header').after('<div class="alert alert-danger alert-dismissible" role="alert">' +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                    'Error occurred while deleting unit.</div>');
            }
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>

<script src="custom/js/settings.js"></script> 