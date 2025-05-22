<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';

// Check if user has admin role
if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'admin') {
    header('location: dashboard.php');
    exit();
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-user-shield"></i> Role Management
                    <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addRoleModal">
                        <i class="fa fa-plus"></i> Add New Role
                    </button>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="rolesTable">
                        <thead>
                            <tr>
                                <th>Role Name</th>
                                <th>Description</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addRoleForm" action="php_action/createRole.php" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add New Role</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="roleName">Role Name</label>
                        <input type="text" class="form-control" id="roleName" name="roleName" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Permissions</label>
                        <div class="permissions-container">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="permissions[]" value="view_dashboard"> View Dashboard
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="permissions[]" value="manage_inventory"> Manage Inventory
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="permissions[]" value="manage_production"> Manage Production
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="permissions[]" value="manage_quality"> Manage Quality Control
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="permissions[]" value="view_reports"> View Reports
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="permissions[]" value="manage_users"> Manage Users
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="permissions[]" value="manage_settings"> Manage Settings
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editRoleForm" action="php_action/updateRole.php" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Role</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editRoleId" name="roleId">
                    <div class="form-group">
                        <label for="editRoleName">Role Name</label>
                        <input type="text" class="form-control" id="editRoleName" name="roleName" required>
                    </div>
                    <div class="form-group">
                        <label for="editDescription">Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Permissions</label>
                        <div class="permissions-container">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="editPermissions[]" value="view_dashboard"> View Dashboard
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="editPermissions[]" value="manage_inventory"> Manage Inventory
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="editPermissions[]" value="manage_production"> Manage Production
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="editPermissions[]" value="manage_quality"> Manage Quality Control
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="editPermissions[]" value="view_reports"> View Reports
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="editPermissions[]" value="manage_users"> Manage Users
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="editPermissions[]" value="manage_settings"> Manage Settings
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Role Modal -->
<div class="modal fade" id="deleteRoleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteRoleForm" action="php_action/deleteRole.php" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-trash"></i> Delete Role</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="deleteRoleId" name="roleId">
                    <p>Are you sure you want to delete this role?</p>
                    <p class="text-warning"><small>This action cannot be undone. Users with this role will need to be reassigned.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
                    <button type="submit" class="btn btn-danger">Yes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script src="custom/js/roles.js"></script>