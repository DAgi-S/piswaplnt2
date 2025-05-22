<?php
/**
 * Production Order Details Controller
 * 
 * This file handles retrieving production order details
 * using the ProductionManager class methods
 */

// Database connection
require_once 'db_connect.php';

// Include required files
require_once 'production_middleware.php';
require_once 'classes/ProductionManager.php';

// Initialize production middleware
$productionMiddleware = new ProductionMiddleware($connect);

// Validate view permission
if (!$productionMiddleware->validateAccess('production.order.view')) {
    echo json_encode(array(
        'success' => false, 
        'messages' => 'Permission denied: Cannot view production order details'
    ));
    exit();
}

// Set default response
$response = array();

// Check if order_id is provided
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Basic validation
if ($order_id <= 0) {
    $response['success'] = false;
    $response['messages'] = "Invalid order ID";
    echo json_encode($response);
    exit();
}

try {
    // Initialize ProductionManager
    $productionManager = new ProductionManager($connect);
    
    // Get production order details
    $result = $productionManager->getProductionOrderDetails($order_id);
    
    // Handle the result
    if ($result['status']) {
        $response['success'] = true;
        
        // Include all data
        $response['order'] = $result['order'];
        $response['materials'] = $result['materials'];
        $response['progress'] = $result['progress'];
        
        // Calculate completion percentage
        if ($result['order']['target_quantity'] > 0) {
            $response['completion_percentage'] = round(
                ($result['order']['completed_quantity'] / $result['order']['target_quantity']) * 100, 2
            );
        } else {
            $response['completion_percentage'] = 0;
        }
    } else {
        $response['success'] = false;
        $response['messages'] = $result['message'];
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 