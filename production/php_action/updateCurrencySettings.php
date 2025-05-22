<?php
require_once 'core.php';

// Check if user has permission to manage currency settings
if (!hasPermission('settings.currency.manage')) {
    $response = array('success' => false, 'messages' => 'Access denied. Permission to manage currency settings required.');
    echo json_encode($response);
    exit();
}

$response = array('success' => false, 'messages' => '');

// Validate and sanitize input
$defaultCurrency = isset($_POST['default_currency']) ? $connect->real_escape_string($_POST['default_currency']) : '';
$currencyPosition = isset($_POST['currency_position']) ? $connect->real_escape_string($_POST['currency_position']) : 'left';
$thousandSeparator = isset($_POST['thousand_separator']) ? $connect->real_escape_string($_POST['thousand_separator']) : ',';
$decimalSeparator = isset($_POST['decimal_separator']) ? $connect->real_escape_string($_POST['decimal_separator']) : '.';
$decimals = isset($_POST['decimals']) ? intval($_POST['decimals']) : 2;

// Validate input
if(empty($defaultCurrency) || empty($currencyPosition) || empty($thousandSeparator) || empty($decimalSeparator)) {
    $response['messages'] = 'All fields are required.';
    echo json_encode($response);
    exit();
}

try {
    // Start transaction
    $connect->begin_transaction();

    // Update currency settings
    $settings = array(
        'default_currency' => $defaultCurrency,
        'currency_position' => $currencyPosition,
        'thousand_separator' => $thousandSeparator,
        'decimal_separator' => $decimalSeparator,
        'decimals' => $decimals
    );

    foreach($settings as $key => $value) {
        $sql = "INSERT INTO settings (setting_type, setting_key, setting_value) 
                VALUES ('currency', ?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('ss', $key, $value);
        
        if(!$stmt->execute()) {
            throw new Exception($connect->error);
        }
        $stmt->close();
    }

    // Commit transaction
    $connect->commit();
    
    $response['success'] = true;
    $response['messages'] = 'Currency settings updated successfully.';

} catch (Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    $response['messages'] = 'Error occurred while updating currency settings: ' . $e->getMessage();
}

// Close database connection
$connect->close();

header('Content-Type: application/json');
echo json_encode($response); 