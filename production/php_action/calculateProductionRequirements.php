<?php
/**
 * Calculate Production Requirements Controller
 * 
 * This file handles calculating the materials required for a production order
 * using the ProductionManager class
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
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $target_qty = isset($_POST['target_qty']) ? floatval($_POST['target_qty']) : 0;
    
    // Basic validation
    if ($order_id <= 0 && $product_id <= 0) {
        $response['success'] = false;
        $response['messages'] = "Either order ID or product ID must be provided";
        echo json_encode($response);
        exit();
    }
    
    if ($product_id > 0 && $target_qty <= 0) {
        $response['success'] = false;
        $response['messages'] = "Target quantity must be provided when using product ID";
        echo json_encode($response);
        exit();
    }
    
    try {
        // Initialize ProductionManager
        $productionManager = new ProductionManager($connect);
        
        // Calculate the requirements
        if ($order_id > 0) {
            // Calculate for existing order
            $result = $productionManager->calculateProductionRequirements(null, null, $order_id);
        } else {
            // Calculate for product and quantity (preview)
            $result = $productionManager->calculateProductionRequirements($product_id, $target_qty);
        }
        
        // Handle the result
        if ($result['status']) {
            $response['success'] = true;
            $response['messages'] = 'Requirements calculated successfully';
            $response['data'] = $result['data'];
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