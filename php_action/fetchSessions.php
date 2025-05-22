<?php
require_once 'core.php';
require_once '../includes/session.php';
require_once 'telegram_notification.php';

// Check if user is admin
if (!isset($_SESSION['roleId']) || $_SESSION['roleId'] !== 2) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied'
    ]);
    exit();
}

$response = array(
    'success' => false,
    'messages' => '',
    'sessions' => array()
);

try {
    // Get all active sessions from the sessions table
    $sql = "SELECT s.*, u.username 
            FROM sessions s 
            JOIN users u ON s.user_id = u.user_id 
            WHERE s.last_activity > (NOW() - INTERVAL 30 MINUTE) 
            ORDER BY s.last_activity DESC";
    $result = $connect->query($sql);

    if ($result) {
        $activeSessions = array();
        while ($row = $result->fetch_assoc()) {
            $session = array(
                'session_id' => $row['session_id'],
                'user_id' => $row['user_id'],
                'username' => $row['username'],
                'ip_address' => $row['ip_address'],
                'user_agent' => $row['user_agent'],
                'last_activity' => strtotime($row['last_activity']),
                'expires_at' => strtotime($row['last_activity']) + (30 * 60) // 30 minutes from last activity
            );
            $activeSessions[] = $session;
            $response['sessions'][] = $session;
        }
        
        // Send Telegram notification about active sessions
        if (!empty($activeSessions)) {
            $message = "<b>🔵 Active Sessions Report</b>\n\n";
            foreach ($activeSessions as $session) {
                $message .= "👤 User: " . $session['username'] . "\n";
                $message .= "🌐 IP: " . $session['ip_address'] . "\n";
                $message .= "⏰ Last Activity: " . date('Y-m-d H:i:s', $session['last_activity']) . "\n";
                $message .= "🔄 Expires: " . date('Y-m-d H:i:s', $session['expires_at']) . "\n";
                $message .= "📱 Device: " . $session['user_agent'] . "\n\n";
            }
            sendTelegramNotification($message);
        }
        
        $response['success'] = true;
    } else {
        throw new Exception("Error fetching sessions: " . $connect->error);
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 