<?php
require_once '../../php_action/core.php';

$response = array(
    'success' => false,
    'messages' => ''
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Enable error logging
        error_log("Starting payment creation process");
        
        // Handle file upload
        $image_location = null;
        if (isset($_FILES['payment_image']) && $_FILES['payment_image']['error'] === 0) {
            error_log("File upload detected");
            
            $upload_dir = dirname(dirname(__FILE__)) . '/uploads/payments/';
            error_log("Upload directory: " . $upload_dir);
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                error_log("Creating upload directory");
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Failed to create upload directory");
                }
            }
            
            // Generate unique filename
            $file_extension = strtolower(pathinfo($_FILES['payment_image']['name'], PATHINFO_EXTENSION));
            error_log("File extension: " . $file_extension);
            
            // Only allow certain file types
            $allowed_types = array('jpg', 'jpeg', 'png', 'pdf');
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG and PDF files are allowed.");
            }
            
            $file_name = uniqid('payment_') . '_' . date('Ymd') . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;
            error_log("Target file: " . $target_file);
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['payment_image']['tmp_name'], $target_file)) {
                error_log("File moved successfully");
                $image_location = 'uploads/payments/' . $file_name;
                error_log("Image location set to: " . $image_location);
                chmod($target_file, 0644); // Set proper file permissions
            } else {
                $upload_error = error_get_last();
                throw new Exception("Error uploading file. PHP Error: " . ($upload_error ? $upload_error['message'] : 'Unknown error'));
            }
        }

        // Get form data
        $payment_number = mysqli_real_escape_string($connect, $_POST['payment_number']);
        $gps_order_id = mysqli_real_escape_string($connect, $_POST['gps_order_id']);
        $payment_date = mysqli_real_escape_string($connect, $_POST['payment_date']);
        $payment_type = mysqli_real_escape_string($connect, $_POST['payment_type']);
        $paid_by = mysqli_real_escape_string($connect, $_POST['paid_by']);
        $paid_amount = (float)$_POST['paid_amount'];
        $currency = mysqli_real_escape_string($connect, $_POST['currency']);
        $rate = isset($_POST['rate']) ? (float)$_POST['rate'] : null;
        $bank = isset($_POST['bank']) ? mysqli_real_escape_string($connect, $_POST['bank']) : null;
        $deposited_to = isset($_POST['deposited_to']) ? mysqli_real_escape_string($connect, $_POST['deposited_to']) : null;

        // Insert payment record
        $sql = "INSERT INTO gps_payments (
                    payment_number, 
                    gps_order_id, 
                    payment_date, 
                    payment_type, 
                    paid_by, 
                    paid_amount, 
                    currency, 
                    rate, 
                    bank, 
                    deposited_to,
                    image_location,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        error_log("SQL Query: " . $sql);
        error_log("Image location before insert: " . ($image_location ?? 'NULL'));

        $stmt = $connect->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $connect->error);
        }

        $stmt->bind_param(
            'sisssdsssss',
            $payment_number,
            $gps_order_id,
            $payment_date,
            $payment_type,
            $paid_by,
            $paid_amount,
            $currency,
            $rate,
            $bank,
            $deposited_to,
            $image_location
        );

        if ($stmt->execute()) {
            error_log("Payment record inserted successfully");
            $response['success'] = true;
            $response['messages'] = 'Payment added successfully';
        } else {
            throw new Exception("Error creating payment: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        $response['messages'] = $e->getMessage();
        // Delete uploaded file if payment record creation fails
        if (isset($image_location) && file_exists($target_file)) {
            unlink($target_file);
        }
    }
}

$connect->close();

header('Content-Type: application/json');
echo json_encode($response); 