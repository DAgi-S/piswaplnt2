<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = array('success' => false, 'data' => array());

try {
    // Get settings from inventory_settings table
    $sql = "SELECT setting_key, setting_value FROM inventory_settings";
    $result = $connect->query($sql);

    if ($result) {
        // Default settings
        $settings = array(
            'threshold_buffer' => 20,
            'auto_calculate_thresholds' => 0,
            'email_notifications' => 0,
            'alert_frequency' => 'daily'
        );

        // Map database settings to response format
        while ($row = $result->fetch_assoc()) {
            switch ($row['setting_key']) {
                case 'low_stock_threshold':
                    $settings['threshold_buffer'] = (int)$row['setting_value'];
                    break;
                case 'notification_enabled':
                    $settings['email_notifications'] = (int)$row['setting_value'];
                    break;
                case 'notification_frequency':
                    $settings['alert_frequency'] = $row['setting_value'];
                    break;
            }
        }

        $response['data'] = $settings;
        $response['success'] = true;
    } else {
        throw new Exception("Error retrieving settings");
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?> 