<?php 
require_once 'core.php';

if($_POST) {
    $paymentId = $_POST['paymentId'];
    $websiteName = $_POST['editWebsiteName'];
    $transactionId = $_POST['editTransactionId'];
    $amount = $_POST['editAmount'];
    $transactionDate = $_POST['editTransactionDate'];
    $paymentReason = $_POST['editPaymentReason'];
    
    $sql = "UPDATE paid_on 
            SET website_name = '$websiteName',
                transaction_id = '$transactionId',
                amount = '$amount',
                transaction_date = '$transactionDate',
                payment_reason = '$paymentReason'
            WHERE id = $paymentId AND account_id = ".$_SESSION['userId'];

    if($connect->query($sql) === TRUE) {
        $valid['success'] = true;
        $valid['messages'] = "Payment updated successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while updating payment";
    }

    $connect->close();
    echo json_encode($valid);
}