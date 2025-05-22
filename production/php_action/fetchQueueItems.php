<?php
require_once 'core.php';

// Check if user has admin privileges
if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

try {
    $sql = "SELECT 
                nq.queue_id,
                n.type,
                CONCAT(u.username, ' (', u.email, ')') as user_name,
                nq.channel,
                nq.status,
                nq.attempts,
                nq.created_at,
                nq.last_attempt,
                nq.error_message
            FROM notification_queue nq
            JOIN notifications n ON n.notification_id = nq.notification_id
            JOIN users u ON u.user_id = nq.user_id
            ORDER BY nq.queue_id DESC";

    $result = $connect->query($sql);
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 