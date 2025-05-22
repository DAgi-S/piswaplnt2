<?php
class User {
    private $db;
    private $userId;
    private $roleId;
    private $permissions = [];

    public function __construct() {
        global $connect;
        $this->db = $connect;
        $this->userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
        $this->roleId = isset($_SESSION['roleId']) ? $_SESSION['roleId'] : null;
        
        // If roleId is not set in session but userId is, try to get it from user_roles
        if (!$this->roleId && $this->userId) {
            $query = "SELECT role_id FROM user_roles WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $this->roleId = $row['role_id'];
                $_SESSION['roleId'] = $this->roleId; // Update session
            }
        }
        
        $this->loadPermissions();
    }

    private function loadPermissions() {
        if ($this->roleId) {
            try {
                $query = "SELECT DISTINCT p.permission_name 
                         FROM permissions p 
                         JOIN role_permissions rp ON p.permission_id = rp.permission_id 
                         WHERE rp.role_id = ?";
                $stmt = $this->db->prepare($query);
                if (!$stmt) {
                    error_log("Failed to prepare permission query: " . $this->db->error);
                    return;
                }
                
                $stmt->bind_param("i", $this->roleId);
                if (!$stmt->execute()) {
                    error_log("Failed to execute permission query: " . $stmt->error);
                    return;
                }
                
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $this->permissions[] = $row['permission_name'];
                }
                
                // Log loaded permissions for debugging
                error_log("Loaded permissions for role {$this->roleId}: " . print_r($this->permissions, true));
            } catch (Exception $e) {
                error_log("Error loading permissions: " . $e->getMessage());
            }
        } else {
            error_log("No role ID available to load permissions");
        }
    }

    public function hasPermission($permission) {
        $has_permission = in_array($permission, $this->permissions);
        error_log("Checking permission '{$permission}' for role {$this->roleId}: " . ($has_permission ? 'true' : 'false'));
        return $has_permission;
    }

    public function getId() {
        return $this->userId;
    }

    public function getRoleId() {
        return $this->roleId;
    }

    public function getPermissions() {
        return $this->permissions;
    }
} 