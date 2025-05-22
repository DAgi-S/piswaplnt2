<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $saleId = $_POST['saleId'];
    
    $sql = "DELETE FROM gps_sales WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $saleId);
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Sale removed successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while removing the sale";
    }
    
    $stmt->close();
    $connect->close();
    
    echo json_encode($valid);
} 