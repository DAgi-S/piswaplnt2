<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $expense_date = $_POST['expense_date'];
    $expense_name = $_POST['expense_name'];
    $expense_type = $_POST['expense_type'];
    $amount = $_POST['amount'];
    $comment = $_POST['comment'];
    
    $sql = "INSERT INTO gps_expenses (
        expense_date,
        expense_name,
        expense_type,
        amount,
        comment
    ) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param(
        'sssds',
        $expense_date,
        $expense_name,
        $expense_type,
        $amount,
        $comment
    );
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Expense added successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while adding the expense";
    }
    
    $stmt->close();
    $connect->close();
    
    echo json_encode($valid);
} 