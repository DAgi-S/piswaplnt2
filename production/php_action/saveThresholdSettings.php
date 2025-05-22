<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = array('success' => false, 'messages' => array());

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Get settings from POST data
        $thresholdBuffer = isset($_POST['threshold_buffer']) ? (int)$_POST['threshold_buffer'] : 20;
        $autoCalculate = isset($_POST['auto_calculate_thresholds']) ? 1 : 0;
        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        $alertFrequency = isset($_POST['alert_frequency']) ? $_POST['alert_frequency'] : 'daily';

        // Update settings in inventory_settings table
        $settings = array(
            'low_stock_threshold' => $thresholdBuffer,
            'critical_stock_threshold' => floor($thresholdBuffer/2),
            'notification_enabled' => $emailNotifications,
            'notification_frequency' => $alertFrequency
        );

        foreach ($settings as $key => $value) {
            // Check if setting exists
            $checkSql = "SELECT id FROM inventory_settings WHERE setting_key = ?";
            $checkStmt = $connect->prepare($checkSql);
            $checkStmt->bind_param("s", $key);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows > 0) {
                // Update existing setting
                $sql = "UPDATE inventory_settings 
                       SET setting_value = ?, updated_at = NOW() 
                       WHERE setting_key = ?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param("ss", $value, $key);
            } else {
                // Insert new setting
                $sql = "INSERT INTO inventory_settings 
                       (setting_key, setting_value, description, created_at, updated_at) 
                       VALUES (?, ?, ?, NOW(), NOW())";
                $stmt = $connect->prepare($sql);
                $description = "Inventory control setting for " . str_replace('_', ' ', $key);
                $stmt->bind_param("sss", $key, $value, $description);
            }

            if (!$stmt->execute()) {
                throw new Exception("Error saving setting: " . $key);
            }
        }

        $response['success'] = true;
        $response['messages'][] = "Settings saved successfully";

    } catch (Exception $e) {
        $response['messages'][] = $e->getMessage();
    }
} else {
    $response['messages'][] = "Invalid request method";
}

echo json_encode($response);
?> 