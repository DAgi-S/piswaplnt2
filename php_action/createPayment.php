<?php 
require_once 'core.php';

if($_POST) {
    $websiteName = $_POST['websiteName'];
    $transactionId = $_POST['transactionId'];
    $amount = $_POST['amount'];
    $transactionDate = $_POST['transactionDate'];
    $paymentReason = $_POST['paymentReason'];
    
    $sql = "INSERT INTO paid_on (website_name, transaction_id, account_id, amount, transaction_date, payment_reason) 
            VALUES ('$websiteName', '$transactionId', ".$_SESSION['userId'].", '$amount', '$transactionDate', '$paymentReason')";

    if($connect->query($sql) === TRUE) {
        $valid['success'] = true;
        $valid['messages'] = "Payment added successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while adding payment";
    }

    $connect->close();
    echo json_encode($valid);
} 