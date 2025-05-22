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

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($response);
    exit();
}

try {
    // Log incoming data
    error_log("Payment POST data: " . print_r($_POST, true));

    // Validate input
    $required_fields = array(
        'payment_order_id' => 'Order ID',
        'account_id' => 'Account',
        'amount' => 'Payment Amount',
        'payment_method' => 'Payment Method',
        'payment_date' => 'Payment Date'
    );

    foreach ($required_fields as $field => $label) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception($label . ' is required');
        }
    }

    // Validate order ID format and existence
    $orderId = filter_var($_POST['payment_order_id'], FILTER_VALIDATE_INT);
    if ($orderId === false || $orderId <= 0) {
        throw new Exception('Invalid order ID format: ' . $_POST['payment_order_id']);
    }

    // Verify order exists and get current status
    $checkOrderSql = "SELECT id, total_amount, paid_amount, balance, payment_status, order_status, 
                             withholding_amount, grand_total
                     FROM sales_orders 
                     WHERE id = ? LIMIT 1";
    $checkOrderStmt = $connect->prepare($checkOrderSql);
    if (!$checkOrderStmt) {
        throw new Exception('Database error while checking order');
    }

    $checkOrderStmt->bind_param('i', $orderId);
    if (!$checkOrderStmt->execute()) {
        throw new Exception('Error verifying order: ' . $checkOrderStmt->error);
    }

    $orderResult = $checkOrderStmt->get_result();
    $orderData = $orderResult->fetch_assoc();
    if (!$orderData) {
        throw new Exception('Order not found with ID: ' . $orderId);
    }
    $checkOrderStmt->close();

    // Check if order is cancelled
    if ($orderData['order_status'] === 'cancelled') {
        throw new Exception('Cannot add payment to cancelled order');
    }

    // Check if order is already paid
    if ($orderData['payment_status'] === 'paid') {
        throw new Exception('Order is already fully paid');
    }

    // Get and validate input
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $referenceNumber = isset($_POST['reference_number']) ? $_POST['reference_number'] : '';
    $paymentDate = isset($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $accountId = isset($_POST['account_id']) ? intval($_POST['account_id']) : 0;

    // Handle file upload
    $paymentProof = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['payment_proof'];
        $fileName = $file['name'];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowedTypes = array('jpg', 'jpeg', 'png', 'pdf');
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG and PDF files are allowed.');
        }
        
        // Generate unique filename
        $newFileName = uniqid('payment_') . '_' . date('Ymd_His') . '.' . $fileType;
        $uploadPath = '../../uploads/payment_proofs/' . $newFileName;
        
        // Move file to upload directory
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload payment proof.');
        }
        
        $paymentProof = 'uploads/payment_proofs/' . $newFileName;
    }

    // Log received data
    error_log("Received payment data: " . print_r([
        'order_id' => $orderId,
        'amount' => $amount,
        'payment_method' => $paymentMethod,
        'account_id' => $accountId,
        'payment_date' => $paymentDate,
        'payment_proof' => $paymentProof
    ], true));

    // Validate inputs
    if ($amount <= 0) {
        throw new Exception('Payment amount must be greater than zero');
    }

    // Calculate new payment amounts
    $currentPaidAmount = floatval($orderData['paid_amount']);
    $totalAmount = floatval($orderData['total_amount']);
    $withholdingAmount = floatval($orderData['withholding_amount']);
    $grandTotal = floatval($orderData['grand_total']);
    $currentBalance = $grandTotal - $currentPaidAmount;  // Recalculate current balance
    
    // Validate payment amount against current balance
    if ($amount > $currentBalance) {
        throw new Exception(sprintf(
            'Payment amount (%.2f) exceeds remaining balance (%.2f)',
            $amount,
            $currentBalance
        ));
    }

    $newPaidAmount = $currentPaidAmount + $amount;
    $newBalance = $grandTotal - $newPaidAmount;
    
    // Determine new payment status (using absolute comparison for floating point)
    $newPaymentStatus = 'unpaid';
    if (abs($newBalance) < 0.01) {  // Consider as paid if balance is less than 0.01
        $newPaymentStatus = 'paid';
        $newBalance = 0; // Set to exactly zero to avoid negative display
    } elseif ($newPaidAmount > 0) {
        $newPaymentStatus = 'partial';
    }

    // Log payment calculation
    error_log("Payment calculation: " . print_r([
        'current_paid' => $currentPaidAmount,
        'grand_total' => $grandTotal,
        'new_paid' => $newPaidAmount,
        'new_balance' => $newBalance,
        'new_status' => $newPaymentStatus
    ], true));

    // Verify account exists and get its platform
    $accountSql = "SELECT a.id, a.account_owner 
                   FROM accounts a
                   WHERE a.id = ? AND a.status = 1 
                   LIMIT 1";
    $accountStmt = $connect->prepare($accountSql);
    if (!$accountStmt) {
        throw new Exception('Error preparing account query: ' . $connect->error);
    }

    $accountStmt->bind_param('i', $accountId);
    if (!$accountStmt->execute()) {
        throw new Exception('Error checking account: ' . $accountStmt->error);
    }
    
    $accountResult = $accountStmt->get_result();
    $accountData = $accountResult->fetch_assoc();
    if (!$accountData) {
        throw new Exception('Invalid or inactive account selected');
    }
    $accountStmt->close();

    // Get user ID and validate
    $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
    if (!$userId) {
        throw new Exception('User not logged in');
    }

    // Start transaction
    $connect->begin_transaction();

    try {
        // Insert payment record
        $paymentSql = "INSERT INTO sales_payments (
            sales_order_id,
            payment_date,
            amount,
            payment_method,
            reference_number,
            notes,
            account_id,
            payment_proof,
            created_by,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $paymentStmt = $connect->prepare($paymentSql);
        if (!$paymentStmt) {
            throw new Exception('Error preparing payment insert: ' . $connect->error);
        }

        $paymentStmt->bind_param('isdsssisi', 
            $orderId,
            $paymentDate,
            $amount,
            $paymentMethod,
            $referenceNumber,
            $notes,
            $accountId,
            $paymentProof,
            $userId
        );

        if (!$paymentStmt->execute()) {
            throw new Exception('Error recording payment: ' . $paymentStmt->error);
        }
        $paymentId = $paymentStmt->insert_id;
        $paymentStmt->close();

        // Update order payment status and amounts
        $updateSql = "UPDATE sales_orders 
                    SET payment_status = ?,
                        paid_amount = ?,
                        balance = ?,
                        updated_at = NOW()
                    WHERE id = ?";

        $updateStmt = $connect->prepare($updateSql);
        if (!$updateStmt) {
            throw new Exception('Error preparing order update');
        }

        $updateStmt->bind_param('sddi', 
            $newPaymentStatus,
            $newPaidAmount,
            $newBalance,
            $orderId
        );

        if (!$updateStmt->execute()) {
            throw new Exception('Error updating order status: ' . $updateStmt->error);
        }
        $updateStmt->close();

        // Commit transaction
        $connect->commit();

        $response['success'] = true;
        $response['messages'][] = 'Payment recorded successfully';
        $response['payment'] = array(
            'id' => $paymentId,
            'amount' => $amount,
            'new_status' => $newPaymentStatus,
            'remaining_balance' => $newBalance
        );

    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($connect) && $connect->ping()) {
        $connect->rollback();
    }
    $response['success'] = false;
    $response['messages'][] = $e->getMessage();
} finally {
    if (isset($connect)) {
        $connect->close();
    }
}

// Ensure clean JSON output
echo json_encode($response, JSON_UNESCAPED_UNICODE); 