<?php
require_once 'core.php';
require_once 'classes/NotificationManager.php';

if (!isset($_SESSION['userId'])) {
    $response = array(
        'success' => false,
        'messages' => 'User not logged in'
    );
    echo json_encode($response);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['notification_type']) || !isset($_POST['frequency'])) {
    $response = array(
        'success' => false,
        'messages' => 'Missing required parameters'
    );
    echo json_encode($response);
    exit();
}

try {
    $notificationManager = new NotificationManager();
    $userId = $_SESSION['userId'];
    $type = $_POST['notification_type'];
    $frequency = $_POST['frequency'];

    // Validate frequency
    if (!in_array($frequency, ['immediate', 'daily', 'weekly'])) {
        throw new Exception('Invalid frequency value');
    }

    // Update frequency
    $success = $notificationManager->updateNotificationFrequency($userId, $type, $frequency);

    if ($success) {
        $response = array(
            'success' => true,
            'messages' => 'Notification frequency updated successfully'
        );
    } else {
        $response = array(
            'success' => false,
            'messages' => 'Failed to update notification frequency'
        );
    }

} catch (Exception $e) {
    $response = array(
        'success' => false,
        'messages' => 'Error: ' . $e->getMessage()
    );
}

echo json_encode($response); 