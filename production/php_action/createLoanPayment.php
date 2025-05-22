<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set header as JSON
header('Content-Type: application/json');

// Function to validate input
function validateInput($data) {
    $errors = array();
    
    if(empty($data['loan_id'])) $errors[] = "Loan ID is required";
    if(empty($data['payment_amount']) || !is_numeric($data['payment_amount'])) $errors[] = "Valid payment amount is required";
    if(empty($data['payment_date'])) $errors[] = "Payment date is required";
    if(empty($data['payment_method_id'])) $errors[] = "Payment method is required";
    if(empty($data['account_id'])) $errors[] = "Account is required";
    
    return $errors;
}

$response = array();

try {
    // Start transaction
    $connect->begin_transaction();
    
    // Validate input
    $validationErrors = validateInput($_POST);
    if(!empty($validationErrors)) {
        throw new Exception(implode(", ", $validationErrors));
    }
    
    // Sanitize and prepare input data
    $loan_id = intval($_POST['loan_id']);
    $payment_amount = floatval($_POST['payment_amount']);
    $payment_date = $_POST['payment_date'];
    $payment_method_id = intval($_POST['payment_method_id']);
    $account_id = intval($_POST['account_id']);
    $reference_number = $connect->real_escape_string($_POST['reference_number'] ?? '');
    $notes = $connect->real_escape_string($_POST['notes'] ?? '');
    $created_by = $_SESSION['userId'];
    
    // Check if loan exists and get current balance
    $loan_sql = "SELECT l.loan_amount, l.remaining_balance, l.loan_status, c.client_name 
                 FROM loans l 
                 JOIN clients c ON l.client_id = c.client_id 
                 WHERE l.loan_id = ?";
    $loan_stmt = $connect->prepare($loan_sql);
    $loan_stmt->bind_param("i", $loan_id);
    $loan_stmt->execute();
    $loan_result = $loan_stmt->get_result();
    
    if($loan_result->num_rows === 0) {
        throw new Exception("Invalid loan ID");
    }
    
    $loan_data = $loan_result->fetch_assoc();
    $remaining_balance = $loan_data['remaining_balance'];
    $client_name = $loan_data['client_name'];
    
    // Validate payment amount
    if($payment_amount > $remaining_balance) {
        throw new Exception("Payment amount cannot exceed remaining balance of " . number_format($remaining_balance, 2));
    }
    
    // Insert payment record
    $payment_sql = "INSERT INTO loan_payments (
        loan_id, 
        payment_amount, 
        payment_date, 
        payment_method_id, 
        account_id, 
        reference_number, 
        notes, 
        created_by, 
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $payment_stmt = $connect->prepare($payment_sql);
    $payment_stmt->bind_param("idsiissi", 
        $loan_id, 
        $payment_amount, 
        $payment_date, 
        $payment_method_id, 
        $account_id, 
        $reference_number, 
        $notes, 
        $created_by
    );
    $payment_stmt->execute();
    $payment_id = $payment_stmt->insert_id;
    
    // Update loan remaining balance
    $new_balance = $remaining_balance - $payment_amount;
    $loan_status = $new_balance <= 0 ? 'Paid' : 'Active';
    
    $update_loan_sql = "UPDATE loans SET 
        remaining_balance = ?, 
        loan_status = ?, 
        updated_at = NOW(), 
        updated_by = ? 
        WHERE loan_id = ?";
    
    $update_loan_stmt = $connect->prepare($update_loan_sql);
    $update_loan_stmt->bind_param("dsii", 
        $new_balance, 
        $loan_status, 
        $created_by, 
        $loan_id
    );
    $update_loan_stmt->execute();
    
    // Update account balance
    $update_account_sql = "UPDATE accounts SET 
        current_balance = current_balance + ?, 
        updated_at = NOW(), 
        updated_by = ? 
        WHERE account_id = ?";
    
    $update_account_stmt = $connect->prepare($update_account_sql);
    $update_account_stmt->bind_param("dii", 
        $payment_amount, 
        $created_by, 
        $account_id
    );
    $update_account_stmt->execute();
    
    // Add transaction record
    $transaction_sql = "INSERT INTO transactions (
        transaction_type,
        reference_id,
        account_id,
        amount,
        transaction_date,
        description,
        created_by,
        created_at
    ) VALUES ('loan_payment', ?, ?, ?, ?, ?, ?, NOW())";
    
    $description = "Loan payment received from " . $client_name;
    $transaction_stmt = $connect->prepare($transaction_sql);
    $transaction_stmt->bind_param("iidssi", 
        $payment_id, 
        $account_id, 
        $payment_amount, 
        $payment_date, 
        $description, 
        $created_by
    );
    $transaction_stmt->execute();
    
    // Add to changelog
    $log_action = "Created new loan payment";
    $log_data = json_encode(array(
        'payment_id' => $payment_id,
        'loan_id' => $loan_id,
        'amount' => $payment_amount,
        'client' => $client_name
    ));
    
    $log_sql = "INSERT INTO changelog (user_id, action, action_data, created_at) VALUES (?, ?, ?, NOW())";
    $log_stmt = $connect->prepare($log_sql);
    $log_stmt->bind_param("iss", $created_by, $log_action, $log_data);
    $log_stmt->execute();
    
    // Commit transaction
    $connect->commit();
    
    $response['success'] = true;
    $response['messages'] = "Payment successfully created";
    $response['payment_id'] = $payment_id;
    
} catch(Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
}

echo json_encode($response);
$connect->close(); 