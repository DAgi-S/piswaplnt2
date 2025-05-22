<?php
require_once 'core.php';
require_once 'classes/NotificationManager.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit();
}

try {
    $notificationManager = new NotificationManager();
    
    // Create test notification data
    $notificationData = [
        'title' => 'Test Email Notification',
        'message' => 'This is a test notification sent at ' . date('Y-m-d H:i:s'),
        'priority' => 'medium',
        'icon' => 'fa-bell',
        'link' => null
    ];

    // Create the notification
    $notificationId = $notificationManager->createNotification('test', $notificationData);
    
    if ($notificationId) {
        // Send to current user via email
        $success = $notificationManager->sendNotification(
            'test',
            [$_SESSION['userId']],
            $notificationData,
            ['email']
        );

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Test notification sent successfully'
            ]);
        } else {
            throw new Exception('Failed to send notification');
        }
    } else {
        throw new Exception('Failed to create notification');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} 