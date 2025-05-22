<?php 
require_once 'php_action/core.php';
require_once 'php_action/middleware.php';
require_once 'php_action/functions.php';

// Check for either new granular permission or legacy permission for viewing roles
if(!hasPermission('role.view') && !hasPermission('permission.view')) {
    header('Location: access_denied.php');
    exit();
}

// Permission checks for specific actions
$canViewRole = hasPermission('role.view');
$canCreateRole = hasPermission('role.create');
$canEditRole = hasPermission('role.edit');
$canDeleteRole = hasPermission('role.delete');
$canRoleAssign = hasPermission('role.assign');
$canAssignPermissions = hasPermission('permission.assign');
$canRevokePermissions = hasPermission('permission.revoke');
$canViewPermissions = hasPermission('permission.view');
$canManagePermissions = hasPermission('permission.manage');

// Module-specific permission checks
$canManageSettings = hasPermission('settings.manage') || hasPermission('settings.access');
$canManageSystem = hasPermission('system.config.manage');
$canManageAPI = hasPermission('api.manage');
$canManageData = hasPermission('data.import.view') || hasPermission('data.export.view');

// New module-specific permission checks
$canManagePurchase = hasPermission('purchase.view') || hasPermission('purchase.manage');
$canManageQuotation = hasPermission('quotation.view') || hasPermission('quotation.manage');
$canManageBusiness = hasPermission('business.view') || hasPermission('business.manage');
$canExportOrderReport = hasPermission('order.report.export');

$title = "Role Management";
?>

<!DOCTYPE html>
<html>
<head>
    <?php include('includes/header.php'); ?>
    <style>
        .role-section {
            display: flex;
            gap: 20px;
            margin: 10px 0;
        }
        
        .roles-list {
            width: 250px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
        }
        
        .role-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .role-item:hover {
            background-color: #f8f9fa;
        }
        
        .role-item.active {
            background-color: #e9ecef;
            border-left: 3px solid #007bff;
        }
        
        .role-item:last-child {
            border-bottom: none;
        }

        .role-info {
            flex: 1;
            min-width: 0;
        }

        .role-name {
            font-weight: 500;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .role-description {
            display: block;
            font-size: 0.85em;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .permissions-section {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
        }
        
        .permission-group {
            margin-bottom: 15px;
            border: 1px solid #eee;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .permission-group-header {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        
        .permission-group-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .permission-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 10px;
            padding: 15px;
        }
        
        .permission-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            padding: 5px;
        }
        
        .permission-item .custom-control {
            width: 100%;
        }
        
        .permission-item .custom-control-label {
            width: 100%;
            padding-right: 10px;
        }
        
        .role-actions {
            display: flex;
            gap: 5px;
            margin-left: 10px;
        }
        
        .role-actions .btn {
            padding: 0.25rem 0.5rem;
        }
        
        .btn-add-role {
            margin-bottom: 10px;
        }
        
        .btn-save-permissions {
            margin-top: 15px;
        }
        
        .badge {
            font-size: 0.75em;
            padding: 0.25em 0.5em;
        }
        
        @media (max-width: 768px) {
            .role-section {
                flex-direction: column;
            }
            
            .roles-list {
                width: 100%;
                margin-bottom: 20px;
            }
            
            .permission-items {
                grid-template-columns: 1fr;
            }
        }
        
        @media print {
            .role-section {
                display: block;
            }
            
            .roles-list {
                width: 100%;
                margin-bottom: 20px;
            }
            
            .permission-items {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Role Management</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Role Management</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <!-- Roles List -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Roles</h3>
                                <div class="card-tools">
                                    <?php if ($canCreateRole): ?>
                                    <button type="button" class="btn btn-primary btn-sm" id="addRoleBtn">
                                        <i class="fas fa-plus"></i> Add Role
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div id="rolesList">
                                    <!-- Roles will be dynamically loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Section -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Permissions</h3>
                                <div class="card-tools">
                                    <?php if ($canAssignPermissions): ?>
                                    <button type="button" class="btn btn-success btn-sm" id="savePermissionsBtn" style="display: none;">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="permissionsContainer">
                                    <!-- Permissions will be dynamically loaded here -->
                                    <div class="text-center text-muted">
                                        <p>Select a role to view and edit permissions</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Add/Edit Role Modal -->
    <?php if ($canCreateRole || $canEditRole): ?>
    <div class="modal fade" id="roleModal" tabindex="-1" role="dialog" aria-labelledby="roleModalTitle" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalTitle">Add New Role</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="roleForm">
                        <input type="hidden" id="roleId">
                        <div class="form-group">
                            <label for="roleName">Role Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="roleName" name="roleName" required 
                                   placeholder="Enter role name">
                        </div>
                        <div class="form-group">
                            <label for="roleDescription">Description</label>
                            <textarea class="form-control" id="roleDescription" name="roleDescription" rows="3"
                                    placeholder="Enter role description"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveRoleBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include('includes/footer.php'); ?>
    
    <!-- Pass PHP variables to JavaScript -->
    <script>
        const userPermissions = {
            canViewRole: <?php echo json_encode($canViewRole); ?>,
            canCreateRole: <?php echo json_encode($canCreateRole); ?>,
            canEditRole: <?php echo json_encode($canEditRole); ?>,
            canDeleteRole: <?php echo json_encode($canDeleteRole); ?>,
            canRoleAssign: <?php echo json_encode($canRoleAssign); ?>,
            canAssignPermissions: <?php echo json_encode($canAssignPermissions); ?>,
            canRevokePermissions: <?php echo json_encode($canRevokePermissions); ?>,
            canManagePurchase: <?php echo json_encode($canManagePurchase); ?>,
            canManageQuotation: <?php echo json_encode($canManageQuotation); ?>,
            canManageBusiness: <?php echo json_encode($canManageBusiness); ?>,
            canExportOrderReport: <?php echo json_encode($canExportOrderReport); ?>,
            // Add permission view flag
            canViewPermissions: <?php echo json_encode($canViewPermissions); ?>,
            canManagePermissions: <?php echo json_encode($canManagePermissions); ?>,
            canManageSettings: <?php echo json_encode($canManageSettings); ?>,
            canManageSystem: <?php echo json_encode($canManageSystem); ?>,
            canManageAPI: <?php echo json_encode($canManageAPI); ?>,
            canManageData: <?php echo json_encode($canManageData); ?>
        };
    </script>
    <script src="custom/js/role_management.js"></script>
</body>
</html> 