<?php
require_once 'core.php';
require_once 'db_connect.php';

// Prevent any output before our JSON response
ob_start();

// Set proper headers
header('Content-Type: application/json');

try {
    // Check if all required fields are set
    if(
        isset($_POST['sale_id']) && !empty($_POST['sale_id']) &&
        isset($_POST['account_id']) && !empty($_POST['account_id']) &&
        isset($_POST['payment_date']) && !empty($_POST['payment_date']) &&
        isset($_POST['payment_method']) && !empty($_POST['payment_method']) &&
        isset($_POST['amount']) && !empty($_POST['amount'])
    ) {
        // Get values from form
        $saleId = $_POST['sale_id'];
        $accountId = $_POST['account_id'];
        $paymentDate = $_POST['payment_date'];
        $paymentMethod = $_POST['payment_method'];
        $amount = $_POST['amount'];
        $referenceNumber = isset($_POST['reference_number']) ? $_POST['reference_number'] : '';
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        // Upload payment proof if provided
        $paymentProof = '';
        if(isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == 0) {
            $uploadDir = '../assets/payment_proofs/';
            
            // Create directory if it doesn't exist
            if(!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileName = 'payment_' . uniqid() . '_' . $_FILES['payment_proof']['name'];
            $uploadPath = $uploadDir . $fileName;
            
            // Move uploaded file
            if(move_uploaded_file($_FILES['payment_proof']['tmp_name'], $uploadPath)) {
                $paymentProof = 'assets/payment_proofs/' . $fileName;
            }
        }
        
        // Get current sale information to validate amount
        $query = "SELECT paid_amount, total_amount FROM sales_orders WHERE id = ?";
        $stmt = $connect->prepare($query);
        $stmt->bind_param("i", $saleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $saleData = $result->fetch_assoc();
        
        // Validate if sale exists
        if(!$saleData) {
            throw new Exception('Sale not found');
        }
        
        // Calculate new paid amount and check if amount is valid
        $currentPaidAmount = floatval($saleData['paid_amount']);
        $totalAmount = floatval($saleData['total_amount']);
        $balance = $totalAmount - $currentPaidAmount;
        
        // Check if payment amount is valid
        if($amount <= 0) {
            throw new Exception('Payment amount must be greater than zero');
        }
        
        if($amount > $balance) {
            throw new Exception('Payment amount exceeds the remaining balance of ' . number_format($balance, 2) . '. Please enter a valid amount.');
        }
        
        // Start transaction
        $connect->begin_transaction();
        
        // 1. Add payment record
        $query = "INSERT INTO sales_payments (
                    sales_order_id, 
                    account_id, 
                    payment_date, 
                    payment_method, 
                    amount, 
                    reference_number, 
                    notes, 
                    payment_proof,
                    status,
                    created_by,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";
        
        $stmt = $connect->prepare($query);
        $userId = $_SESSION['userId'];
        
        $stmt->bind_param("iissdsssi", $saleId, $accountId, $paymentDate, $paymentMethod, $amount, $referenceNumber, $notes, $paymentProof, $userId);
        $stmt->execute();
        $paymentId = $stmt->insert_id;
        
        // 2. Calculate new paid amount and determine payment status
        $newPaidAmount = $currentPaidAmount + $amount;
        $newBalance = $totalAmount - $newPaidAmount;
        $paymentStatus = 'unpaid';
        
        if($newPaidAmount >= $totalAmount) {
            $paymentStatus = 'paid';
        } else if($newPaidAmount > 0) {
            $paymentStatus = 'partial';
        }
        
        // 3. Update sales_orders table
        $query = "UPDATE sales_orders SET 
                  paid_amount = ?, 
                  payment_status = ?,
                  balance = ?,
                  updated_at = NOW()
                  WHERE id = ?";
        
        $stmt = $connect->prepare($query);
        $stmt->bind_param("dsdi", $newPaidAmount, $paymentStatus, $newBalance, $saleId);
        $stmt->execute();
        
        // Commit transaction
        $connect->commit();
        
        // Success response
        $response = array(
            'success' => true,
            'message' => 'Payment added successfully',
            'payment_id' => $paymentId,
            'payment_status' => $paymentStatus,
            'paid_amount' => $newPaidAmount,
            'balance' => $newBalance
        );
    } else {
        // Missing required fields
        $response = array(
            'success' => false,
            'message' => 'Please fill all required fields'
        );
    }
    
    // Clear any previous output
    ob_clean();
    
    // Output the JSON response
    echo json_encode($response);
    exit;
    
} catch(Exception $e) {
    // Rollback transaction on error
    if(isset($connect) && $connect->ping()) {
        $connect->rollback();
    }
    
    // Clear any previous output
    ob_clean();
    
    // Return error response
    echo json_encode(array(
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ));
    exit;
}
?> 