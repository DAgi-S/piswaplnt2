<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Debug log
error_log("Received POST data: " . print_r($_POST, true));

// Check for either new granular permission or legacy permission
if(!hasPermission('manage_roles') && !hasPermission('permission.assign')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access Denied: Insufficient permissions. Required: permission.assign or manage_roles'
    ]);
    exit();
}

// Additional module-specific permission checks
$modulePermissions = [
    'purchase' => ['purchase.manage', 'purchase.view'],
    'quotation' => ['quotation.manage', 'quotation.view'],
    'business' => ['business.manage', 'business.view']
];

// Additional security check for system roles (1 = Super Admin, 2 = Admin)
if(isset($_POST['role_id']) && ($_POST['role_id'] == 1 || $_POST['role_id'] == 2)) {
    if(!hasPermission('permission.manage')) {
        echo json_encode([
            'success' => false,
            'message' => 'Access Denied: Cannot modify system role permissions'
        ]);
        exit();
    }
}

// Validate input
if(!isset($_POST['role_id']) || !is_numeric($_POST['role_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid role ID provided'
    ]);
    exit();
}

$roleId = intval($_POST['role_id']);

// Check if role exists
$stmt = $connect->prepare("SELECT role_id, role_name FROM user_roles WHERE role_id = ?");
$stmt->bind_param("i", $roleId);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Role not found'
    ]);
    exit();
}

$roleData = $result->fetch_assoc();

// Validate permissions array
$permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
if(!is_array($permissions)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid permissions format'
    ]);
    exit();
}

// Debug log
error_log("Original permissions array: " . print_r($permissions, true));

// Convert all permission IDs to integers and remove any empty/invalid values
$permissions = array_filter(array_map('intval', $permissions), function($value) {
    return $value > 0;
});

// Debug log
error_log("Filtered permissions array: " . print_r($permissions, true));

if(!empty($permissions)) {
    // First, get all valid permission IDs from the database
    $placeholders = str_repeat('?,', count($permissions) - 1) . '?';
    $stmt = $connect->prepare("SELECT permission_id, permission_name, module FROM permissions WHERE permission_id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($permissions)), ...$permissions);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $validPermissions = [];
    $permissionDetails = [];
    while($row = $result->fetch_assoc()) {
        $validPermissions[] = $row['permission_id'];
        $permissionDetails[] = $row;
    }
    
    // Debug log
    error_log("Valid permissions from DB: " . print_r($validPermissions, true));
    
    // Find invalid permissions
    $invalidPermissions = array_diff($permissions, $validPermissions);
    if(!empty($invalidPermissions)) {
        error_log("Invalid permissions found: " . print_r($invalidPermissions, true));
        echo json_encode([
            'success' => false,
            'message' => 'Invalid permission IDs found: ' . implode(', ', $invalidPermissions),
            'debug' => [
                'invalid_permissions' => $invalidPermissions,
                'submitted_permissions' => $permissions,
                'valid_permissions' => $validPermissions
            ]
        ]);
        exit();
    }
    
    // Use only valid permissions
    $permissions = $validPermissions;
}

try {
    // Start transaction
    $connect->begin_transaction();

    // Delete existing permissions for the role
    $stmt = $connect->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->bind_param("i", $roleId);
    if(!$stmt->execute()) {
        throw new Exception("Error deleting existing permissions: " . $stmt->error);
    }

    // Insert new permissions
    if(!empty($permissions)) {
        $values = array_fill(0, count($permissions), "(?, ?)");
        $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES " . implode(", ", $values);
        
        // Debug log
        error_log("SQL Query: " . $sql);
        
        // Prepare the parameters for binding
        $params = [];
        $types = '';
        foreach($permissions as $permissionId) {
            $types .= 'ii'; // i for role_id, i for permission_id
            $params[] = $roleId;
            $params[] = $permissionId;
        }
        
        // Debug log
        error_log("Params for insert: " . print_r($params, true));
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if(!$stmt->execute()) {
            throw new Exception("Error inserting new permissions: " . $stmt->error);
        }
    }

    // Commit transaction
    $connect->commit();
    
    // Log the permission update
    $user_id = isset($_SESSION['userId']) ? $_SESSION['userId'] : 0;
    $log_message = sprintf(
        "Permissions updated for role '%s' (ID: %d) by user ID: %d. Total permissions: %d",
        $roleData['role_name'],
        $roleId,
        $user_id,
        count($permissions)
    );
    error_log($log_message);
    
    // Add module-specific validation
    if(!empty($permissionDetails)) {
        $userModuleAccess = [];
        foreach($modulePermissions as $module => $requiredPerms) {
            $userModuleAccess[$module] = false;
            foreach($requiredPerms as $perm) {
                if(hasPermission($perm)) {
                    $userModuleAccess[$module] = true;
                    break;
                }
            }
        }

        foreach($permissionDetails as $perm) {
            $module = $perm['module'];
            if(isset($userModuleAccess[$module]) && !$userModuleAccess[$module]) {
                throw new Exception("Access Denied: Insufficient permissions for module '$module'");
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Permissions updated successfully',
        'data' => [
            'role_id' => $roleId,
            'role_name' => $roleData['role_name'],
            'permission_count' => count($permissions),
            'permissions' => $permissionDetails
        ]
    ]);
    
} catch(Exception $e) {
    // Rollback on error
    $connect->rollback();
    
    error_log("Error updating permissions: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => "Failed to update permissions: " . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} 