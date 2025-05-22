<?php
require_once 'core.php';

// Check if user has permission to manage currency settings
if (!hasPermission('settings.currency.manage')) {
    $response = array('success' => false, 'messages' => 'Access denied. Permission to manage currency settings required.');
    echo json_encode($response);
    exit();
}

$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_type = 'currency'";
$result = $connect->query($sql);

$settings = array();
if($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Set default values if not found
$defaultSettings = array(
    'default_currency' => 'ETB',
    'currency_position' => 'left',
    'thousand_separator' => ',',
    'decimal_separator' => '.',
    'decimals' => '2'
);

foreach($defaultSettings as $key => $value) {
    if(!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

$response = array(
    'success' => true,
    'data' => $settings
);

// Close database connection
$connect->close();

header('Content-Type: application/json');
echo json_encode($response); 