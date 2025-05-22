<?php 
require_once 'core.php';

if($_POST) {
    $paymentId = $_POST['paymentId'];
    $platform = $_POST['editPlatform'];
    
    $sql = "UPDATE digitalswap 
            SET platform = '$platform'
            WHERE id = $paymentId";

    if($connect->query($sql) === TRUE) {
        $valid['success'] = true;
        $valid['messages'] = "Payment platform updated successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while updating payment platform";
    }

    $connect->close();
    echo json_encode($valid);
} 