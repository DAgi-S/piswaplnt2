<?php
/**
 * Production Progress Recording Controller
 * 
 * This file handles recording progress for production orders
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
    $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Basic validation
    if ($order_id <= 0) {
        $response['success'] = false;
        $response['messages'] = "Invalid order ID";
        echo json_encode($response);
        exit();
    }
    
    if ($quantity <= 0) {
        $response['success'] = false;
        $response['messages'] = "Please enter a valid quantity";
        echo json_encode($response);
        exit();
    }
    
    try {
        // Initialize ProductionManager
        $productionManager = new ProductionManager($connect);
        
        // Record production progress
        $result = $productionManager->recordProductionProgress($order_id, $quantity, $notes);
        
        // Handle the result
        if ($result['status']) {
            $response['success'] = true;
            $response['messages'] = $result['message'];
            
            // Include additional data
            $response['data'] = array(
                'completed' => $result['completed'],
                'target' => $result['target'],
                'new_status' => $result['new_status']
            );
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