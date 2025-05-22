<?php
/**
 * Production Order Status Update Controller
 * 
 * This file handles updating the status of production orders
 * using the ProductionManager class methods
 */

// Database connection
require_once 'db_connect.php';

// Include the ProductionManager class
require_once 'classes/ProductionManager.php';

// Set default response
$response = array();

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Validate user session if needed
    // if(!isset($_SESSION['userId'])) {
    //     $response['success'] = false;
    //     $response['messages'] = "Unauthorized access";
    //     echo json_encode($response);
    //     exit();
    // }
    
    // Get form data with validations
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
        $response['messages'] = "Status is required";
        echo json_encode($response);
        exit();
    }
    
    // Validate status value
    $validStatuses = ['draft', 'planned', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
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
            $response['messages'] = $result['message'];
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