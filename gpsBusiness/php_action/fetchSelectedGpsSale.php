<?php
require_once 'core.php';

if($_POST) {
    $saleId = $_POST['saleId'];
    
    $sql = "SELECT * FROM gps_sales WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $saleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_array();
    
    $stmt->close();
    $connect->close();
    
    echo json_encode($row);
} 