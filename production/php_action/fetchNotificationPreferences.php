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

try {
    $notificationManager = new NotificationManager();
    $userId = $_SESSION['userId'];

    // Get user preferences
    $preferences = $notificationManager->getUserPreferences($userId);
    
    // Get frequency settings
    $frequencies = $notificationManager->getNotificationFrequencies($userId);
    
    // Get channel settings
    $channels = $notificationManager->getChannelSettings($userId);

    $response = array(
        'success' => true,
        'preferences' => $preferences,
        'frequencies' => $frequencies,
        'channels' => $channels
    );

} catch (Exception $e) {
    $response = array(
        'success' => false,
        'messages' => 'Error: ' . $e->getMessage()
    );
}

echo json_encode($response); 