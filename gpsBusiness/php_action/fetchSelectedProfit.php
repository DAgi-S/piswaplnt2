<?php
require_once 'core.php';

if($_POST) {
    $profitId = $_POST['profitId'];
    
    $sql = "SELECT * FROM gps_profit WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $profitId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $row = $result->fetch_array();
    }
    
    $stmt->close();
    $connect->close();
    
    echo json_encode($row);
} 