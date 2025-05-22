<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'core.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(array(
        'success' => false,
        'messages' => array('Session expired. Please log in again.')
    ));
    exit();
}

$response = array(
    'success' => false,
    'messages' => array()
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required fields
        $required_fields = array('payment_date', 'paid_by', 'currency', 'amount');
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }

        // Get form data
        $payment_date = mysqli_real_escape_string($connect, $_POST['payment_date']);
        $paid_by = mysqli_real_escape_string($connect, $_POST['paid_by']);
        $currency = mysqli_real_escape_string($connect, $_POST['currency']);
        $amount = (float)$_POST['amount'];
        $rate = isset($_POST['rate']) && !empty($_POST['rate']) ? (float)$_POST['rate'] : null;
        $transfer_to = isset($_POST['transfer_to']) ? mysqli_real_escape_string($connect, $_POST['transfer_to']) : null;
        $bank_platform_name = isset($_POST['bank_platform_name']) ? mysqli_real_escape_string($connect, $_POST['bank_platform_name']) : null;
        $comment = isset($_POST['comment']) ? mysqli_real_escape_string($connect, $_POST['comment']) : null;

        // Validate amount
        if ($amount <= 0) {
            throw new Exception("Amount must be greater than zero.");
        }

        // Handle file upload
        $payment_image = null;
        if (isset($_FILES['payment_image']) && $_FILES['payment_image']['error'] === 0) {
            $upload_dir = '../uploads/payment_followup/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['payment_image']['name'], PATHINFO_EXTENSION));
            $allowed_types = array('jpg', 'jpeg', 'png', 'pdf');
            
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG and PDF files are allowed.");
            }

            // Validate file size (max 5MB)
            if ($_FILES['payment_image']['size'] > 5 * 1024 * 1024) {
                throw new Exception("File size too large. Maximum size is 5MB.");
            }

            $file_name = uniqid('payment_') . '_' . date('Ymd') . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['payment_image']['tmp_name'], $target_file)) {
                $payment_image = 'uploads/payment_followup/' . $file_name;
                chmod($target_file, 0644);
            } else {
                throw new Exception("Error uploading file.");
            }
        }

        // Insert payment follow-up record
        $sql = "INSERT INTO gps_payment_followup (
                    payment_date,
                    paid_by,
                    currency,
                    amount,
                    rate,
                    transfer_to,
                    bank_platform_name,
                    comment,
                    payment_image
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $connect->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $connect->error);
        }

        $stmt->bind_param(
            'sssddssss',
            $payment_date,
            $paid_by,
            $currency,
            $amount,
            $rate,
            $transfer_to,
            $bank_platform_name,
            $comment,
            $payment_image
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['messages'][] = "Payment follow-up created successfully";
            $response['id'] = $stmt->insert_id;
        } else {
            throw new Exception("Error creating payment follow-up: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        $response['messages'][] = $e->getMessage();
        // Delete uploaded file if record creation fails
        if (isset($target_file) && file_exists($target_file)) {
            unlink($target_file);
        }
    }
} else {
    $response['messages'][] = "Invalid request method";
}

// Close the database connection
if (isset($connect)) {
    $connect->close();
}

// Set the content type to JSON
header('Content-Type: application/json');
echo json_encode($response); 