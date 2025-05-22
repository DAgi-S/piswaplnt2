<?php
require_once 'core.php';
require_once 'classes/NotificationManager.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    $response = array(
        'success' => false,
        'messages' => 'User not logged in'
    );
    echo json_encode($response);
    exit();
}

// Check if notification ID is provided
if (!isset($_POST['notification_id'])) {
    $response = array(
        'success' => false,
        'messages' => 'Notification ID is required'
    );
    echo json_encode($response);
    exit();
}

try {
    $notificationManager = new NotificationManager();
    $success = $notificationManager->markAsRead($_POST['notification_id'], $_SESSION['userId']);

    if ($success) {
        $response = array(
            'success' => true,
            'messages' => 'Notification marked as read'
        );
    } else {
        $response = array(
            'success' => false,
            'messages' => 'Failed to mark notification as read'
        );
    }

} catch (Exception $e) {
    $response = array(
        'success' => false,
        'messages' => 'Error: ' . $e->getMessage()
    );
}

echo json_encode($response); 