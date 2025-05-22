<?php
/**
 * Production Order Creation Controller
 * 
 * This file handles the creation of production orders using the ProductionManager class
 * Replaces direct database operations with the class methods
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
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $target_quantity = isset($_POST['target_quantity']) ? floatval($_POST['target_quantity']) : 0;
    $order_number = isset($_POST['order_number']) && !empty($_POST['order_number']) ? 
        $_POST['order_number'] : 'PO-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $start_date = isset($_POST['start_date']) && !empty($_POST['start_date']) ? 
        $_POST['start_date'] : date('Y-m-d');
    
    $expected_completion_date = isset($_POST['expected_completion_date']) && !empty($_POST['expected_completion_date']) ? 
        $_POST['expected_completion_date'] : date('Y-m-d', strtotime('+7 days'));
    
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $created_by = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
    
    // Basic validation
    if ($product_id <= 0) {
        $response['success'] = false;
        $response['messages'] = "Please select a valid product";
        echo json_encode($response);
        exit();
    }
    
    if ($target_quantity <= 0) {
        $response['success'] = false;
        $response['messages'] = "Please enter a valid quantity";
        echo json_encode($response);
        exit();
    }
    
    try {
        // Create order data array
        $orderData = array(
            'order_number' => $order_number,
            'product_id' => $product_id,
            'target_quantity' => $target_quantity,
            'start_date' => $start_date,
            'expected_completion_date' => $expected_completion_date,
            'notes' => $notes,
            'created_by' => $created_by,
            'status' => 'draft'
        );
        
        // Initialize ProductionManager
        $productionManager = new ProductionManager($connect);
        
        // Create the production order
        $result = $productionManager->createProductionOrder($orderData);
        
        // Handle the result
        if ($result['status']) {
            $response['success'] = true;
            $response['messages'] = 'Production order created successfully';
            $response['order_id'] = $result['order_id'];
            $response['order_number'] = $result['order_number'];
            
            // Include materials if available
            if (isset($result['materials'])) {
                $response['materials'] = $result['materials'];
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