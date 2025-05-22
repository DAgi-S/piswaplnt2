<?php
require_once 'core.php';
require_once 'classes/ConfigurationManager.php';

// Check permissions
if (!isset($_SESSION['userId']) || !isset($_SESSION['role_id'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

class EnhancedPermissions {
    private $conn;
    private $config;
    private $errors = [];

    public function __construct() {
        $this->config = ConfigurationManager::getInstance();
        $this->conn = new mysqli(
            $this->config->get('db_host'),
            $this->config->get('db_user'),
            $this->config->get('db_password'),
            $this->config->get('db_name')
        );

        if ($this->conn->connect_error) {
            $this->errors[] = "Connection failed: " . $this->conn->connect_error;
        }

        // Create necessary tables if they don't exist
        $this->createPermissionTables();
    }

    private function createPermissionTables() {
        // Create granular_permissions table
        $sql = "CREATE TABLE IF NOT EXISTS granular_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            module VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_permission (name, module)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$this->conn->query($sql)) {
            $this->errors[] = "Error creating granular_permissions table: " . $this->conn->error;
        }

        // Create role_granular_permissions table
        $sql = "CREATE TABLE IF NOT EXISTS role_granular_permissions (
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES roles(role_id),
            FOREIGN KEY (permission_id) REFERENCES granular_permissions(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$this->conn->query($sql)) {
            $this->errors[] = "Error creating role_granular_permissions table: " . $this->conn->error;
        }

        // Create ip_restrictions table
        $sql = "CREATE TABLE IF NOT EXISTS ip_restrictions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            role_id INT NOT NULL,
            is_allowed BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES roles(role_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$this->conn->query($sql)) {
            $this->errors[] = "Error creating ip_restrictions table: " . $this->conn->error;
        }
    }

    public function addGranularPermission($name, $description, $module) {
        if (!empty($this->errors)) {
            return false;
        }

        $stmt = $this->conn->prepare("INSERT INTO granular_permissions (name, description, module) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $description, $module);

        if (!$stmt->execute()) {
            $this->errors[] = "Error adding permission: " . $stmt->error;
            return false;
        }

        return true;
    }

    public function assignPermissionToRole($roleId, $permissionId) {
        if (!empty($this->errors)) {
            return false;
        }

        $stmt = $this->conn->prepare("INSERT INTO role_granular_permissions (role_id, permission_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $roleId, $permissionId);

        if (!$stmt->execute()) {
            $this->errors[] = "Error assigning permission: " . $stmt->error;
            return false;
        }

        return true;
    }

    public function addIpRestriction($ipAddress, $roleId, $isAllowed = true) {
        if (!empty($this->errors)) {
            return false;
        }

        $stmt = $this->conn->prepare("INSERT INTO ip_restrictions (ip_address, role_id, is_allowed) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $ipAddress, $roleId, $isAllowed);

        if (!$stmt->execute()) {
            $this->errors[] = "Error adding IP restriction: " . $stmt->error;
            return false;
        }

        return true;
    }

    public function checkPermission($userId, $permissionName, $module) {
        if (!empty($this->errors)) {
            return false;
        }

        $sql = "SELECT 1 FROM users u
                JOIN roles r ON u.role_id = r.role_id
                JOIN role_granular_permissions rgp ON r.role_id = rgp.role_id
                JOIN granular_permissions gp ON rgp.permission_id = gp.id
                WHERE u.user_id = ? AND gp.name = ? AND gp.module = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $userId, $permissionName, $module);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }

    public function checkIpRestriction($userId, $ipAddress) {
        if (!empty($this->errors)) {
            return false;
        }

        $sql = "SELECT 1 FROM users u
                JOIN ip_restrictions ir ON u.role_id = ir.role_id
                WHERE u.user_id = ? AND ir.ip_address = ? AND ir.is_allowed = 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $userId, $ipAddress);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }

    public function getErrors() {
        return $this->errors;
    }
}

// Handle GET request for checking permissions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $permissions = new EnhancedPermissions();
    $userId = $_SESSION['userId'];
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $permissionName = $_GET['permission'] ?? '';
    $module = $_GET['module'] ?? '';

    $hasPermission = $permissions->checkPermission($userId, $permissionName, $module);
    $hasIpAccess = $permissions->checkIpRestriction($userId, $ipAddress);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'has_permission' => $hasPermission,
        'has_ip_access' => $hasIpAccess
    ]);
} 
// Handle POST request for managing permissions
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $permissions = new EnhancedPermissions();
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['action'])) {
        $success = false;
        switch ($data['action']) {
            case 'add_permission':
                $success = $permissions->addGranularPermission(
                    $data['name'],
                    $data['description'],
                    $data['module']
                );
                break;
            case 'assign_permission':
                $success = $permissions->assignPermissionToRole(
                    $data['role_id'],
                    $data['permission_id']
                );
                break;
            case 'add_ip_restriction':
                $success = $permissions->addIpRestriction(
                    $data['ip_address'],
                    $data['role_id'],
                    $data['is_allowed'] ?? true
                );
                break;
            default:
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit();
        }

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'errors' => $permissions->getErrors()]);
        }
    } else {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action is required']);
    }
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 