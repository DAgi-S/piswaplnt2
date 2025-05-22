<?php 
require_once 'core.php';

if($_POST) {
    $paymentId = $_POST['paymentId'];
    
    $sql = "DELETE FROM paid_on WHERE id = $paymentId AND account_id = ".$_SESSION['userId'];

    if($connect->query($sql) === TRUE) {
        $valid['success'] = true;
        $valid['messages'] = "Payment removed successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while removing payment";
    }

    $connect->close();
    echo json_encode($valid);
} 