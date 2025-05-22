<?php
require_once 'core.php';
require_once 'classes/NotificationManager.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit();
}

try {
    // Initialize default preferences
    $preferences = [
        'channels' => [
            'email' => false,
            'browser' => false,
            'sms' => false
        ],
        'types' => [
            'inventory_low' => false,
            'restock' => false,
            'order_new' => false,
            'order_status' => false,
            'system_maintenance' => false,
            'system_updates' => false,
            'security_login' => false,
            'security_changes' => false
        ],
        'quiet_hours' => [
            'start' => '22:00',
            'end' => '06:00'
        ]
    ];

    // Get user's notification preferences
    $sql = "SELECT np.notification_type, np.is_enabled, np.config, nc.type as channel_type 
            FROM user_notification_preferences np 
            LEFT JOIN notification_channels nc ON nc.channel_id = np.channel_id 
            WHERE np.user_id = ?";
    
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $connect->error);
    }

    $stmt->bind_param("i", $_SESSION['userId']);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row['channel_type']) {
            // This is a channel preference
            $preferences['channels'][$row['channel_type']] = (bool)$row['is_enabled'];
        } else if ($row['notification_type'] === 'quiet_hours' && $row['config']) {
            // This is quiet hours config
            $config = json_decode($row['config'], true);
            if ($config) {
                $preferences['quiet_hours'] = [
                    'start' => $config['quiet_hours_start'] ?? '22:00',
                    'end' => $config['quiet_hours_end'] ?? '06:00'
                ];
            }
        } else {
            // This is a notification type preference
            $preferences['types'][$row['notification_type']] = (bool)$row['is_enabled'];
        }
    }

    echo json_encode([
        'success' => true,
        'preferences' => $preferences
    ]);

} catch (Exception $e) {
    error_log('Error getting user preferences: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load preferences: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} 