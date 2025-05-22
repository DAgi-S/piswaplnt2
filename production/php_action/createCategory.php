<?php
require_once 'core.php';

// Check if user has admin role
if(!isset($_SESSION['roleId']) || $_SESSION['roleId'] !== 2) {
    $response = array('success' => false, 'message' => 'Access denied. Admin privileges required.');
    echo json_encode($response);
    exit();
}

$response = array('success' => false, 'message' => '');

// Validate and sanitize input
$name = isset($_POST['name']) ? $connect->real_escape_string($_POST['name']) : '';
$type = isset($_POST['type']) ? $connect->real_escape_string($_POST['type']) : 'product';
$description = isset($_POST['description']) ? $connect->real_escape_string($_POST['description']) : '';
$status = isset($_POST['status']) ? intval($_POST['status']) : 1;

// Validate input
if(empty($name)) {
    $response['message'] = 'Category name is required.';
    echo json_encode($response);
    exit();
}

if(empty($type)) {
    $response['message'] = 'Category type is required.';
    echo json_encode($response);
    exit();
}

try {
    // Insert category
    $sql = "INSERT INTO categories (name, type, description, status) VALUES (?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('sssi', $name, $type, $description, $status);
    
    if($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Category added successfully.';
    } else {
        throw new Exception($connect->error);
    }
    
    $stmt->close();

} catch (Exception $e) {
    $response['message'] = 'Error occurred while adding category: ' . $e->getMessage();
}

// Close database connection
$connect->close();

header('Content-Type: application/json');
echo json_encode($response);