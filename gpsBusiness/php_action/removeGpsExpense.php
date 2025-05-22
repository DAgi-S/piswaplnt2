<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $expenseId = $_POST['expenseId'];
    
    $sql = "DELETE FROM gps_expenses WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $expenseId);
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Expense removed successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while removing the expense";
    }
    
    $stmt->close();
    $connect->close();
    
    echo json_encode($valid);
} 