<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

$paymentId = $_POST['paymentId'];

if($paymentId) {
    $sql = "DELETE FROM gps_payments WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $paymentId);
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Payment successfully removed";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while removing payment";
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 