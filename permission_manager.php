<?php
require_once 'php_action/core.php';
require_once 'php_action/classes/ConfigurationManager.php';
require_once 'php_action/granular_permissions.php';

// Check permissions
if (!isset($_SESSION['userId']) || !isset($_SESSION['role_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user has permission to manage permissions
$permissions = new GranularPermissions();
if (!$permissions->checkPermission($_SESSION['userId'], 'manage_permissions', 'system', 'admin')) {
    header('Location: dashboard.php');
    exit();
}

// Get all roles
$conn = new mysqli(
    $config->get('db_host'),
    $config->get('db_user'),
    $config->get('db_password'),
    $config->get('db_name')
);

$roles = [];
$result = $conn->query("SELECT * FROM roles");
while ($row = $result->fetch_assoc()) {
    $roles[] = $row;
}

// Get all permissions
$permissionsList = [];
$result = $conn->query("SELECT * FROM granular_permissions ORDER BY module, section, name");
while ($row = $result->fetch_assoc()) {
    $permissionsList[] = $row;
}

// Get IP restrictions
$ipRestrictions = [];
$result = $conn->query("SELECT ir.*, r.role_name FROM ip_restrictions ir JOIN roles r ON ir.role_id = r.role_id");
while ($row = $result->fetch_assoc()) {
    $ipRestrictions[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permission Manager</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2>Permission Manager</h2>

        <!-- Role Permissions -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>Role Permissions</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <select class="form-select" id="roleSelect">
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['role_id']; ?>">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th>Section</th>
                                <th>Permission</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="permissionsTable">
                            <?php foreach ($permissionsList as $permission): ?>
                                <tr data-permission-id="<?php echo $permission['id']; ?>">
                                    <td><?php echo htmlspecialchars($permission['module']); ?></td>
                                    <td><?php echo htmlspecialchars($permission['section']); ?></td>
                                    <td><?php echo htmlspecialchars($permission['name']); ?></td>
                                    <td><?php echo htmlspecialchars($permission['description']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary permission-status">Not Assigned</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary assign-permission" style="display: none;">Assign</button>
                                        <button class="btn btn-sm btn-danger revoke-permission" style="display: none;">Revoke</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- IP Restrictions -->
        <div class="card">
            <div class="card-header">
                <h4>IP Restrictions</h4>
            </div>
            <div class="card-body">
                <form id="ipRestrictionForm" class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <select class="form-select" name="role_id" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>">
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="ip_address" placeholder="IP Address" required>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="is_allowed" required>
                                <option value="1">Allow</option>
                                <option value="0">Deny</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Add Restriction</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>IP Address</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ipRestrictionsTable">
                            <?php foreach ($ipRestrictions as $restriction): ?>
                                <tr data-restriction-id="<?php echo $restriction['id']; ?>">
                                    <td><?php echo htmlspecialchars($restriction['role_name']); ?></td>
                                    <td><?php echo htmlspecialchars($restriction['ip_address']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $restriction['is_allowed'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $restriction['is_allowed'] ? 'Allowed' : 'Denied'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-danger delete-restriction">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/permission-manager.js"></script>
</body>
</html> 