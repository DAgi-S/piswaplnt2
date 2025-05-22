<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

require_once 'core.php';
require_once 'db_connect.php';

// Set header type to JSON
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$productId = isset($_POST['id']) ? intval($_POST['id']) : 0;
$newStatus = isset($_POST['status']) ? $_POST['status'] : '';

// Validate input
if (!$productId || !in_array($newStatus, ['active', 'inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
    exit();
}

try {
    // Prepare and execute the update query
    $stmt = $connect->prepare("UPDATE production_products SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $newStatus, $productId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        throw new Exception("Error updating status: " . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error in changeProductStatus.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error updating product status']);
}

$connect->close(); 