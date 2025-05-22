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
    $stats = [
        'pending' => 0,
        'processing' => 0,
        'completed' => 0,
        'failed' => 0,
        'total' => 0
    ];

    // Get counts for each status
    $sql = "SELECT status, COUNT(*) as count 
            FROM notification_queue 
            GROUP BY status";
    
    $result = $connect->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $stats[$row['status']] = (int)$row['count'];
        $stats['total'] += (int)$row['count'];
    }

    // Get additional statistics
    $sql = "SELECT 
                AVG(attempts) as avg_attempts,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT channel) as active_channels
            FROM notification_queue
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    
    $result = $connect->query($sql);
    $additionalStats = $result->fetch_assoc();

    $stats = array_merge($stats, [
        'avg_attempts' => round($additionalStats['avg_attempts'], 2),
        'unique_users' => (int)$additionalStats['unique_users'],
        'active_channels' => (int)$additionalStats['active_channels']
    ]);

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}