<?php
require_once 'core.php';

if($_POST) {
    $expenseId = $_POST['expenseId'];
    
    $sql = "SELECT * FROM gps_expenses WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $expenseId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_array();
    
    $stmt->close();
    $connect->close();
    
    echo json_encode($row);
} 