<?php
require_once 'core.php';

// Prevent any output before our JSON response
ob_start();

try {
    // Check if all required fields are set
    if(
        isset($_POST['sales_order_id']) && !empty($_POST['sales_order_id']) &&
        isset($_POST['account_id']) && !empty($_POST['account_id']) &&
        isset($_POST['payment_date']) && !empty($_POST['payment_date']) &&
        isset($_POST['payment_method']) && !empty($_POST['payment_method']) &&
        isset($_POST['amount']) && !empty($_POST['amount'])
    ) {
        // Get values from form
        $salesOrderId = $_POST['sales_order_id'];
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
        
        // Start transaction
        $connect->begin_transaction();
        
        // 1. Add payment record - match the sales_payments table structure from the screenshot
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
        
        $stmt->bind_param("iissdsssi", $salesOrderId, $accountId, $paymentDate, $paymentMethod, $amount, $referenceNumber, $notes, $paymentProof, $userId);
        $stmt->execute();
        
        // 2. Get current paid amount and total from sales_orders
        $query = "SELECT paid_amount, total_amount FROM sales_orders WHERE id = ?";
        $stmt = $connect->prepare($query);
        $stmt->bind_param("i", $salesOrderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $saleData = $result->fetch_assoc();
        
        // 3. Calculate new paid amount and determine payment status
        $newPaidAmount = $saleData['paid_amount'] + $amount;
        $paymentStatus = 'unpaid';
        
        if($newPaidAmount >= $saleData['total_amount']) {
            $paymentStatus = 'paid';
        } else if($newPaidAmount > 0) {
            $paymentStatus = 'partial';
        }
        
        // 4. Update sales_orders table
        $query = "UPDATE sales_orders SET 
                  paid_amount = ?, 
                  payment_status = ?,
                  updated_at = NOW()
                  WHERE id = ?";
        
        $stmt = $connect->prepare($query);
        $stmt->bind_param("dsi", $newPaidAmount, $paymentStatus, $salesOrderId);
        $stmt->execute();
        
        // Commit transaction
        $connect->commit();
        
        // Success response
        $response = array(
            'success' => true,
            'message' => 'Payment added successfully',
            'payment_status' => $paymentStatus,
            'paid_amount' => $newPaidAmount
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
    
    // Set proper JSON header
    header('Content-Type: application/json');
    
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
    
    // Set proper JSON header
    header('Content-Type: application/json');
    
    // Return error response
    echo json_encode(array(
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ));
    exit;
} 