<?php
require_once '../core.php';
require_once '../ConfigurationManager.php';

// Check if user is logged in and has permission to manage permissions
if (!isset($_SESSION['userId']) || !isset($_SESSION['role_id'])) {
    die(json_encode(['success' => false, 'message' => 'Not logged in']));
}

$permissions = new EnhancedPermissions();
$response = ['success' => false, 'message' => 'Invalid action'];

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_permissions':
            if (!isset($_GET['role_id'])) {
                $response = ['success' => false, 'message' => 'Role ID not provided'];
                break;
            }
            
            $roleId = (int)$_GET['role_id'];
            $permissions = $permissions->getRolePermissions($roleId);
            $response = ['success' => true, 'permissions' => $permissions];
            break;
            
        case 'check_ip':
            if (!isset($_GET['ip'])) {
                $response = ['success' => false, 'message' => 'IP address not provided'];
                break;
            }
            
            $isAllowed = $permissions->checkIpRestriction($_GET['ip'], $_SESSION['role_id']);
            $response = ['success' => true, 'is_allowed' => $isAllowed];
            break;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'assign_permission':
            if (!isset($data['role_id']) || !isset($data['permission_id'])) {
                $response = ['success' => false, 'message' => 'Missing required parameters'];
                break;
            }
            
            $success = $permissions->assignPermission($data['role_id'], $data['permission_id']);
            $response = ['success' => $success, 'message' => $success ? 'Permission assigned successfully' : 'Failed to assign permission'];
            break;
            
        case 'revoke_permission':
            if (!isset($data['role_id']) || !isset($data['permission_id'])) {
                $response = ['success' => false, 'message' => 'Missing required parameters'];
                break;
            }
            
            $success = $permissions->revokePermission($data['role_id'], $data['permission_id']);
            $response = ['success' => $success, 'message' => $success ? 'Permission revoked successfully' : 'Failed to revoke permission'];
            break;
            
        case 'add_ip_restriction':
            if (!isset($data['role_id']) || !isset($data['ip_address']) || !isset($data['is_allowed'])) {
                $response = ['success' => false, 'message' => 'Missing required parameters'];
                break;
            }
            
            $restriction = $permissions->addIpRestriction($data['role_id'], $data['ip_address'], $data['is_allowed']);
            if ($restriction) {
                $response = ['success' => true, 'message' => 'IP restriction added successfully', 'restriction' => $restriction];
            } else {
                $response = ['success' => false, 'message' => 'Failed to add IP restriction'];
            }
            break;
            
        case 'delete_ip_restriction':
            if (!isset($data['restriction_id'])) {
                $response = ['success' => false, 'message' => 'Restriction ID not provided'];
                break;
            }
            
            $success = $permissions->deleteIpRestriction($data['restriction_id']);
            $response = ['success' => $success, 'message' => $success ? 'IP restriction deleted successfully' : 'Failed to delete IP restriction'];
            break;
    }
}

header('Content-Type: application/json');
echo json_encode($response);

class EnhancedPermissions {
    private $conn;
    
    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            die(json_encode(['success' => false, 'message' => 'Database connection failed']));
        }
    }
    
    public function getRolePermissions($roleId) {
        $stmt = $this->conn->prepare("SELECT permission_id FROM role_granular_permissions WHERE role_id = ?");
        $stmt->bind_param("i", $roleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row['permission_id'];
        }
        
        return $permissions;
    }
    
    public function assignPermission($roleId, $permissionId) {
        $stmt = $this->conn->prepare("INSERT INTO role_granular_permissions (role_id, permission_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $roleId, $permissionId);
        return $stmt->execute();
    }
    
    public function revokePermission($roleId, $permissionId) {
        $stmt = $this->conn->prepare("DELETE FROM role_granular_permissions WHERE role_id = ? AND permission_id = ?");
        $stmt->bind_param("ii", $roleId, $permissionId);
        return $stmt->execute();
    }
    
    public function addIpRestriction($roleId, $ipAddress, $isAllowed) {
        $stmt = $this->conn->prepare("INSERT INTO ip_restrictions (role_id, ip_address, is_allowed) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $roleId, $ipAddress, $isAllowed);
        
        if ($stmt->execute()) {
            $restrictionId = $this->conn->insert_id;
            $stmt = $this->conn->prepare("
                SELECT ir.*, r.role_name 
                FROM ip_restrictions ir 
                JOIN roles r ON ir.role_id = r.role_id 
                WHERE ir.id = ?
            ");
            $stmt->bind_param("i", $restrictionId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }
        
        return false;
    }
    
    public function deleteIpRestriction($restrictionId) {
        $stmt = $this->conn->prepare("DELETE FROM ip_restrictions WHERE id = ?");
        $stmt->bind_param("i", $restrictionId);
        return $stmt->execute();
    }
    
    public function checkIpRestriction($ipAddress, $roleId) {
        $stmt = $this->conn->prepare("
            SELECT is_allowed 
            FROM ip_restrictions 
            WHERE role_id = ? AND ip_address = ?
        ");
        $stmt->bind_param("is", $roleId, $ipAddress);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return (bool)$row['is_allowed'];
        }
        
        // Default to allowed if no restriction exists
        return true;
    }
    
    public function __destruct() {
        $this->conn->close();
    }
} 