<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

$investorId = $_POST['investorId'];

if($investorId) {
    $sql = "DELETE FROM gps_investors WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $investorId);
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Investor successfully removed";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while removing investor";
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 