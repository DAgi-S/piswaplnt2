<?php
/**
 * Update Production Order Status Controller
 * 
 * This file handles updating the status of production orders using the ProductionManager class
 * Replaces direct database operations with class methods
 */

// Database connection
require_once 'db_connect.php';

// Include the ProductionManager class
require_once 'classes/ProductionManager.php';

// Set default response
$response = array();

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get and validate form data
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Basic validation
    if ($order_id <= 0) {
        $response['success'] = false;
        $response['messages'] = "Invalid order ID";
        echo json_encode($response);
        exit();
    }
    
    if (empty($status)) {
        $response['success'] = false;
        $response['messages'] = "Please select a valid status";
        echo json_encode($response);
        exit();
    }
    
    // Validate status - allowed values
    $allowedStatuses = ['draft', 'pending', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($status, $allowedStatuses)) {
        $response['success'] = false;
        $response['messages'] = "Invalid status value";
        echo json_encode($response);
        exit();
    }
    
    try {
        // Initialize ProductionManager
        $productionManager = new ProductionManager($connect);
        
        // Update the order status
        $result = $productionManager->updateOrderStatus($order_id, $status, $notes);
        
        // Handle the result
        if ($result['status']) {
            $response['success'] = true;
            $response['messages'] = 'Order status updated successfully';
            $response['order_id'] = $order_id;
            $response['new_status'] = $status;
            
            // Include any additional data from the result
            if (isset($result['data'])) {
                $response['data'] = $result['data'];
            }
        } else {
            $response['success'] = false;
            $response['messages'] = $result['message'];
        }
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = 'Error: ' . $e->getMessage();
    }
    
} else {
    $response['success'] = false;
    $response['messages'] = 'Invalid request method';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 