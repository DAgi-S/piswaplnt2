<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $purchaseId = $_POST['purchaseId'];

    // Get purchase items to revert stock
    $sql = "SELECT product_id, quantity FROM purchase_items WHERE purchase_id = '$purchaseId'";
    $result = $connect->query($sql);
    while($row = $result->fetch_array()) {
        $sql = "UPDATE raw_materials SET quantity = quantity - ".$row[1]." WHERE id = '".$row[0]."'";
        $connect->query($sql);
    }

    // Delete purchase items
    $sql = "DELETE FROM purchase_items WHERE purchase_id = '$purchaseId'";
    $connect->query($sql);

    // Soft delete purchase
    $sql = "UPDATE purchases SET active = 2, updated_at = NOW() WHERE id = '$purchaseId'";
    $connect->query($sql);

    $valid['success'] = true;
    $valid['messages'] = "Successfully Removed";

    $connect->close();

    echo json_encode($valid);
} 