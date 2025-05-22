<?php
require_once 'core.php';
require_once 'classes/LowStockManager.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
    exit();
}

// Validate input parameters
$thresholdBuffer = isset($_POST['threshold_buffer']) ? floatval($_POST['threshold_buffer']) : 20;
$autoCalculate = isset($_POST['auto_calculate']) ? filter_var($_POST['auto_calculate'], FILTER_VALIDATE_BOOLEAN) : false;
$emailNotifications = isset($_POST['email_notifications']) ? filter_var($_POST['email_notifications'], FILTER_VALIDATE_BOOLEAN) : false;
$alertFrequency = isset($_POST['alert_frequency']) ? $_POST['alert_frequency'] : 'daily';

// Validate threshold buffer
if ($thresholdBuffer < 0 || $thresholdBuffer > 100) {
    echo json_encode(array('success' => false, 'message' => 'Invalid threshold buffer value'));
    exit();
}

// Validate alert frequency
$validFrequencies = array('daily', 'weekly', 'immediate');
if (!in_array($alertFrequency, $validFrequencies)) {
    echo json_encode(array('success' => false, 'message' => 'Invalid alert frequency'));
    exit();
}

try {
    // Update settings in the database
    $query = "INSERT INTO system_settings (setting_key, setting_value) VALUES 
              ('threshold_buffer', ?),
              ('auto_calculate_thresholds', ?),
              ('email_notifications', ?),
              ('alert_frequency', ?)
              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    
    $stmt = $connect->prepare($query);
    $autoCalculateStr = $autoCalculate ? '1' : '0';
    $emailNotificationsStr = $emailNotifications ? '1' : '0';
    
    $stmt->bind_param("ssss", 
        $thresholdBuffer,
        $autoCalculateStr,
        $emailNotificationsStr,
        $alertFrequency
    );
    
    if ($stmt->execute()) {
        // If auto-calculate is enabled, update all thresholds
        if ($autoCalculate) {
            $lowStockManager = new LowStockManager($connect);
            $lowStockManager->updateThresholds();
        }
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Threshold settings updated successfully'
        ));
    } else {
        throw new Exception("Failed to update settings");
    }
} catch (Exception $e) {
    error_log("Error updating threshold settings: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Failed to update threshold settings'
    ));
} 