<?php
require_once dirname(__FILE__) . '/php_action/core.php';

// Check if user is logged in
if(!isset($_SESSION['userId'])) {
    header('location: login.php');
    exit();
}

// Check page permission
if (!isset($_SESSION['roleId']) || ($_SESSION['roleId'] != 2)) {
    header('location: access_denied.php');
    exit();
}

$title = "User Management";

// Set permissions based on role_id
$hasEditPermission = true;
$hasDeletePermission = true;
$hasCreatePermission = true;

// Pass permissions to JavaScript
?>
<script>
    var hasEditPermission = <?php echo json_encode($hasEditPermission); ?>;
    var hasDeletePermission = <?php echo json_encode($hasDeletePermission); ?>;
    var hasCreatePermission = <?php echo json_encode($hasCreatePermission); ?>;
</script>
<?php
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">User Management</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-users"></i> Manage Users
                    <?php if($hasCreatePermission): ?>
                    <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addUserModal">
                        <i class="fa fa-plus"></i> Add New User
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="usersTable">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addUserForm" action="php_action/createUser.php" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add New User</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username" class="control-label">Username*</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="email" class="control-label">Email*</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password" class="control-label">Password*</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword" class="control-label">Confirm Password*</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                            </div>
                            <div class="form-group">
                                <label for="role" class="control-label">Role*</label>
                                <select class="form-control" id="role" name="roleId" required>
                                    <option value="">Select Role</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="full_name" class="control-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name">
                            </div>
                            <div class="form-group">
                                <label for="phone" class="control-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="form-group">
                                <label for="language" class="control-label">Language</label>
                                <select class="form-control" id="language" name="language">
                                    <option value="en">English</option>
                                    <option value="es">Spanish</option>
                                    <option value="fr">French</option>
                                    <option value="de">German</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="timezone" class="control-label">Timezone</label>
                                <select class="form-control" id="timezone" name="timezone">
                                    <option value="UTC">UTC</option>
                                    <option value="America/New_York">Eastern Time</option>
                                    <option value="America/Chicago">Central Time</option>
                                    <option value="America/Denver">Mountain Time</option>
                                    <option value="America/Los_Angeles">Pacific Time</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="control-label">Notifications</label>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="notify_updates" value="1"> Receive Updates
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="notify_alerts" value="1"> Receive Alerts
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="notify_reports" value="1"> Receive Reports
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editUserForm" action="php_action/updateUser.php" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit User</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editUserId" name="userId">
                    <div class="form-group">
                        <label for="editUsername">Username</label>
                        <input type="text" class="form-control" id="editUsername" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="editEmail">Email</label>
                        <input type="email" class="form-control" id="editEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="editPassword">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="editPassword" name="password">
                    </div>
                    <div class="form-group">
                        <label for="editConfirmPassword">Confirm New Password</label>
                        <input type="password" class="form-control" id="editConfirmPassword" name="confirmPassword">
                    </div>
                    <div class="form-group">
                        <label for="editRole">Role</label>
                        <select class="form-control" id="editRole" name="role" required>
                            <!-- Roles will be populated dynamically -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editStatus">Status</label>
                        <select class="form-control" id="editStatus" name="status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteUserForm" action="php_action/deleteUser.php" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-trash"></i> Delete User</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="deleteUserId" name="userId">
                    <p>Are you sure you want to delete this user?</p>
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

<script src="custom/js/users.js"></script> 