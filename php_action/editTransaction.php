<?php
require_once 'db_connect.php';

// Check for valid session
if (!isset($_SESSION['userId'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    // Validate input
    $transaction_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($transaction_id <= 0) {
        throw new Exception('Invalid transaction ID');
    }

    // Sanitize and validate inputs
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
    $type = filter_var($_POST['type'], FILTER_SANITIZE_STRING);
    $platform_id = filter_var($_POST['platform_id'], FILTER_VALIDATE_INT);
    $account_id = filter_var($_POST['account_id'], FILTER_VALIDATE_INT);
    $status = isset($_POST['status']) ? 1 : 0;
    $comment = filter_var($_POST['comment'], FILTER_SANITIZE_STRING);
    $transaction_date = filter_var($_POST['transaction_date'], FILTER_SANITIZE_STRING);

    // Handle file upload if present
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $upload_dir = '../uploads/';
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (!in_array($file_ext, $allowed_types)) {
            throw new Exception('Invalid file type');
        }
        
        $new_filename = 'transaction_' . time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $new_filename;
        }
    }

    // Begin transaction
    $connect->begin_transaction();

    // Update transaction
    $sql = "UPDATE digitalswap SET 
            amount = ?,
            type = ?,
            platform_id = ?,
            account_id = ?,
            status = ?,
            comment = ?,
            transaction_date = ?,
            updated_at = NOW()";
    
    // Add image to update if new one uploaded
    if ($image_path) {
        $sql .= ", image = ?";
    }
    
    $sql .= " WHERE id = ?";

    $stmt = $connect->prepare($sql);
    
    if ($image_path) {
        $stmt->bind_param("dsiissss", $amount, $type, $platform_id, $account_id, $status, $comment, $transaction_date, $image_path, $transaction_id);
    } else {
        $stmt->bind_param("dsiisss", $amount, $type, $platform_id, $account_id, $status, $comment, $transaction_date, $transaction_id);
    }
    
    $stmt->execute();

    // Log the update
    $log_sql = "INSERT INTO transaction_logs (transaction_id, user_id, action, details) VALUES (?, ?, 'UPDATE', ?)";
    $log_stmt = $connect->prepare($log_sql);
    $log_details = json_encode($_POST);
    $log_stmt->bind_param("iis", $transaction_id, $_SESSION['userId'], $log_details);
    $log_stmt->execute();

    $connect->commit();
    
    echo json_encode(['success' => true, 'message' => 'Transaction updated successfully']);

} catch (Exception $e) {
    if ($connect->inTransaction()) {
        $connect->rollback();
    }
    error_log("Edit Transaction Error: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred while updating the transaction', 'details' => $e->getMessage()]);
} 