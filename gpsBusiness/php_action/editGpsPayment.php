<?php
require_once '../../php_action/core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $paymentId = $_POST['paymentId'];
    $gpsOrderId = $_POST['editGpsOrderId'];
    $paymentDate = $_POST['editPaymentDate'];
    $paymentType = $_POST['editPaymentType'];
    $paidBy = $_POST['editPaidBy'];
    $paidAmount = $_POST['editPaidAmount'];
    $currency = $_POST['editCurrency'];
    $rate = $_POST['editRate'];
    $bank = $_POST['editBank'];
    $depositedTo = $_POST['editDepositedTo'];
    $oldImageLocation = isset($_POST['editOldImageLocation']) ? $_POST['editOldImageLocation'] : '';

    // Handle file upload if a new image is provided
    $imageLocation = $oldImageLocation; // Default to old image
    if(isset($_FILES['editPaymentImage']) && $_FILES['editPaymentImage']['error'] === 0) {
        $upload_dir = '../uploads/payments/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $file_extension = pathinfo($_FILES['editPaymentImage']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('payment_') . '_' . date('Ymd') . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;

        // Move uploaded file
        if(move_uploaded_file($_FILES['editPaymentImage']['tmp_name'], $target_file)) {
            $imageLocation = 'uploads/payments/' . $file_name;
            
            // Delete old image if exists
            if($oldImageLocation && file_exists('../' . $oldImageLocation)) {
                unlink('../' . $oldImageLocation);
            }
        }
    }

    $sql = "UPDATE gps_payments 
            SET gps_order_id = ?, 
                payment_date = ?, 
                payment_type = ?, 
                paid_by = ?, 
                paid_amount = ?,
                currency = ?,
                rate = ?,
                bank = ?, 
                deposited_to = ?,
                image_location = ? 
            WHERE id = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('isssdsdssssi', $gpsOrderId, $paymentDate, $paymentType, $paidBy, $paidAmount, $currency, $rate, $bank, $depositedTo, $imageLocation, $paymentId);
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Payment successfully updated";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while updating payment: " . $connect->error;
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 