<?php
require_once 'core.php';
require_once 'classes/ConfigurationManager.php';

class SessionSecurity {
    private $config;
    private $sessionTimeout = 1800; // 30 minutes
    private $maxConcurrentSessions = 3;
    private $errors = [];

    public function __construct() {
        $this->config = ConfigurationManager::getInstance();
        
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', $this->sessionTimeout);
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function validateSession() {
        if (!isset($_SESSION['userId'])) {
            return false;
        }

        // Check session age
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $this->sessionTimeout)) {
            $this->destroySession();
            return false;
        }

        // Check IP binding
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            $this->destroySession();
            return false;
        }

        // Check user agent
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            $this->destroySession();
            return false;
        }

        // Update last activity
        $_SESSION['last_activity'] = time();

        return true;
    }

    public function createSession($userId, $roleId) {
        // Check for concurrent sessions
        if (!$this->checkConcurrentSessions($userId)) {
            $this->errors[] = "Maximum concurrent sessions reached";
            return false;
        }

        // Generate new session ID
        session_regenerate_id(true);

        // Set session data
        $_SESSION['userId'] = $userId;
        $_SESSION['role_id'] = $roleId;
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['last_activity'] = time();
        $_SESSION['session_token'] = bin2hex(random_bytes(32));

        // Log session creation
        $this->logSessionActivity($userId, 'login');

        return true;
    }

    public function destroySession() {
        if (isset($_SESSION['userId'])) {
            $userId = $_SESSION['userId'];
            $this->logSessionActivity($userId, 'logout');
        }

        // Clear session data
        $_SESSION = array();

        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy session
        session_destroy();
    }

    private function checkConcurrentSessions($userId) {
        // Get current sessions from database
        $conn = new mysqli(
            $this->config->get('db_host'),
            $this->config->get('db_user'),
            $this->config->get('db_password'),
            $this->config->get('db_name')
        );

        if ($conn->connect_error) {
            $this->errors[] = "Connection failed: " . $conn->connect_error;
            return false;
        }

        $stmt = $conn->prepare("SELECT COUNT(*) FROM active_sessions WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_row()[0];

        return $count < $this->maxConcurrentSessions;
    }

    private function logSessionActivity($userId, $action) {
        $conn = new mysqli(
            $this->config->get('db_host'),
            $this->config->get('db_user'),
            $this->config->get('db_password'),
            $this->config->get('db_name')
        );

        if ($conn->connect_error) {
            $this->errors[] = "Connection failed: " . $conn->connect_error;
            return;
        }

        // Create sessions table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS active_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$conn->query($sql)) {
            $this->errors[] = "Error creating sessions table: " . $conn->error;
            return;
        }

        if ($action === 'login') {
            $stmt = $conn->prepare("INSERT INTO active_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $userId, session_id(), $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        } else {
            $stmt = $conn->prepare("DELETE FROM active_sessions WHERE user_id = ? AND session_id = ?");
            $stmt->bind_param("is", $userId, session_id());
        }

        if (!$stmt->execute()) {
            $this->errors[] = "Error logging session activity: " . $stmt->error;
        }
    }

    public function getErrors() {
        return $this->errors;
    }
}

// Handle session validation
if (isset($_GET['validate'])) {
    $sessionSecurity = new SessionSecurity();
    $isValid = $sessionSecurity->validateSession();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'is_valid' => $isValid,
        'errors' => $sessionSecurity->getErrors()
    ]);
}
// Handle session creation
elseif (isset($_POST['create'])) {
    $sessionSecurity = new SessionSecurity();
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['user_id']) && isset($data['role_id'])) {
        $success = $sessionSecurity->createSession($data['user_id'], $data['role_id']);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'errors' => $sessionSecurity->getErrors()]);
        }
    } else {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID and role ID are required']);
    }
}
// Handle session destruction
elseif (isset($_POST['destroy'])) {
    $sessionSecurity = new SessionSecurity();
    $sessionSecurity->destroySession();

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 