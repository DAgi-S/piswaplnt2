<?php
// Prevent unwanted output
ob_start();

require_once 'core.php';
require_once 'db_connect.php';
require_once 'includes/LogManager.php';

// Clear any previous output
ob_clean();

// Set proper content type
header('Content-Type: application/json');

// Default response
$response = array(
    'success' => false,
    'messages' => array(),
    'payment_id' => null
);

try {
    // Check if user session exists
    if (!isset($_SESSION['userId'])) {
        throw new Exception("User session not found.");
    }

    // Initialize log manager
    $logManager = new LogManager($connect, $_SESSION['userId']);

    // Validate required fields
    $required_fields = array(
        'order_id' => 'Order',
        'payment_date' => 'Payment Date',
        'amount' => 'Amount',
        'payment_method' => 'Payment Method',
        'account_id' => 'Account'
    );

    foreach ($required_fields as $field => $label) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception($label . " is required.");
        }
    }

    // Sanitize and validate input
    $orderId = intval($_POST['order_id']);
    $paymentDate = date('Y-m-d', strtotime($_POST['payment_date']));
    $amount = floatval($_POST['amount']);
    $paymentMethod = $connect->real_escape_string($_POST['payment_method']);
    $accountId = intval($_POST['account_id']);
    $referenceNumber = isset($_POST['reference_number']) ? $connect->real_escape_string($_POST['reference_number']) : null;
    $notes = isset($_POST['notes']) ? $connect->real_escape_string($_POST['notes']) : null;
    $createdBy = $_SESSION['userId'];

    // Validate amount
    if ($amount <= 0) {
        throw new Exception("Amount must be greater than zero.");
    }

    // Start transaction
    $connect->begin_transaction();

    // Check if order exists and get its balance
    $orderQuery = "SELECT total_amount, paid_amount, (total_amount - paid_amount) as balance 
                  FROM sales_orders 
                  WHERE id = ? AND order_status != 'cancelled'";
    $stmt = $connect->prepare($orderQuery);
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $orderResult = $stmt->get_result();

    if ($orderResult->num_rows === 0) {
        throw new Exception("Invalid order selected.");
    }

    $orderData = $orderResult->fetch_assoc();
    $balance = floatval($orderData['balance']);

    // Validate payment amount against order balance
    if ($amount > $balance) {
        throw new Exception("Payment amount cannot exceed order balance of " . number_format($balance, 2));
    }

    // Handle file upload if provided
    $paymentProof = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['size'] > 0) {
        $allowedTypes = array('image/jpeg', 'image/png', 'image/gif', 'application/pdf');
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['payment_proof']['type'], $allowedTypes)) {
            throw new Exception("Invalid file type. Only JPEG, PNG, GIF, and PDF files are allowed.");
        }

        if ($_FILES['payment_proof']['size'] > $maxSize) {
            throw new Exception("File size too large. Maximum size is 5MB.");
        }

        // Set the correct upload directory path
        $uploadDir = '../../uploads/payment_proofs/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = 'payment_' . uniqid() . '_' . basename($_FILES['payment_proof']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $targetPath)) {
            // Store the relative path in database
            $paymentProof = 'uploads/payment_proofs/' . $fileName;
        } else {
            throw new Exception("Failed to upload payment proof.");
        }
    }

    // Insert payment record
    $sql = "INSERT INTO sales_payments (
                sales_order_id, 
                payment_date, 
                amount, 
                payment_method, 
                reference_number, 
                payment_proof, 
                notes, 
                account_id, 
                created_by, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param(
        'isdssssis',
        $orderId,
        $paymentDate,
        $amount,
        $paymentMethod,
        $referenceNumber,
        $paymentProof,
        $notes,
        $accountId,
        $createdBy
    );

    if (!$stmt->execute()) {
        throw new Exception("Error creating payment: " . $stmt->error);
    }

    $paymentId = $connect->insert_id;

    // Update order paid amount and status
    $newPaidAmount = floatval($orderData['paid_amount']) + $amount;
    $paymentStatus = ($newPaidAmount >= $orderData['total_amount']) ? 'paid' : 'partial';

    $updateOrderSql = "UPDATE sales_orders 
                      SET paid_amount = ?, 
                          payment_status = ?,
                          updated_at = CURRENT_TIMESTAMP 
                      WHERE id = ?";
    
    $stmt = $connect->prepare($updateOrderSql);
    $stmt->bind_param('dsi', $newPaidAmount, $paymentStatus, $orderId);
    
    if (!$stmt->execute()) {
        throw new Exception("Error updating order: " . $stmt->error);
    }

    // Increment account transaction count
    $updateAccountSql = "UPDATE accounts 
                        SET number_of_transactions = number_of_transactions + 1,
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?";
    
    $stmt = $connect->prepare($updateAccountSql);
    $stmt->bind_param('i', $accountId);
    
    if (!$stmt->execute()) {
        throw new Exception("Error updating account: " . $stmt->error);
    }

    // Log the payment creation
    $paymentData = array(
        'payment_id' => $paymentId,
        'order_id' => $orderId,
        'payment_date' => $paymentDate,
        'amount' => $amount,
        'payment_method' => $paymentMethod,
        'account_id' => $accountId,
        'reference_number' => $referenceNumber,
        'payment_proof' => $paymentProof,
        'notes' => $notes
    );
    
    $logManager->logPaymentCreation($paymentId, $paymentData);

    // Commit transaction
    $connect->commit();

    // Set success response
    $response['success'] = true;
    $response['messages'][] = "Payment created successfully.";
    $response['payment_id'] = $paymentId;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($connect) && $connect->ping()) {
        $connect->rollback();
    }
    
    // Log the error
    error_log("Error in createSalesPayment.php: " . $e->getMessage());
    
    // Set error response
    $response['messages'][] = $e->getMessage();
}

// Close database connection
if (isset($connect)) {
    $connect->close();
}

// Send response
echo json_encode($response);
exit();
?> 