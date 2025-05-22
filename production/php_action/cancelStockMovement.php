<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

try {
    // Get movement ID
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    
    if(empty($id)) {
        throw new Exception("Movement ID is required");
    }
    
    // Get movement details
    $query = "SELECT material_id, movement_type, quantity, status 
              FROM stock_movements 
              WHERE id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        throw new Exception("Stock movement not found");
    }
    
    $movement = $result->fetch_assoc();
    
    // Check if movement is already cancelled
    if($movement['status'] === 'cancelled') {
        throw new Exception("Movement is already cancelled");
    }
    
    // For outbound cancellation, check if there's enough stock to return
    if($movement['movement_type'] === 'outbound') {
        $stockQuery = "SELECT current_stock FROM raw_materials WHERE id = ?";
        $stockStmt = $connect->prepare($stockQuery);
        $stockStmt->bind_param("i", $movement['material_id']);
        $stockStmt->execute();
        $stockResult = $stockStmt->get_result();
        $currentStock = $stockResult->fetch_assoc()['current_stock'];
        
        // No need to check stock for outbound cancellation as we're adding back
    }
    
    // Start transaction
    $connect->begin_transaction();
    
    // Update movement status
    $updateQuery = "UPDATE stock_movements SET status = 'cancelled' WHERE id = ?";
    $updateStmt = $connect->prepare($updateQuery);
    $updateStmt->bind_param("i", $id);
    
    if(!$updateStmt->execute()) {
        throw new Exception("Failed to cancel stock movement");
    }
    
    // Revert material stock
    // If original movement was inbound, subtract the quantity
    // If original movement was outbound, add the quantity back
    $stockUpdateQuery = "UPDATE raw_materials SET 
        current_stock = current_stock " . ($movement['movement_type'] === 'inbound' ? '-' : '+') . " ?
    WHERE id = ?";
    
    $stockUpdateStmt = $connect->prepare($stockUpdateQuery);
    $stockUpdateStmt->bind_param("di", $movement['quantity'], $movement['material_id']);
    
    if(!$stockUpdateStmt->execute()) {
        throw new Exception("Failed to update material stock");
    }
    
    // Check if the resulting stock would be negative
    $finalStockQuery = "SELECT current_stock FROM raw_materials WHERE id = ?";
    $finalStockStmt = $connect->prepare($finalStockQuery);
    $finalStockStmt->bind_param("i", $movement['material_id']);
    $finalStockStmt->execute();
    $finalStockResult = $finalStockStmt->get_result();
    $finalStock = $finalStockResult->fetch_assoc()['current_stock'];
    
    if($finalStock < 0) {
        throw new Exception("Cannot cancel movement: Would result in negative stock");
    }
    
    // Commit transaction
    $connect->commit();
    
    $response = array(
        'success' => true,
        'messages' => 'Stock movement cancelled successfully'
    );
    
} catch(Exception $e) {
    // Rollback transaction on error
    if(isset($connect) && $connect->connect_errno === 0) {
        $connect->rollback();
    }
    
    $response = array(
        'success' => false,
        'messages' => $e->getMessage()
    );
}

// Close the database connection
$connect->close();

// Return the JSON response
echo json_encode($response); 