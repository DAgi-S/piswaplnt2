<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

$categoryId = $_POST['categoryId'];

if($categoryId) {
    $sql = "DELETE FROM gps_expense_categories WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $categoryId);
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Expense category successfully removed";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while removing expense category";
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 