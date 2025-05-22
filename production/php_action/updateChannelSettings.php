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
if (!isset($_POST['channel']) || !isset($_POST['settings'])) {
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
    $channel = $_POST['channel'];
    $settings = is_array($_POST['settings']) ? $_POST['settings'] : json_decode($_POST['settings'], true);

    // Validate settings based on channel type
    switch ($channel) {
        case 'email':
            if (!validateEmailSettings($settings)) {
                throw new Exception('Invalid email settings');
            }
            break;
        case 'sms':
            if (!validateSMSSettings($settings)) {
                throw new Exception('Invalid SMS settings');
            }
            break;
        case 'telegram':
            if (!validateTelegramSettings($settings)) {
                throw new Exception('Invalid Telegram settings');
            }
            break;
    }

    // Update channel settings
    $success = $notificationManager->updateChannelSettings($userId, $channel, $settings);

    if ($success) {
        // Test the channel connection if possible
        $testResult = $notificationManager->testChannelConnection($channel);
        
        $response = array(
            'success' => true,
            'messages' => 'Channel settings updated successfully',
            'test_result' => $testResult
        );
    } else {
        $response = array(
            'success' => false,
            'messages' => 'Failed to update channel settings'
        );
    }

} catch (Exception $e) {
    $response = array(
        'success' => false,
        'messages' => 'Error: ' . $e->getMessage()
    );
}

echo json_encode($response);

// Validation functions
function validateEmailSettings($settings) {
    return isset($settings['smtp_host']) 
        && isset($settings['smtp_port']) 
        && isset($settings['smtp_username']) 
        && isset($settings['smtp_password'])
        && filter_var($settings['smtp_username'], FILTER_VALIDATE_EMAIL)
        && is_numeric($settings['smtp_port']);
}

function validateSMSSettings($settings) {
    return isset($settings['account_sid']) 
        && isset($settings['auth_token']) 
        && isset($settings['from_number'])
        && !empty($settings['account_sid'])
        && !empty($settings['auth_token'])
        && preg_match('/^\+\d{10,15}$/', $settings['from_number']);
}

function validateTelegramSettings($settings) {
    return isset($settings['bot_token']) 
        && isset($settings['chat_id'])
        && !empty($settings['bot_token'])
        && !empty($settings['chat_id']);
} 