<?php
require_once 'php_action/db_connect.php';
require_once 'php_action/core.php';
require_once 'php_action/functions.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit();
}

// Check if user has admin access
$sql = "SELECT 1 FROM users u
        JOIN user_roles r ON u.role_id = r.role_id
        JOIN role_permissions rp ON r.role_id = rp.role_id
        JOIN permissions p ON rp.permission_id = p.permission_id
        WHERE u.user_id = ? AND p.permission_name = 'admin.manage_permissions'
        LIMIT 1";

$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $_SESSION['userId']);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $_SESSION['error'] = "You don't have permission to manage permissions";
    header('Location: access_denied.php');
    exit();
}

// Get all roles
$roles = $connect->query("SELECT role_id, role_name, description FROM user_roles ORDER BY role_name");

// Get all permissions grouped by module
$permissions = $connect->query("
    SELECT permission_id, permission_name, description, module 
    FROM permissions 
    ORDER BY module, permission_name
");

// Include header after permission check
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <ol class="breadcrumb">
                <li><a href="dashboard.php">Home</a></li>
                <li class="active">Manage Permissions</li>
            </ol>

            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading"> <i class="glyphicon glyphicon-lock"></i> Manage Permissions</div>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h3>Roles</h3>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Role Name</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($roles && $roles->num_rows > 0): ?>
                                        <?php while($role = $roles->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($role['role_name']); ?></td>
                                            <td><?php echo htmlspecialchars($role['description']); ?></td>
                                            <td>
                                                <button class="btn btn-default btn-sm edit-permissions" 
                                                        data-role-id="<?php echo $role['role_id']; ?>"
                                                        data-role-name="<?php echo htmlspecialchars($role['role_name']); ?>">
                                                    <i class="glyphicon glyphicon-edit"></i> Edit Permissions
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No roles found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h3>Available Permissions</h3>
                            <div class="permissions-list">
                                <?php
                                $current_module = '';
                                if($permissions && $permissions->num_rows > 0):
                                    while($permission = $permissions->fetch_assoc()):
                                        if ($current_module != $permission['module']):
                                            if ($current_module != '') echo '</div>';
                                            $current_module = $permission['module'];
                                            echo '<h4>' . htmlspecialchars(ucfirst($current_module)) . '</h4>';
                                            echo '<div class="module-permissions">';
                                        endif;
                                ?>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" class="permission-checkbox" 
                                                       value="<?php echo $permission['permission_id']; ?>"
                                                       data-permission-name="<?php echo htmlspecialchars($permission['permission_name']); ?>">
                                                <?php echo htmlspecialchars($permission['description']); ?>
                                            </label>
                                        </div>
                                <?php 
                                    endwhile;
                                    if ($current_module != '') echo '</div>';
                                else:
                                ?>
                                    <div class="alert alert-info">No permissions found</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for editing permissions -->
<div class="modal fade" id="editPermissionsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Edit Role Permissions</h4>
            </div>
            <div class="modal-body">
                <div id="permissionsContainer">
                    <!-- Permissions will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="savePermissions">Save changes</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var currentRoleId = null;

    $('.edit-permissions').click(function() {
        currentRoleId = $(this).data('role-id');
        var roleName = $(this).data('role-name');
        
        // Load role permissions
        $.get('php_action/get_role_permissions.php', {
            role_id: currentRoleId
        }, function(data) {
            $('#editPermissionsModal').modal('show');
            $('.modal-title').text('Edit Permissions for ' + roleName);
            
            // Reset all checkboxes
            $('.permission-checkbox').prop('checked', false);
            
            // Check the permissions this role has
            if (data.permissions) {
                data.permissions.forEach(function(permId) {
                    $('.permission-checkbox[value="' + permId + '"]').prop('checked', true);
                });
            }
        });
    });

    $('#savePermissions').click(function() {
        if (!currentRoleId) return;

        var permissions = [];
        $('.permission-checkbox:checked').each(function() {
            permissions.push($(this).val());
        });

        $.post('php_action/update_role_permissions.php', {
            role_id: currentRoleId,
            permissions: permissions
        }, function(response) {
            if (response.success) {
                $('#editPermissionsModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 