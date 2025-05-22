<?php
require_once 'core.php';

// Check if user has permission to manage tax settings
if (!hasPermission('settings.tax.manage')) {
    $response['success'] = false;
    $response['messages'] = 'Access denied. Permission to manage tax settings required.';
    echo json_encode($response);
    exit();
}

// Initialize response array
$response = array();
$response['success'] = false;
$response['messages'] = '';

// Validate tax rate ID
if (isset($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
    $taxId = $_POST['id'];
    
    // Perform soft delete by updating the deleted status
    $sql = "UPDATE tax_rates SET deleted = 1 WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $taxId);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = 'Tax rate deleted successfully';
    } else {
        $response['messages'] = 'Error deleting tax rate: ' . $connect->error;
    }
    
    $stmt->close();
} else {
    $response['messages'] = 'Invalid tax rate ID';
}

// Close database connection
$connect->close();

// Set content type header and output response
header('Content-Type: application/json');
echo json_encode($response); 