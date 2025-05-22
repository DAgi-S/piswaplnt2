<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $paymentId = $_POST['paymentId'];

    // First, get the image filename if exists
    $sql = "SELECT payment_image FROM gps_payment_followup WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_array();
    $payment_image = $row['payment_image'];

    // Delete the record
    $sql = "DELETE FROM gps_payment_followup WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $paymentId);

    if($stmt->execute()) {
        // If there was an image, delete it
        if($payment_image) {
            $target = "../assets/images/payment_images/" . $payment_image;
            if(file_exists($target)) {
                unlink($target);
            }
        }

        $valid['success'] = true;
        $valid['messages'] = "Payment follow-up record removed successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while removing payment follow-up record";
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 