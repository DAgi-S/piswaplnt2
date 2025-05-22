<?php
require_once 'core.php';
require_once 'classes/NotificationManager.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode([
        'success' => false,
        'messages' => 'User not logged in'
    ]);
    exit();
}

try {
    $notificationManager = new NotificationManager($connect);
    
    // Create a test notification
    $notification = [
        'title' => 'Test Browser Notification',
        'message' => 'This is a test browser notification. If you see this, browser notifications are working!',
        'priority' => 'normal',
        'type' => 'test'
    ];
    
    // Send notification to current user
    $result = $notificationManager->createNotification(
        $notification['title'],
        $notification['message'],
        $_SESSION['userId'],
        $notification['priority'],
        $notification['type']
    );
    
    if ($result) {
        // Get the created notification for browser display
        $notification['notification_id'] = $result;
        
        echo json_encode([
            'success' => true,
            'messages' => 'Test notification sent successfully',
            'notification' => $notification
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'messages' => 'Failed to create test notification'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'messages' => 'Error: ' . $e->getMessage()
    ]);
} 