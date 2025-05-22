<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $investorId = $_POST['investorId'];
    $name = $_POST['editName'];
    $sharePercentage = $_POST['editSharePercentage'];
    $investment = $_POST['editInvestment'];
    $accountType = $_POST['editAccountType'];
    $balance = $_POST['editBalance'];
    $creditAmount = $_POST['editCreditAmount'];

    $sql = "UPDATE gps_investors 
            SET name = ?, 
                share_percentage = ?, 
                investment = ?, 
                account_type = ?, 
                balance = ?, 
                credit_amount = ? 
            WHERE id = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('sddsddj', $name, $sharePercentage, $investment, $accountType, $balance, $creditAmount, $investorId);
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Investor successfully updated";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while updating investor";
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 