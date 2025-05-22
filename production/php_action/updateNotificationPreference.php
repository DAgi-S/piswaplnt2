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
if (!isset($_POST['notification_type']) || !isset($_POST['channel']) || !isset($_POST['enabled'])) {
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
    $channel = $_POST['channel'];
    $enabled = $_POST['enabled'] === 'true' || $_POST['enabled'] === '1';

    // Update preference
    $success = $notificationManager->updatePreference($userId, $type, $channel, $enabled);

    if ($success) {
        $response = array(
            'success' => true,
            'messages' => 'Preference updated successfully'
        );
    } else {
        $response = array(
            'success' => false,
            'messages' => 'Failed to update preference'
        );
    }

} catch (Exception $e) {
    $response = array(
        'success' => false,
        'messages' => 'Error: ' . $e->getMessage()
    );
}

echo json_encode($response); 