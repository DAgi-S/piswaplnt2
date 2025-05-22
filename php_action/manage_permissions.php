<?php
require_once 'db_connect.php';
require_once 'core.php';
require_once 'functions.php';

// Check if user has admin access
if (!check_access('admin.manage_permissions')) {
    $_SESSION['error'] = "You don't have permission to manage permissions";
    header('Location: ../access_denied.php');
    exit();
}

/**
 * Assign a role to a user
 */
function assign_role_to_user($user_id, $role_id) {
    global $connect;
    
    $stmt = $connect->prepare("UPDATE users SET role_id = ? WHERE user_id = ?");
    $stmt->bind_param('ii', $role_id, $user_id);
    return $stmt->execute();
}

/**
 * Get all permissions for a role
 */
function get_role_permissions($role_id) {
    global $connect;
    
    $sql = "SELECT p.permission_id, p.permission_name, p.description, p.module
            FROM permissions p
            JOIN role_permissions rp ON p.permission_id = rp.permission_id
            WHERE rp.role_id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Add permission to role
 */
function add_permission_to_role($role_id, $permission_id) {
    global $connect;
    
    $stmt = $connect->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
    $stmt->bind_param('ii', $role_id, $permission_id);
    return $stmt->execute();
}

/**
 * Remove permission from role
 */
function remove_permission_from_role($role_id, $permission_id) {
    global $connect;
    
    $stmt = $connect->prepare("DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?");
    $stmt->bind_param('ii', $role_id, $permission_id);
    return $stmt->execute();
}

/**
 * Get all available permissions
 */
function get_all_permissions() {
    global $connect;
    
    $sql = "SELECT permission_id, permission_name, description, module FROM permissions ORDER BY module, permission_name";
    return $connect->query($sql);
}

/**
 * Get all roles
 */
function get_all_roles() {
    global $connect;
    
    $sql = "SELECT role_id, role_name, description FROM user_roles ORDER BY role_name";
    return $connect->query($sql);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array('success' => false, 'message' => '');
    
    try {
        switch ($_POST['action']) {
            case 'assign_role':
                if (assign_role_to_user($_POST['user_id'], $_POST['role_id'])) {
                    $response['success'] = true;
                    $response['message'] = 'Role assigned successfully';
                }
                break;
                
            case 'add_permission':
                if (add_permission_to_role($_POST['role_id'], $_POST['permission_id'])) {
                    $response['success'] = true;
                    $response['message'] = 'Permission added successfully';
                }
                break;
                
            case 'remove_permission':
                if (remove_permission_from_role($_POST['role_id'], $_POST['permission_id'])) {
                    $response['success'] = true;
                    $response['message'] = 'Permission removed successfully';
                }
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} 