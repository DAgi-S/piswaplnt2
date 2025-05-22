<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $purchaseId = $_POST['purchaseId'];

    // Check if purchase exists and is not paid
    $checkSql = "SELECT payment_status FROM purchases WHERE id = ?";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bind_param("i", $purchaseId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if($row['payment_status'] == 'paid') {
            $valid['success'] = false;
            $valid['messages'] = "Cannot delete a paid purchase";
            
            echo json_encode($valid);
            exit();
        }
        
        // First delete purchase items
        $deleteItemsSql = "DELETE FROM purchase_items WHERE purchase_id = ?";
        $deleteItemsStmt = $connect->prepare($deleteItemsSql);
        $deleteItemsStmt->bind_param("i", $purchaseId);
        $deleteItemsStmt->execute();
        
        // Then delete the purchase
        $deletePurchaseSql = "DELETE FROM purchases WHERE id = ?";
        $deletePurchaseStmt = $connect->prepare($deletePurchaseSql);
        $deletePurchaseStmt->bind_param("i", $purchaseId);
        $deletePurchaseStmt->execute();
        
        if($deleteItemsStmt->affected_rows >= 0 && $deletePurchaseStmt->affected_rows > 0) {
            $valid['success'] = true;
            $valid['messages'] = "Purchase successfully deleted";
        } else {
            $valid['success'] = false;
            $valid['messages'] = "Error while deleting the purchase";
        }
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Purchase not found";
    }
    
    $connect->close();

    echo json_encode($valid);
} 