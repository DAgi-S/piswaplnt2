<?php
/**
 * Session Management Endpoint
 * Handles listing and terminating user sessions
 */

// Set security headers
require_once __DIR__ . '/../../../config/security.php';
apply_security_headers();

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/jwt/JWTHandler.php';
require_once __DIR__ . '/../../../includes/jwt/SessionManager.php';
require_once __DIR__ . '/../../../config/database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET and DELETE requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'DELETE'])) {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

try {
    // Validate access token
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('No access token provided');
    }

    $accessToken = $matches[1];
    $jwtHandler = JWTHandler::getInstance();
    
    // Validate token
    $payload = $jwtHandler->validateToken($accessToken);
    if (!$payload) {
        throw new Exception('Invalid access token');
    }

    $userId = $payload->id;
    $sessionManager = SessionManager::getInstance();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all active sessions for user
        $sessions = $sessionManager->getActiveSessions($userId);

        // Get current session ID from request headers or query parameters
        $currentSessionId = null;
        if (isset($_SERVER['HTTP_X_SESSION_ID'])) {
            $currentSessionId = $_SERVER['HTTP_X_SESSION_ID'];
        } elseif (isset($_GET['session_id'])) {
            $currentSessionId = $_GET['session_id'];
        }

        // Format session data for response
        $formattedSessions = array_map(function($session) use ($currentSessionId) {
            return [
                'session_id' => $session['session_id'],
                'ip_address' => $session['ip_address'],
                'user_agent' => $session['user_agent'],
                'last_activity' => $session['last_activity'],
                'expires_at' => $session['expires_at'],
                'is_current' => $session['session_id'] === $currentSessionId
            ];
        }, $sessions);

        echo json_encode([
            'status' => 'success',
            'sessions' => $formattedSessions
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['session_id'])) {
            throw new Exception('Session ID is required');
        }

        $sessionToTerminate = $data['session_id'];
        $currentSessionId = $data['current_session_id'] ?? null;

        // Don't allow terminating current session through this endpoint
        if ($sessionToTerminate === $currentSessionId) {
            throw new Exception('Cannot terminate current session');
        }

        // Verify the session belongs to the user
        $sessions = $sessionManager->getActiveSessions($userId);
        $sessionBelongsToUser = false;
        foreach ($sessions as $session) {
            if ($session['session_id'] === $sessionToTerminate) {
                $sessionBelongsToUser = true;
                break;
            }
        }

        if (!$sessionBelongsToUser) {
            throw new Exception('Session not found or does not belong to user');
        }

        // Terminate the specified session
        $result = $sessionManager->invalidateSession($userId, $sessionToTerminate);

        if (!$result) {
            throw new Exception('Failed to terminate session');
        }

        // Log session termination
        error_log(sprintf(
            "Session terminated: user_id=%d, session=%s, ip=%s, timestamp=%s",
            $userId,
            $sessionToTerminate,
            $_SERVER['REMOTE_ADDR'],
            date('Y-m-d H:i:s')
        ));

        echo json_encode([
            'status' => 'success',
            'message' => 'Session terminated successfully'
        ]);
    }

} catch (Exception $e) {
    $statusCode = 401;
    
    if ($e->getMessage() === 'Method not allowed') {
        $statusCode = 405;
    }

    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 