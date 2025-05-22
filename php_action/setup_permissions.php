<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connect.php';
require_once 'core.php';

// Add debugging information
error_log("Starting permission setup process");
error_log("Session user ID: " . (isset($_SESSION['userId']) ? $_SESSION['userId'] : 'not set'));

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Please log in to continue']);
    exit();
}

try {
    // Check database connection
    if ($connect->connect_error) {
        throw new Exception("Database connection failed: " . $connect->connect_error);
    }
    error_log("Database connection successful");

    // Start transaction
    if (!$connect->begin_transaction()) {
        throw new Exception("Could not start transaction: " . $connect->error);
    }
    error_log("Transaction started");

    // Check if users table has role_id column
    $result = $connect->query("SHOW COLUMNS FROM users LIKE 'role_id'");
    if ($result->num_rows === 0) {
        error_log("Adding role_id column to users table");
        $connect->query("ALTER TABLE users ADD COLUMN role_id INT(11) DEFAULT NULL");
    }

    // 1. Create roles table if not exists
    $createRolesTable = "CREATE TABLE IF NOT EXISTS roles (
        role_id INT(11) NOT NULL AUTO_INCREMENT,
        role_name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (role_id)
    )";
    if (!$connect->query($createRolesTable)) {
        throw new Exception("Failed to create roles table: " . $connect->error);
    }
    error_log("Roles table created/verified");

    // 2. Create permissions table if not exists
    $createPermissionsTable = "CREATE TABLE IF NOT EXISTS permissions (
        permission_id INT(11) NOT NULL AUTO_INCREMENT,
        permission_name VARCHAR(255) NOT NULL,
        module VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (permission_id)
    )";
    if (!$connect->query($createPermissionsTable)) {
        throw new Exception("Failed to create permissions table: " . $connect->error);
    }
    error_log("Permissions table created/verified");

    // 3. Create role_permissions table if not exists
    $createRolePermissionsTable = "CREATE TABLE IF NOT EXISTS role_permissions (
        role_id INT(11) NOT NULL,
        permission_id INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (role_id, permission_id),
        FOREIGN KEY (role_id) REFERENCES roles(role_id),
        FOREIGN KEY (permission_id) REFERENCES permissions(permission_id)
    )";
    if (!$connect->query($createRolePermissionsTable)) {
        throw new Exception("Failed to create role_permissions table: " . $connect->error);
    }
    error_log("Role permissions table created/verified");

    // 4. Insert admin role if not exists
    $stmt = $connect->prepare("INSERT IGNORE INTO roles (role_name) VALUES (?)");
    if (!$stmt) {
        throw new Exception("Failed to prepare admin role statement: " . $connect->error);
    }
    $adminRole = "admin";
    if (!$stmt->bind_param("s", $adminRole)) {
        throw new Exception("Failed to bind admin role parameter: " . $stmt->error);
    }
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert admin role: " . $stmt->error);
    }
    error_log("Admin role created/verified");
    $stmt->close();

    // Get admin role ID
    $result = $connect->query("SELECT role_id FROM roles WHERE role_name = 'admin'");
    if (!$result) {
        throw new Exception("Failed to get admin role ID: " . $connect->error);
    }
    $adminRoleId = $result->fetch_assoc()['role_id'];
    error_log("Admin role ID: " . $adminRoleId);

    // 5. Insert basic permissions if not exist
    $basicPermissions = [
        ['view_system_config', 'system_config'],
        ['edit_system_config', 'system_config']
    ];

    $stmt = $connect->prepare("INSERT IGNORE INTO permissions (permission_name, module) VALUES (?, ?)");
    if (!$stmt) {
        throw new Exception("Failed to prepare permissions statement: " . $connect->error);
    }

    foreach ($basicPermissions as $permission) {
        if (!$stmt->bind_param("ss", $permission[0], $permission[1])) {
            throw new Exception("Failed to bind permission parameters: " . $stmt->error);
        }
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert permission: " . $stmt->error);
        }
        error_log("Permission created/verified: " . $permission[0]);
    }
    $stmt->close();

    // 6. Assign permissions to admin role
    $stmt = $connect->prepare("
        INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT ?, permission_id FROM permissions WHERE module = ?
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare role permissions statement: " . $connect->error);
    }

    $module = "system_config";
    if (!$stmt->bind_param("is", $adminRoleId, $module)) {
        throw new Exception("Failed to bind role permissions parameters: " . $stmt->error);
    }
    if (!$stmt->execute()) {
        throw new Exception("Failed to assign permissions to admin role: " . $stmt->error);
    }
    error_log("Permissions assigned to admin role");
    $stmt->close();

    // 7. Update user to admin if needed
    $stmt = $connect->prepare("UPDATE users SET role_id = ? WHERE user_id = ? AND (role_id IS NULL OR role_id != ?)");
    if (!$stmt) {
        throw new Exception("Failed to prepare user update statement: " . $connect->error);
    }
    
    $userId = $_SESSION['userId'];
    if (!$stmt->bind_param("iii", $adminRoleId, $userId, $adminRoleId)) {
        throw new Exception("Failed to bind user update parameters: " . $stmt->error);
    }
    if (!$stmt->execute()) {
        throw new Exception("Failed to update user role: " . $stmt->error);
    }
    error_log("User role updated if needed");
    $stmt->close();

    // Commit transaction
    if (!$connect->commit()) {
        throw new Exception("Failed to commit transaction: " . $connect->error);
    }
    error_log("Transaction committed successfully");

    echo json_encode(['success' => true, 'message' => 'Permission setup completed successfully']);

} catch (Exception $e) {
    error_log("Error in setup_permissions.php: " . $e->getMessage());
    if (isset($connect)) {
        $connect->rollback();
        error_log("Transaction rolled back");
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($connect)) {
        $connect->close();
        error_log("Database connection closed");
    }
} 