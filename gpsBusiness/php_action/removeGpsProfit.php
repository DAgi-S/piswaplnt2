<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $profitId = $_POST['profitId'];
    
    $sql = "DELETE FROM gps_profit WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $profitId);
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Profit record removed successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while removing the profit record";
    }
    
    $stmt->close();
    $connect->close();
    
    echo json_encode($valid);
} 