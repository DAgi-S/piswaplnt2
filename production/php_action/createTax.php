<?php
require_once 'core.php';

// Check if user has permission to manage tax settings
if (!hasPermission('settings.tax.manage')) {
    $response = array('success' => false, 'messages' => 'Access denied. Permission to manage tax settings required.');
    echo json_encode($response);
    exit();
}

$response = array('success' => false, 'messages' => '');

// Validate and sanitize input
$name = isset($_POST['name']) ? $connect->real_escape_string($_POST['name']) : '';
$rate = isset($_POST['rate']) ? floatval($_POST['rate']) : 0;
$type = isset($_POST['type']) ? $connect->real_escape_string($_POST['type']) : 'percentage';
$status = isset($_POST['status']) ? intval($_POST['status']) : 1;

// Validate input
if(empty($name)) {
    $response['messages'] = 'Tax name is required.';
    echo json_encode($response);
    exit();
}

if($rate < 0 || $rate > 100) {
    $response['messages'] = 'Tax rate must be between 0 and 100.';
    echo json_encode($response);
    exit();
}

try {
    // Insert tax rate
    $sql = "INSERT INTO tax_rates (name, rate, type, status) VALUES (?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('sdsi', $name, $rate, $type, $status);
    
    if($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = 'Tax rate added successfully.';
    } else {
        throw new Exception($connect->error);
    }
    
    $stmt->close();

} catch (Exception $e) {
    $response['messages'] = 'Error occurred while adding tax rate: ' . $e->getMessage();
}

// Close database connection
$connect->close();

header('Content-Type: application/json');
echo json_encode($response); 