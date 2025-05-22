<?php
/**
 * Get Production Order Controller
 * 
 * This file handles retrieving production order details including materials and progress
 * using the ProductionManager class
 */

// Database connection
require_once 'db_connect.php';

// Include the ProductionManager class
require_once 'classes/ProductionManager.php';

// Set default response
$response = array();

// Check if the order ID parameter exists
if (isset($_GET['id']) && !empty($_GET['id'])) {
    
    // Get order ID
    $order_id = intval($_GET['id']);
    
    try {
        // Initialize ProductionManager
        $productionManager = new ProductionManager($connect);
        
        // Get the production order details
        $orderDetails = $productionManager->getProductionOrder($order_id);
        
        if ($orderDetails) {
            // Get materials for this order
            $materials = $productionManager->getProductionOrderMaterials($order_id);
            
            // Get progress records
            $progress = $productionManager->getProductionProgress($order_id);
            
            // Get quality checks if any
            $qualityChecks = $productionManager->getQualityChecks($order_id);
            
            // Prepare response
            $response = array(
                'success' => true,
                'order' => $orderDetails,
                'materials' => $materials,
                'progress' => $progress,
                'quality_checks' => $qualityChecks
            );
            
            // Include product information
            if (isset($orderDetails['product_id'])) {
                $product = $productionManager->getProduct($orderDetails['product_id']);
                if ($product) {
                    $response['product'] = $product;
                }
            }
            
        } else {
            $response['success'] = false;
            $response['messages'] = 'Production order not found';
        }
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = 'Error: ' . $e->getMessage();
    }
    
} else {
    $response['success'] = false;
    $response['messages'] = 'Missing order ID parameter';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 