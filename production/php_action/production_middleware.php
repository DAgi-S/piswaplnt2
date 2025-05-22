<?php
require_once 'core.php';
require_once 'middleware.php';

/**
 * Production Module Middleware
 * Handles authentication, authorization, and security for production-related operations
 */

class ProductionMiddleware {
    private $connect;
    private $userId;
    private $roleId;
    private $lastRequestTime;
    private $requestCount;
    
    public function __construct($connect) {
        $this->connect = $connect;
        $this->userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
        $this->roleId = isset($_SESSION['roleId']) ? $_SESSION['roleId'] : null;
        $this->lastRequestTime = isset($_SESSION['last_request_time']) ? $_SESSION['last_request_time'] : 0;
        $this->requestCount = isset($_SESSION['request_count']) ? $_SESSION['request_count'] : 0;
    }

    /**
     * Validates production module access and enforces security measures
     */
    public function validateAccess($requiredPermission = null) {
        try {
            // Check authentication
            $this->checkAuthentication();
            
            // Validate CSRF token for POST requests
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->validateCSRFToken();
            }
            
            // Check rate limiting
            $this->checkRateLimit();
            
            // Check specific permission if required
            if ($requiredPermission) {
                if (!$this->checkPermission($requiredPermission)) {
                    throw new Exception("Permission denied: {$requiredPermission}");
                }
            }
            
            // Update request metrics
            $this->updateRequestMetrics();
            
            return true;
        } catch (Exception $e) {
            $this->handleError($e);
            return false;
        }
    }

    /**
     * Checks if user is authenticated
     */
    private function checkAuthentication() {
        if (!$this->userId || !$this->roleId) {
            throw new Exception('Authentication required');
        }
    }

    /**
     * Validates CSRF token
     */
    private function validateCSRFToken() {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid security token');
        }
    }

    /**
     * Implements rate limiting
     */
    private function checkRateLimit() {
        $currentTime = time();
        $timeWindow = 60; // 1 minute window
        $maxRequests = 100; // Maximum requests per minute
        
        if ($currentTime - $this->lastRequestTime > $timeWindow) {
            // Reset counter for new time window
            $this->requestCount = 1;
            $this->lastRequestTime = $currentTime;
        } else {
            // Increment counter
            $this->requestCount++;
            
            // Check if limit exceeded
            if ($this->requestCount > $maxRequests) {
                throw new Exception('Rate limit exceeded. Please try again later.');
            }
        }
        
        // Update session variables
        $_SESSION['last_request_time'] = $this->lastRequestTime;
        $_SESSION['request_count'] = $this->requestCount;
    }

    /**
     * Checks if user has required permission
     */
    public function checkPermission($permission) {
        try {
            $sql = "SELECT 1 FROM permissions p 
                    JOIN role_permissions rp ON p.permission_id = rp.permission_id 
                    WHERE rp.role_id = ? AND p.permission_name = ?";
                    
            $stmt = $this->connect->prepare($sql);
            $stmt->bind_param("is", $this->roleId, $permission);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return ($result->num_rows > 0);
        } catch (Exception $e) {
            error_log("Permission check error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets production permissions for the current user
     */
    public function getProductionPermissions() {
        $sql = "SELECT p.permission_name 
                FROM permissions p 
                JOIN role_permissions rp ON p.permission_id = rp.permission_id 
                WHERE rp.role_id = ? AND p.permission_name LIKE 'production.order.%'";
                
        $stmt = $this->connect->prepare($sql);
        $stmt->bind_param("i", $this->roleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $permissions = [
            'view' => false,
            'create' => false,
            'edit' => false,
            'delete' => false
        ];
        
        while ($row = $result->fetch_assoc()) {
            switch ($row['permission_name']) {
                case 'production.order.view':
                    $permissions['view'] = true;
                    break;
                case 'production.order.create':
                    $permissions['create'] = true;
                    break;
                case 'production.order.edit':
                    $permissions['edit'] = true;
                    break;
                case 'production.order.delete':
                    $permissions['delete'] = true;
                    break;
            }
        }
        
        return $permissions;
    }

    /**
     * Updates request metrics for monitoring
     */
    private function updateRequestMetrics() {
        try {
            // First check if table exists
            $checkTable = $this->connect->query("SHOW TABLES LIKE 'system_integration_logs'");
            if ($checkTable->num_rows == 0) {
                // Table doesn't exist, create it
                $sql = "CREATE TABLE IF NOT EXISTS system_integration_logs (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    user_id INT(11),
                    module VARCHAR(100),
                    endpoint VARCHAR(255),
                    request_method VARCHAR(10),
                    ip_address VARCHAR(45),
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                )";
                $this->connect->query($sql);
            }

            // Now insert the log
            $sql = "INSERT INTO system_integration_logs 
                    (user_id, module, endpoint, request_method, ip_address, created_at) 
                    VALUES (?, 'production', ?, ?, ?, NOW())";
                    
            $stmt = $this->connect->prepare($sql);
            $endpoint = $_SERVER['REQUEST_URI'];
            $method = $_SERVER['REQUEST_METHOD'];
            $ip = $_SERVER['REMOTE_ADDR'];
            
            $stmt->bind_param("isss", $this->userId, $endpoint, $method, $ip);
            $stmt->execute();
        } catch (Exception $e) {
            // Log the error but don't stop execution
            error_log("Error updating request metrics: " . $e->getMessage());
        }
    }

    /**
     * Handles and logs errors
     */
    private function handleError($exception) {
        // Log error
        error_log("Production Module Error: " . $exception->getMessage());
        
        // Log to database
        $sql = "INSERT INTO system_audit_trails 
                (user_id, action_type, table_name, record_id, old_values, new_values, ip_address, created_at) 
                VALUES (?, 'error', 'production', NULL, NULL, ?, ?, NOW())";
                
        $stmt = $this->connect->prepare($sql);
        $details = json_encode([
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $stmt->bind_param("iss", $this->userId, $details, $ip);
        $stmt->execute();
        
        // Send error response
        http_response_code(403);
        echo json_encode([
            'error' => true,
            'message' => $exception->getMessage()
        ]);
        exit();
    }

    /**
     * Generates CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Returns HTML for CSRF token input
     */
    public static function getCSRFTokenInput() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}

// Example usage in production endpoints:
/*
require_once 'production_middleware.php';

$productionMiddleware = new ProductionMiddleware($connect);

// For view operations
if ($productionMiddleware->validateAccess('view_production_orders')) {
    // Proceed with view operation
}

// For create operations
if ($productionMiddleware->validateAccess('create_production_order')) {
    // Proceed with create operation
}

// For edit operations
if ($productionMiddleware->validateAccess('edit_production_order')) {
    // Proceed with edit operation
}

// For delete operations
if ($productionMiddleware->validateAccess('delete_production_order')) {
    // Proceed with delete operation
}
*/ 