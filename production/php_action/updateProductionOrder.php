<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'production_middleware.php';

header('Content-Type: application/json');

// Initialize production middleware
$productionMiddleware = new ProductionMiddleware($connect);

// Validate edit permission
if (!$productionMiddleware->validateAccess('production.order.edit')) {
    echo json_encode(array('success' => false, 'messages' => 'Permission denied: Cannot edit production orders'));
    exit();
}

if(!isset($_POST['orderId'])) {
    echo json_encode(array('success' => false, 'messages' => 'Order ID not provided'));
    exit();
}

$orderId = intval($_POST['orderId']);
$status = $connect->real_escape_string($_POST['status']);
$completed_quantity = floatval($_POST['completed_quantity']);
$notes = $connect->real_escape_string($_POST['notes']);

try {
    // Start transaction
    $connect->begin_transaction();

    // First, get the current order details to validate the update
    $checkQuery = "SELECT target_quantity, status FROM production_orders WHERE id = ?";
    $stmt = $connect->prepare($checkQuery);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        throw new Exception('Production order not found');
    }
    
    $currentOrder = $result->fetch_assoc();
    
    // Validate completed quantity
    if($completed_quantity > $currentOrder['target_quantity']) {
        throw new Exception('Completed quantity cannot exceed target quantity');
    }
    
    // Validate status transition
    $validTransitions = array(
        'draft' => array('confirmed', 'cancelled'),
        'confirmed' => array('in_progress', 'cancelled'),
        'in_progress' => array('completed', 'cancelled'),
        'completed' => array(),
        'cancelled' => array()
    );
    
    $currentStatus = strtolower($currentOrder['status']);
    $newStatus = strtolower($status);
    
    if($currentStatus !== $newStatus && !in_array($newStatus, $validTransitions[$currentStatus])) {
        throw new Exception('Invalid status transition from ' . ucwords($currentStatus) . ' to ' . ucwords($newStatus));
    }
    
    // Update the production order
    $updateQuery = "UPDATE production_orders SET 
        status = ?,
        completed_quantity = ?,
        notes = ?,
        updated_at = NOW()
    WHERE id = ?";
    
    $stmt = $connect->prepare($updateQuery);
    $stmt->bind_param("sdsi", $status, $completed_quantity, $notes, $orderId);
    $stmt->execute();
    
    if($stmt->affected_rows === 0) {
        throw new Exception('No changes were made to the production order');
    }
    
    // If status is completed, ensure all required materials are consumed
    if($newStatus === 'completed') {
        $materialsQuery = "SELECT 
            pom.required_quantity,
            pom.consumed_quantity
        FROM production_order_materials pom
        WHERE pom.order_id = ?";
        
        $stmt = $connect->prepare($materialsQuery);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $materialsResult = $stmt->get_result();
        
        while($material = $materialsResult->fetch_assoc()) {
            if($material['consumed_quantity'] < $material['required_quantity']) {
                throw new Exception('Cannot mark as completed: Some materials have not been fully consumed');
            }
        }
    }
    
    // Commit transaction
    $connect->commit();
    
    echo json_encode(array(
        'success' => true,
        'messages' => 'Production order updated successfully'
    ));

} catch(Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    
    error_log('Error in updateProductionOrder.php: ' . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'messages' => $e->getMessage()
    ));
}

$connect->close(); 