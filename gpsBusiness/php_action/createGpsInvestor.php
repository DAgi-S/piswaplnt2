<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $name = $_POST['name'];
    $sharePercentage = $_POST['sharePercentage'];
    $investment = $_POST['investment'];
    $accountType = $_POST['accountType'];
    $balance = $_POST['balance'];
    $creditAmount = $_POST['creditAmount'];

    $sql = "INSERT INTO gps_investors (name, share_percentage, investment, account_type, balance, credit_amount) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('sddsdd', $name, $sharePercentage, $investment, $accountType, $balance, $creditAmount);
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Investor successfully created";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while creating investor";
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 