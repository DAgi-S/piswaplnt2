<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $name = $_POST['categoryName'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    $sql = "INSERT INTO gps_expense_categories (name, description, status) VALUES (?, ?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('ssi', $name, $description, $status);
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Expense category successfully created";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while creating expense category";
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 