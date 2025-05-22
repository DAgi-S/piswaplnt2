<?php 
require_once 'php_action/core.php';
require_once 'php_action/middleware.php';
require_once 'php_action/functions.php';
require_once 'php_action/checkPagePermission.php';

// Check page permission
if (!isset($_SESSION['roleId']) || ($_SESSION['roleId'] !== 2 && !hasPermission('view_user'))) {
    header('location: access_denied.php');
    exit();
}

$title = "User Management";

// Set permissions
$hasEditPermission = $_SESSION['roleId'] === 2 || hasPermission('edit_user');
$hasDeletePermission = $_SESSION['roleId'] === 2 || hasPermission('delete_user');
$hasCreatePermission = $_SESSION['roleId'] === 2 || hasPermission('create_user');

// Pass permissions to JavaScript
?>
<script>
    var hasEditPermission = <?php echo json_encode($hasEditPermission); ?>;
    var hasDeletePermission = <?php echo json_encode($hasDeletePermission); ?>;
    var hasCreatePermission = <?php echo json_encode($hasCreatePermission); ?>;
</script>
<?php


?>

<!DOCTYPE html>
<html>
<head>
    <?php include('includes/header.php'); ?>
    <link rel="stylesheet" href="custom/css/user.css">
</head>
<body>
    
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <ol class="breadcrumb">
                    <li><a href="dashboard.php">Home</a></li>
                    <li class="active">User Management</li>
                </ol>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="page-heading">
                            <i class="glyphicon glyphicon-user"></i> Manage Users
                            <?php if(hasPermission('create_user')): ?>
                            <button class="btn btn-primary pull-right" id="addUserBtn">
                                <i class="glyphicon glyphicon-plus"></i> Add User
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="manageUserTable">
                                        <thead>
                                            <tr>
												<th>ID</th>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="glyphicon glyphicon-plus"></i> Add New User</h4>
                </div>
                <form id="addUserForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select class="form-control" name="roleId" required>
                                <?php
                                // Requery to avoid using a closed result set
                                $sql = "SELECT role_id, role_name FROM user_roles ORDER BY role_name ASC";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_assoc()) {
                                    echo "<option value='".$row['role_id']."'>".$row['role_name']."</option>";
                                }
                                ?>
                            </select>
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

    <!-- Edit User Modal with similar structure -->
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="glyphicon glyphicon-edit"></i> Edit User</h4>
                </div>
                <form id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="userid" id="editUserId">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" name="edituserName" id="editUsername" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="editEmail" id="editEmail" required>
                        </div>
                        <div class="form-group">
                            <label>Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" name="editPassword" id="editPassword">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select class="form-control" name="editRoleId" id="editRoleId" required>
                                <?php
                                // Requery again for edit modal
                                $sql = "SELECT role_id, role_name FROM user_roles ORDER BY role_name ASC";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_assoc()) {
                                    echo "<option value='".$row['role_id']."'>".$row['role_name']."</option>";
                                }
                                ?>
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

    <!-- Remove User Modal with similar structure -->

    <?php include('includes/footer.php'); ?>
    <script src="custom/js/user.js"></script>
</body>
</html>