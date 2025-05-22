<?php
/**
 * Session Manager
 * Handles user session management with security features
 */

class SessionManager {
    private static $instance = null;
    private $db;
    private $sessionTable = 'user_sessions';

    private function __construct() {
        $database = new PiStockDatabase();
        $this->db = $database->getConnection();
        $this->initializeSessionTable();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize session table if it doesn't exist
     */
    private function initializeSessionTable() {
        $query = "CREATE TABLE IF NOT EXISTS {$this->sessionTable} (
            id INT(11) PRIMARY KEY AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            refresh_token_hash VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent VARCHAR(255) NOT NULL,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_session (user_id, session_id),
            INDEX idx_refresh_token (refresh_token_hash),
            INDEX idx_user_sessions (user_id, is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($query);
    }

    /**
     * Create new session
     */
    public function createSession($userId, $refreshToken) {
        // Clean up expired sessions first
        $this->cleanupExpiredSessions();

        // Hash the refresh token for storage
        $refreshTokenHash = password_hash($refreshToken, PASSWORD_DEFAULT);
        
        $sessionId = bin2hex(random_bytes(32));
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $expiresAt = date('Y-m-d H:i:s', time() + JWT_REFRESH_TOKEN_EXPIRY);

        $query = "INSERT INTO {$this->sessionTable} 
                 (user_id, session_id, refresh_token_hash, ip_address, user_agent, expires_at) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('isssss', $userId, $sessionId, $refreshTokenHash, $ipAddress, $userAgent, $expiresAt);
        
        if ($stmt->execute()) {
            return $sessionId;
        }
        
        return false;
    }

    /**
     * Validate session
     */
    public function validateSession($userId, $sessionId, $refreshToken) {
        $query = "SELECT refresh_token_hash, is_active, expires_at 
                 FROM {$this->sessionTable} 
                 WHERE user_id = ? AND session_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('is', $userId, $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }

        $session = $result->fetch_assoc();
        
        // Check if session is active and not expired
        if (!$session['is_active'] || strtotime($session['expires_at']) < time()) {
            return false;
        }

        // Verify refresh token
        return password_verify($refreshToken, $session['refresh_token_hash']);
    }

    /**
     * Invalidate session
     */
    public function invalidateSession($userId, $sessionId) {
        $query = "UPDATE {$this->sessionTable} 
                 SET is_active = FALSE 
                 WHERE user_id = ? AND session_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('is', $userId, $sessionId);
        return $stmt->execute();
    }

    /**
     * Invalidate all sessions for user
     */
    public function invalidateAllSessions($userId, $exceptSessionId = null) {
        $query = "UPDATE {$this->sessionTable} 
                 SET is_active = FALSE 
                 WHERE user_id = ?";
        
        if ($exceptSessionId) {
            $query .= " AND session_id != ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('is', $userId, $exceptSessionId);
        } else {
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('i', $userId);
        }
        
        return $stmt->execute();
    }

    /**
     * Clean up expired sessions
     */
    private function cleanupExpiredSessions() {
        $query = "DELETE FROM {$this->sessionTable} 
                 WHERE expires_at < NOW() OR 
                 (is_active = FALSE AND last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR))";
        
        $this->db->query($query);
    }

    /**
     * Get active sessions for user
     */
    public function getActiveSessions($userId) {
        $query = "SELECT session_id, ip_address, user_agent, last_activity, expires_at 
                 FROM {$this->sessionTable} 
                 WHERE user_id = ? AND is_active = TRUE AND expires_at > NOW()";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        $sessions = [];
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        
        return $sessions;
    }

    /**
     * Update session activity
     */
    public function updateSessionActivity($userId, $sessionId) {
        $query = "UPDATE {$this->sessionTable} 
                 SET last_activity = CURRENT_TIMESTAMP 
                 WHERE user_id = ? AND session_id = ? AND is_active = TRUE";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('is', $userId, $sessionId);
        return $stmt->execute();
    }
} 