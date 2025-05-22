<?php
require_once 'core.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

if(!isset($_POST['orderId'])) {
    echo json_encode(array('success' => false, 'messages' => 'Order ID not provided'));
    exit();
}

$orderId = intval($_POST['orderId']);

try {
    // Start transaction
    $connect->begin_transaction();

    // First, check if the order exists and is in draft status
    $checkQuery = "SELECT status FROM production_orders WHERE id = ?";
    $stmt = $connect->prepare($checkQuery);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        throw new Exception('Production order not found');
    }
    
    $order = $result->fetch_assoc();
    if(strtolower($order['status']) !== 'draft') {
        throw new Exception('Only draft orders can be deleted');
    }
    
    // Delete associated materials first
    $deleteMaterialsQuery = "DELETE FROM production_order_materials WHERE production_order_id = ?";
    $stmt = $connect->prepare($deleteMaterialsQuery);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    
    // Then delete the order
    $deleteOrderQuery = "DELETE FROM production_orders WHERE id = ?";
    $stmt = $connect->prepare($deleteOrderQuery);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    
    if($stmt->affected_rows === 0) {
        throw new Exception('Error deleting production order');
    }
    
    // Commit transaction
    $connect->commit();
    
    echo json_encode(array(
        'success' => true,
        'messages' => 'Production order deleted successfully'
    ));

} catch(Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    
    error_log('Error in removeProductionOrder.php: ' . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'messages' => $e->getMessage()
    ));
}

$connect->close(); 