<?php
/**
 * Update Production Order Status Controller
 * 
 * This file handles updating the status of existing production orders
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
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $completed_qty = isset($_POST['completed_qty']) ? floatval($_POST['completed_qty']) : null;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : null;
    $modified_by = isset($_POST['modified_by']) ? intval($_POST['modified_by']) : 0;
    
    // Basic validation
    if ($order_id <= 0) {
        $response['success'] = false;
        $response['messages'] = "Production order ID is required";
        echo json_encode($response);
        exit();
    }
    
    // Validate status
    $allowed_statuses = array('draft', 'pending', 'in_progress', 'completed', 'cancelled');
    if (!in_array($status, $allowed_statuses)) {
        $response['success'] = false;
        $response['messages'] = "Invalid status value";
        echo json_encode($response);
        exit();
    }
    
    // If status is completed, make sure we have a completed quantity
    if ($status == 'completed' && ($completed_qty === null || $completed_qty <= 0)) {
        $response['success'] = false;
        $response['messages'] = "Completed quantity is required when marking an order as completed";
        echo json_encode($response);
        exit();
    }
    
    try {
        // Initialize ProductionManager
        $productionManager = new ProductionManager($connect);
        
        // Prepare update data
        $updateData = array(
            'status' => $status,
            'modified_by' => $modified_by
        );
        
        // Add completed quantity if provided
        if ($completed_qty !== null) {
            $updateData['completed_qty'] = $completed_qty;
        }
        
        // Add notes if provided
        if ($notes !== null) {
            $updateData['notes'] = $notes;
        }
        
        // Add completion date if status is completed
        if ($status == 'completed') {
            $updateData['completion_date'] = date('Y-m-d H:i:s');
        }
        
        // Update the production order status
        $result = $productionManager->updateProductionOrder($order_id, $updateData);
        
        // Handle the result
        if ($result['status']) {
            $response['success'] = true;
            $response['messages'] = 'Production order status updated successfully';
            
            // If order is completed, update inventory
            if ($status == 'completed' && $completed_qty > 0) {
                $inventoryResult = $productionManager->updateInventoryFromCompletedOrder($order_id);
                $response['inventory_updated'] = $inventoryResult['status'];
                if (!$inventoryResult['status']) {
                    $response['inventory_message'] = $inventoryResult['message'];
                }
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