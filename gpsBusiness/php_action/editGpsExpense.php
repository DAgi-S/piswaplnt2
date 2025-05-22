<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $expenseId = $_POST['expenseId'];
    $expense_date = $_POST['editExpenseDate'];
    $expense_name = $_POST['editExpenseName'];
    $expense_type = $_POST['editExpenseType'];
    $amount = $_POST['editAmount'];
    $comment = $_POST['editComment'];
    
    $sql = "UPDATE gps_expenses SET 
        expense_date = ?,
        expense_name = ?,
        expense_type = ?,
        amount = ?,
        comment = ?
        WHERE id = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param(
        'sssdsi',
        $expense_date,
        $expense_name,
        $expense_type,
        $amount,
        $comment,
        $expenseId
    );
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Expense updated successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while updating the expense";
    }
    
    $stmt->close();
    $connect->close();
    
    echo json_encode($valid);
} 