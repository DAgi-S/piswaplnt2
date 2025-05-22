<?php
// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unwanted output
ob_start();

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
while (ob_get_level()) {
    ob_end_clean();
}

// Set proper headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Default response
$response = array(
    'success' => false,
    'messages' => array()
);

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        throw new Exception("User not logged in");
    }

    // Check if payment_id is provided
    if (!isset($_POST['payment_id']) || empty($_POST['payment_id'])) {
        throw new Exception('Payment ID is required');
    }

    // Validate and sanitize inputs
    $paymentId = intval($_POST['payment_id']);
    $paymentDate = isset($_POST['payment_date']) ? $connect->real_escape_string($_POST['payment_date']) : null;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : null;
    $paymentMethod = isset($_POST['payment_method']) ? $connect->real_escape_string($_POST['payment_method']) : null;
    $accountId = isset($_POST['account_id']) ? intval($_POST['account_id']) : null;
    $referenceNumber = isset($_POST['reference_number']) ? $connect->real_escape_string($_POST['reference_number']) : null;
    $notes = isset($_POST['notes']) ? $connect->real_escape_string($_POST['notes']) : null;

    // Validate required fields
    if (!$paymentDate || !$amount || !$paymentMethod || !$accountId) {
        throw new Exception('Required fields are missing');
    }

    // Start transaction
    $connect->begin_transaction();

    // Get current payment details
    $stmt = $connect->prepare("SELECT * FROM sales_payments WHERE id = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $connect->error);
    }
    
    $stmt->bind_param('i', $paymentId);
    if (!$stmt->execute()) {
        throw new Exception("Error fetching payment: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $currentPayment = $result->fetch_assoc();
    
    if (!$currentPayment) {
        throw new Exception("Payment not found");
    }
    $stmt->close();

    // Handle file upload if new file is provided
    $paymentProof = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        // Validate file size (max 5MB)
        $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
        if ($_FILES['payment_proof']['size'] > $maxFileSize) {
            throw new Exception('File size too large. Maximum size allowed is 5MB.');
        }

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['payment_proof']['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = array(
            'image/jpeg',
            'image/png',
            'application/pdf'
        );

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and PDF files are allowed.');
        }

        $uploadDir = '../../uploads/payment_proofs/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception('Failed to create upload directory.');
            }
        }

        // Ensure directory is writable
        if (!is_writable($uploadDir)) {
            throw new Exception('Upload directory is not writable.');
        }

        // Get file info
        $fileName = $_FILES['payment_proof']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Generate unique filename with order info
        $orderInfo = '';
        if ($currentPayment && $currentPayment['sales_order_id']) {
            $stmt = $connect->prepare("SELECT order_number FROM sales_orders WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $currentPayment['sales_order_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $orderInfo = $row['order_number'] . '_';
                }
                $stmt->close();
            }
        }
        
        $newFileName = 'payment_' . $orderInfo . date('Ymd_His') . '_' . uniqid() . '.' . $fileExt;
        $uploadPath = $uploadDir . $newFileName;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload file. Please check file permissions.');
        }

        $paymentProof = 'uploads/payment_proofs/' . $newFileName;

        // Delete old file if exists
        if ($currentPayment && $currentPayment['payment_proof']) {
            $oldFile = '../../' . $currentPayment['payment_proof'];
            if (file_exists($oldFile) && is_file($oldFile)) {
                if (!unlink($oldFile)) {
                    error_log("Warning: Could not delete old payment proof file: " . $oldFile);
                }
            }
        }
    }

    // Update payment record
    $updateQuery = "UPDATE sales_payments SET 
        payment_date = ?,
        amount = ?,
        payment_method = ?,
        account_id = ?,
        reference_number = ?,
        notes = ?,
        status = 'pending',  /* Reset to pending when payment is updated */
        updated_at = NOW()";

    $params = array($paymentDate, $amount, $paymentMethod, $accountId, $referenceNumber, $notes);
    $types = "sdssss"; // string, double, string, string, string, string

    // Add payment proof to query if uploaded
    if ($paymentProof) {
        $updateQuery .= ", payment_proof = ?";
        $params[] = $paymentProof;
        $types .= "s";
    }

    $updateQuery .= " WHERE id = ?";
    $params[] = $paymentId;
    $types .= "i";

    $stmt = $connect->prepare($updateQuery);
    if (!$stmt) {
        throw new Exception("Error preparing update query: " . $connect->error);
    }

    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        throw new Exception("Error updating payment: " . $stmt->error);
    }
    $stmt->close();

    // Update sales order payment status and balance
    $updateOrderQuery = "UPDATE sales_orders so 
        INNER JOIN (
            SELECT sales_order_id, SUM(amount) as total_paid
            FROM sales_payments 
            WHERE sales_order_id = (SELECT sales_order_id FROM sales_payments WHERE id = ?)
            GROUP BY sales_order_id
        ) payments ON so.id = payments.sales_order_id
        SET 
            so.paid_amount = payments.total_paid,
            so.balance = so.total_amount - payments.total_paid,
            so.payment_status = CASE 
                WHEN payments.total_paid >= so.total_amount THEN 'paid'
                WHEN payments.total_paid > 0 THEN 'partial'
                ELSE 'unpaid'
            END,
            so.updated_at = NOW()";

    $stmt = $connect->prepare($updateOrderQuery);
    if (!$stmt) {
        throw new Exception("Error preparing order update query: " . $connect->error);
    }

    $stmt->bind_param('i', $paymentId);
    if (!$stmt->execute()) {
        throw new Exception("Error updating order: " . $stmt->error);
    }
    $stmt->close();

    // Get updated payment data for logging
    $stmt = $connect->prepare("SELECT * FROM sales_payments WHERE id = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Error preparing final query: " . $connect->error);
    }

    $stmt->bind_param('i', $paymentId);
    if (!$stmt->execute()) {
        throw new Exception("Error fetching updated payment: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $updatedPayment = $result->fetch_assoc();
    $stmt->close();

    // Commit transaction
    $connect->commit();

    $response['success'] = true;
    $response['messages'][] = 'Payment updated successfully';
    $response['payment'] = $updatedPayment;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($connect) && $connect->ping()) {
        $connect->rollback();
    }
    
    // Log the error
    error_log("Error in updateSalesPayment.php: " . $e->getMessage());
    
    $response['success'] = false;
    $response['messages'][] = $e->getMessage();
} finally {
    // Close database connection
    if (isset($connect)) {
        $connect->close();
    }
}

// Send response
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit(); 