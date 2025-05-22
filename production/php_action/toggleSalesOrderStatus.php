<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set header type to JSON
header('Content-Type: application/json');

$response = array();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['success'] = false;
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

// Check if required parameters are present
if (!isset($_POST['order_id']) || !isset($_POST['new_status'])) {
    $response['success'] = false;
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit();
}

$orderId = intval($_POST['order_id']);
$newStatus = $_POST['new_status'];

// Validate status value
if (!in_array($newStatus, ['pending', 'completed'])) {
    $response['success'] = false;
    $response['message'] = 'Invalid status value';
    echo json_encode($response);
    exit();
}

try {
    // Start transaction
    $connect->begin_transaction();

    // Update order status
    $updateQuery = "UPDATE sales_orders 
                   SET order_status = ?, 
                       updated_at = CURRENT_TIMESTAMP 
                   WHERE id = ?";
    
    $stmt = $connect->prepare($updateQuery);
    $stmt->bind_param('si', $newStatus, $orderId);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Order not found or no changes made');
    }

    // If status is being set to completed, verify all items are fulfilled
    if ($newStatus === 'completed') {
        // Add any additional checks here if needed
        // For example, you might want to check if all items have been delivered
        // or if payment is complete
    }

    // Commit transaction
    $connect->commit();

    $response['success'] = true;
    $response['message'] = 'Order status successfully updated to ' . ucfirst($newStatus);

} catch (Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Close statement if it exists
if (isset($stmt)) {
    $stmt->close();
}

echo json_encode($response); 