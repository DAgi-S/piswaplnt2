<?php
require_once 'core.php';

$response = array('success' => false, 'messages' => '');

// Permission check
if (!hasPermission('settings.brands.manage')) {
    $response['success'] = false;
    $response['messages'] = 'Access denied. Permission to manage brands required.';
    echo json_encode($response);
    exit();
}

// Validate brand ID
if (isset($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
    $brandId = $_POST['id'];
    // Perform soft delete by updating the deleted status
    $sql = "UPDATE brands SET deleted = 1 WHERE brand_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $brandId);
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = 'Brand deleted successfully';
    } else {
        $response['messages'] = 'Error deleting brand: ' . $connect->error;
    }
    $stmt->close();
} else {
    $response['messages'] = 'Invalid brand ID';
}

$connect->close();
header('Content-Type: application/json');
echo json_encode($response); 