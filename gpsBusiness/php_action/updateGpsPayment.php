<?php
require_once '../../php_action/core.php';

$response = array(
    'success' => false,
    'messages' => ''
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Enable error logging
        error_log("Starting payment update process");
        error_log("POST data received: " . print_r($_POST, true));
        error_log("FILES data received: " . print_r($_FILES, true));
        
        // Handle file upload
        $image_location = null;
        if (isset($_FILES['editPaymentImage']) && $_FILES['editPaymentImage']['error'] === 0) {
            error_log("File upload detected");
            
            $upload_dir = '../uploads/payments/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = strtolower(pathinfo($_FILES['editPaymentImage']['name'], PATHINFO_EXTENSION));
            
            // Only allow certain file types
            $allowed_types = array('jpg', 'jpeg', 'png', 'pdf');
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG and PDF files are allowed.");
            }
            
            $file_name = uniqid('payment_') . '_' . date('Ymd') . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['editPaymentImage']['tmp_name'], $target_file)) {
                $image_location = 'uploads/payments/' . $file_name;
                chmod($target_file, 0644);
                
                // Delete old image if exists
                if (!empty($_POST['editOldImageLocation'])) {
                    $old_file = dirname(dirname(__FILE__)) . '/' . $_POST['editOldImageLocation'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
            } else {
                throw new Exception("Error uploading file.");
            }
        } else {
            // Keep existing image if no new image uploaded
            $image_location = isset($_POST['editOldImageLocation']) ? $_POST['editOldImageLocation'] : null;
        }

        // Get form data
        $payment_id = (int)$_POST['editPaymentId'];
        $gps_order_id = (int)$_POST['editGpsOrderId'];
        $payment_date = $_POST['editPaymentDate'];
        $payment_type = $_POST['editPaymentType'];
        $paid_by = $_POST['editPaidBy'];
        $paid_amount = (float)$_POST['editPaidAmount'];
        $currency = $_POST['editCurrency'];
        $rate = !empty($_POST['editRate']) ? (float)$_POST['editRate'] : null;
        $bank = !empty($_POST['editBank']) ? $_POST['editBank'] : null;
        $deposited_to = !empty($_POST['editDepositedTo']) ? $_POST['editDepositedTo'] : null;

        error_log("Processing payment update for ID: " . $payment_id);

        // Update payment record
        $sql = "UPDATE gps_payments SET 
                gps_order_id = ?, 
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
        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $connect->error);
        }

        $stmt->bind_param(
            'isssdssssssi',
            $gps_order_id,
            $payment_date,
            $payment_type,
            $paid_by,
            $paid_amount,
            $currency,
            $rate,
            $bank,
            $deposited_to,
            $image_location,
            $payment_id
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = 'Payment updated successfully';
        } else {
            throw new Exception("Error updating payment: " . $stmt->error);
        }
        
        $stmt->close();

    } catch (Exception $e) {
        error_log("Error in updateGpsPayment.php: " . $e->getMessage());
        $response['messages'] = $e->getMessage();
        // Delete uploaded file if payment record update fails
        if (isset($target_file) && file_exists($target_file) && $image_location !== $_POST['editOldImageLocation']) {
            unlink($target_file);
        }
    }
}

$connect->close();
header('Content-Type: application/json');
echo json_encode($response); 