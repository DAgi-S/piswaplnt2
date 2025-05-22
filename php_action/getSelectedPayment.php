<?php 
require_once 'core.php';

if($_POST) {
    $paymentId = $_POST['paymentId'];
    $sql = "SELECT * FROM paid_on WHERE id = $paymentId AND account_id = ".$_SESSION['userId'];
    $result = $connect->query($sql);
    
    if($result->num_rows > 0) {
        $row = $result->fetch_array();
    }

    $connect->close();
    echo json_encode($row);
} 