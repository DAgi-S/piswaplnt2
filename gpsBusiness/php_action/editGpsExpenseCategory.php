<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $categoryId = $_POST['categoryId'];
    $name = $_POST['editCategoryName'];
    $description = $_POST['editDescription'];
    $status = $_POST['editStatus'];

    $sql = "UPDATE gps_expense_categories 
            SET name = ?, 
                description = ?, 
                status = ? 
            WHERE id = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('ssii', $name, $description, $status, $categoryId);
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Expense category successfully updated";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while updating expense category";
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 