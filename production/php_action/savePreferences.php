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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid input data'
    ]);
    exit();
}

try {
    // Start transaction
    $connect->begin_transaction();
    
    try {
        // Save channel preferences
        if (isset($input['channels'])) {
            // Get channel IDs
            $sql = "SELECT channel_id, type FROM notification_channels";
            $result = $connect->query($sql);
            $channelIds = [];
            while ($row = $result->fetch_assoc()) {
                $channelIds[$row['type']] = $row['channel_id'];
            }

            // Save channel preferences
            $sql = "INSERT INTO user_notification_preferences 
                    (user_id, channel_id, notification_type, is_enabled) 
                    VALUES (?, ?, '', ?)
                    ON DUPLICATE KEY UPDATE is_enabled = ?";
            
            $stmt = $connect->prepare($sql);
            foreach ($input['channels'] as $channel => $enabled) {
                if (isset($channelIds[$channel])) {
                    $isEnabled = $enabled ? 1 : 0;
                    $stmt->bind_param("iiis", $_SESSION['userId'], $channelIds[$channel], $isEnabled, $isEnabled);
                    $stmt->execute();
                }
            }
        }

        // Save notification type preferences
        if (isset($input['types'])) {
            $sql = "INSERT INTO user_notification_preferences 
                    (user_id, notification_type, is_enabled) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE is_enabled = ?";
            
            $stmt = $connect->prepare($sql);
            foreach ($input['types'] as $type => $enabled) {
                $isEnabled = $enabled ? 1 : 0;
                $stmt->bind_param("isii", $_SESSION['userId'], $type, $isEnabled, $isEnabled);
                $stmt->execute();
            }
        }
        
        // Save quiet hours if provided
        if (isset($input['quiet_hours'])) {
            $sql = "INSERT INTO user_notification_preferences 
                    (user_id, notification_type, config) 
                    VALUES (?, 'quiet_hours', ?)
                    ON DUPLICATE KEY UPDATE config = ?";
            
            $config = json_encode([
                'quiet_hours_start' => $input['quiet_hours']['start'],
                'quiet_hours_end' => $input['quiet_hours']['end']
            ]);
            
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("iss", $_SESSION['userId'], $config, $config);
            $stmt->execute();
        }
        
        $connect->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Preferences saved successfully'
        ]);
        
    } catch (Exception $e) {
        $connect->rollback();
        throw $e;
    }
} catch (Exception $e) {
    error_log('Error saving preferences: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save preferences: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'input' => $input
        ]
    ]);
} 