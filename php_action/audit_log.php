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

class AuditLogger {
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

        // Create audit_log table if it doesn't exist
        $this->createAuditLogTable();
    }

    private function createAuditLogTable() {
        $sql = "CREATE TABLE IF NOT EXISTS audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(255) NOT NULL,
            module VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$this->conn->query($sql)) {
            $this->errors[] = "Error creating audit_log table: " . $this->conn->error;
        }
    }

    public function logAction($action, $module, $details = '') {
        if (!empty($this->errors)) {
            return false;
        }

        $userId = $_SESSION['userId'];
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $stmt = $this->conn->prepare("INSERT INTO audit_log (user_id, action, module, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $userId, $action, $module, $details, $ipAddress, $userAgent);

        if (!$stmt->execute()) {
            $this->errors[] = "Error logging action: " . $stmt->error;
            return false;
        }

        return true;
    }

    public function getLogs($filters = []) {
        if (!empty($this->errors)) {
            return ['success' => false, 'errors' => $this->errors];
        }

        $where = [];
        $params = [];
        $types = '';

        if (!empty($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
            $types .= 'i';
        }

        if (!empty($filters['module'])) {
            $where[] = "module = ?";
            $params[] = $filters['module'];
            $types .= 's';
        }

        if (!empty($filters['start_date'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['start_date'];
            $types .= 's';
        }

        if (!empty($filters['end_date'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['end_date'];
            $types .= 's';
        }

        $sql = "SELECT al.*, u.username 
                FROM audit_log al 
                JOIN users u ON al.user_id = u.user_id";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
            $types .= 'i';
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            return ['success' => false, 'errors' => [$stmt->error]];
        }

        $result = $stmt->get_result();
        $logs = [];

        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }

        return ['success' => true, 'logs' => $logs];
    }

    public function getErrors() {
        return $this->errors;
    }
}

// Handle GET request for retrieving logs
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $logger = new AuditLogger();
    $filters = [
        'user_id' => $_GET['user_id'] ?? null,
        'module' => $_GET['module'] ?? null,
        'start_date' => $_GET['start_date'] ?? null,
        'end_date' => $_GET['end_date'] ?? null,
        'limit' => $_GET['limit'] ?? 100
    ];

    $result = $logger->getLogs($filters);
    header('Content-Type: application/json');
    echo json_encode($result);
} 
// Handle POST request for logging actions
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logger = new AuditLogger();
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['action']) || !isset($data['module'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action and module are required']);
        exit();
    }

    $success = $logger->logAction(
        $data['action'],
        $data['module'],
        $data['details'] ?? ''
    );

    header('Content-Type: application/json');
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => $logger->getErrors()]);
    }
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 