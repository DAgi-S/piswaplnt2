<?php
// Include core.php if not already included
require_once 'core.php';

class Middleware {
    private $connect;
    private $session;
    
    public function __construct($db) {
        $this->connect = $db;
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Store session for internal use
        $this->session = $_SESSION;
    }

    // Validate user session
    public function validateSession() {
        if(!isset($this->session['userId'])) {
            header('location: ../login.php');
            exit();
        }
    }

    // Check user permission using core.php's hasPermission
    public function checkPermission($permissionName) {
        return hasPermission($permissionName);
    }

    // Get user role name
    public function getUserRoleName() {
        if (!isset($this->session['roleId'])) {
            return null;
        }
        
        $sql = "SELECT role_name FROM user_roles WHERE role_id = ?";
        $stmt = $this->connect->prepare($sql);
        $stmt->bind_param("i", $this->session['roleId']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row ? $row['role_name'] : null;
    }

    // Check user role permission
    public function checkUserRole($requiredRole) {
        if(!isset($this->session['roleId']) || $this->session['roleId'] != $requiredRole) {
            header('location: ../dashboard.php');
            exit();
        }
    }
}

// Initialize middleware if not in a class context
if (!isset($middleware) && isset($connect)) {
    $middleware = new Middleware($connect);
    $middleware->validateSession();
} 