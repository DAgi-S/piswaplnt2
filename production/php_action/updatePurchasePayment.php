<?php
require_once 'core.php';

// Set proper headers
header('Content-Type: application/json');

// Initialize response array
$response = array(
    'success' => false,
    'messages' => ''
);

try {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and validate required fields
    $purchaseNumber = isset($_POST['purchase_number']) ? trim($_POST['purchase_number']) : '';
    
    if (empty($purchaseNumber)) {
        throw new Exception('Purchase number is required');
    }

    $paymentStatus = isset($_POST['payment_status']) ? strtolower(trim($_POST['payment_status'])) : 'unpaid';
    $paidAmount = isset($_POST['paid_amount']) ? floatval($_POST['paid_amount']) : 0;
    $paymentDate = isset($_POST['payment_date']) ? trim($_POST['payment_date']) : date('Y-m-d');
    $paymentMethod = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
    $referenceNumber = isset($_POST['reference_number']) ? trim($_POST['reference_number']) : '';
    $paymentNotes = isset($_POST['payment_notes']) ? trim($_POST['payment_notes']) : '';

    // Validate payment date
    $inputDate = new DateTime($paymentDate);
    $today = new DateTime();
    if ($inputDate > $today) {
        throw new Exception('Payment date cannot be in the future');
    }

    // Handle file upload if provided
    $paymentProof = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['payment_proof'];
        $fileName = $file['name'];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, JPEG, PNG, and PDF files are allowed.');
        }

        // Generate unique filename
        $newFileName = uniqid('payment_') . '_' . date('Ymd') . '.' . $fileType;
        $uploadPath = '../../uploads/payment_proofs/' . $newFileName;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload payment proof');
        }

        $paymentProof = 'uploads/payment_proofs/' . $newFileName;
    }

    // Start transaction
    $connect->begin_transaction();

    // Get current purchase details and total paid amount
    $sql = "SELECT p.id, p.grand_total, COALESCE(SUM(pp.amount), 0) as total_paid 
            FROM purchases p 
            LEFT JOIN purchase_payments pp ON p.id = pp.purchase_id 
            WHERE p.purchase_number = ? AND p.active = 1 
            GROUP BY p.id";
    
    $stmt = $connect->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare purchase query: " . $connect->error);
    }
    
    $stmt->bind_param("s", $purchaseNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $purchase = $result->fetch_assoc();

    if (!$purchase) {
        throw new Exception("Purchase order not found or inactive");
    }

    $purchaseId = $purchase['id'];
    $grandTotal = floatval($purchase['grand_total']);
    $currentPaidAmount = floatval($purchase['total_paid']);
    $remainingAmount = $grandTotal - $currentPaidAmount;

    // Calculate the payment amount to be recorded
    $paymentAmount = 0;
    
    if ($paymentStatus === 'paid') {
        $paymentAmount = $remainingAmount;
        $newTotalPaid = $grandTotal;
    } else if ($paymentStatus === 'partially_paid') {
        if ($paidAmount <= $currentPaidAmount) {
            throw new Exception("New paid amount must be greater than current paid amount for partially paid status");
        }
        if ($paidAmount >= $grandTotal) {
            throw new Exception("For partial payment, amount must be less than grand total");
        }
        $paymentAmount = $paidAmount - $currentPaidAmount;
        $newTotalPaid = $currentPaidAmount + $paymentAmount;
    } else {
        // For unpaid status, reverse all payments
        $paymentAmount = -$currentPaidAmount;
        $newTotalPaid = 0;
    }

    // Validate payment amount
    if ($newTotalPaid > $grandTotal) {
        throw new Exception("Total paid amount cannot exceed grand total");
    }

    // Insert into purchase_payments if there's a payment to record
    if ($paymentAmount > 0) {
        $insertPaymentSql = "INSERT INTO purchase_payments (
            purchase_id, 
            payment_date, 
            amount, 
            payment_method, 
            reference_number, 
            notes,
            payment_proof
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $insertStmt = $connect->prepare($insertPaymentSql);
        
        if (!$insertStmt) {
            throw new Exception("Failed to prepare payment insert query: " . $connect->error);
        }

        $insertStmt->bind_param("isdssss", 
            $purchaseId,
            $paymentDate,
            $paymentAmount,
            $paymentMethod,
            $referenceNumber,
            $paymentNotes,
            $paymentProof
        );

        if (!$insertStmt->execute()) {
            throw new Exception("Failed to insert payment record: " . $insertStmt->error);
        }
    }

    // If status is being set to unpaid, delete all payment records and their proofs
    if ($paymentStatus === 'unpaid') {
        // Get payment proofs to delete
        $getProofsSql = "SELECT payment_proof FROM purchase_payments WHERE purchase_id = ? AND payment_proof IS NOT NULL";
        $getProofsStmt = $connect->prepare($getProofsSql);
        $getProofsStmt->bind_param("i", $purchaseId);
        $getProofsStmt->execute();
        $proofs = $getProofsStmt->get_result();
        
        // Delete physical files
        while ($proof = $proofs->fetch_assoc()) {
            if ($proof['payment_proof']) {
                $filePath = '../../' . $proof['payment_proof'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }

        // Delete payment records
        $deletePaymentsSql = "DELETE FROM purchase_payments WHERE purchase_id = ?";
        $deleteStmt = $connect->prepare($deletePaymentsSql);
        if (!$deleteStmt) {
            throw new Exception("Failed to prepare delete payments query: " . $connect->error);
        }
        $deleteStmt->bind_param("i", $purchaseId);
        if (!$deleteStmt->execute()) {
            throw new Exception("Failed to delete payment records: " . $deleteStmt->error);
        }
    }

    // Update purchase payment status
    $updateSql = "UPDATE purchases SET 
                    payment_status = ?,
                    paid_amount = ?,
                    payment_date = ?,
                    last_payment_date = CASE 
                        WHEN ? > 0 THEN ? 
                        ELSE last_payment_date 
                    END,
                    updated_at = CURRENT_TIMESTAMP
                  WHERE id = ? AND active = 1";

    $updateStmt = $connect->prepare($updateSql);
    
    if (!$updateStmt) {
        throw new Exception("Failed to prepare update query: " . $connect->error);
    }

    $updateStmt->bind_param("sdsdsi", 
        $paymentStatus,
        $newTotalPaid,
        $paymentDate,
        $paymentAmount,
        $paymentDate,
        $purchaseId
    );

    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update payment status: " . $updateStmt->error);
    }

    if ($updateStmt->affected_rows === 0) {
        throw new Exception("No changes were made to the purchase order");
    }

    // Commit transaction
    $connect->commit();

    $response['success'] = true;
    $response['messages'] = "Payment status updated successfully";

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($connect) && $connect->ping()) {
        $connect->rollback();
    }
    
    // Delete uploaded file if exists and there was an error
    if (isset($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
    error_log("Payment update error: " . $e->getMessage());

} finally {
    // Close all statements and connection
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if (isset($updateStmt) && $updateStmt instanceof mysqli_stmt) {
        $updateStmt->close();
    }
    if (isset($insertStmt) && $insertStmt instanceof mysqli_stmt) {
        $insertStmt->close();
    }
    if (isset($deleteStmt) && $deleteStmt instanceof mysqli_stmt) {
        $deleteStmt->close();
    }
    if (isset($connect) && $connect instanceof mysqli) {
        $connect->close();
    }
}

// Return JSON response
echo json_encode($response); 