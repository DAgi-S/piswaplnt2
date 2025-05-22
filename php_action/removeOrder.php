<?php 
require_once 'core.php';
require_once 'db_connect.php';

if($_POST) {
    $orderId = $_POST['orderId'];
    $valid = array('success' => false, 'messages' => array());

    try {
        // Start transaction
        $connect->begin_transaction();

        // First delete order items
        $deleteItemsSql = "DELETE FROM order_items WHERE order_id = ?";
        $itemStmt = $connect->prepare($deleteItemsSql);
        if(!$itemStmt) {
            throw new Exception("Failed to prepare delete items statement: " . $connect->error);
        }
        $itemStmt->bind_param("i", $orderId);
        if(!$itemStmt->execute()) {
            throw new Exception("Failed to delete order items: " . $itemStmt->error);
        }
        $itemStmt->close();

        // Then delete the order
        $deleteOrderSql = "DELETE FROM orders WHERE order_id = ?";
        $orderStmt = $connect->prepare($deleteOrderSql);
        if(!$orderStmt) {
            throw new Exception("Failed to prepare delete order statement: " . $connect->error);
        }
        $orderStmt->bind_param("i", $orderId);
        if(!$orderStmt->execute()) {
            throw new Exception("Failed to delete order: " . $orderStmt->error);
        }
        $orderStmt->close();

        // If we got here, commit the transaction
        $connect->commit();
        
        $valid['success'] = true;
        $valid['messages'] = "Order successfully removed";
        
    } catch (Exception $e) {
        // Something went wrong, rollback the transaction
        $connect->rollback();
        
        $valid['success'] = false;
        $valid['messages'] = $e->getMessage();
    }

    $connect->close();

    echo json_encode($valid);
}