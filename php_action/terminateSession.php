<?php
require_once 'core.php';
require_once '../includes/session.php';

// Check if user is admin
if (!isset($_SESSION['roleId']) || $_SESSION['roleId'] !== 1) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied'
    ]);
    exit();
}

$response = array(
    'success' => false,
    'messages' => ''
);

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['session_id'])) {
        throw new Exception('Session ID is required');
    }

    $sessionId = $data['session_id'];
    
    // Don't allow terminating current session
    if ($sessionId === session_id()) {
        throw new Exception('Cannot terminate current session');
    }

    // Delete the session from database
    $sql = "DELETE FROM sessions WHERE session_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('s', $sessionId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['messages'] = 'Session terminated successfully';
        } else {
            throw new Exception('Session not found or already terminated');
        }
    } else {
        throw new Exception('Error terminating session: ' . $stmt->error);
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 