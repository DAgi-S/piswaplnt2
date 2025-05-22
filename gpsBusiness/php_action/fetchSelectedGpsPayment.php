<?php
require_once 'core.php';

if($_POST) {
    $paymentId = $_POST['paymentId'];
    
    $sql = "SELECT * FROM gps_payments WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_array();
    
    $stmt->close();
    $connect->close();
    
    echo json_encode($row);
} 