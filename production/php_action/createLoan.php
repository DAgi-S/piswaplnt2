<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $borrowerType = $_POST['borrowerType'];
    $borrowerId = $_POST['borrowerId'];
    $loanAmount = $_POST['loanAmount'];
    $interestRate = $_POST['interestRate'];
    $loanDate = $_POST['loanDate'];
    $dueDate = $_POST['dueDate'];
    $description = $_POST['description'];
    $accountId = $_POST['accountId'];
    $paymentMethod = $_POST['paymentMethod'];
    $reference = $_POST['reference'];
    
    $sql = "INSERT INTO loans (borrower_type, borrower_id, loan_amount, interest_rate, loan_date, due_date, description, account_id, payment_method, reference_no, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("siddsssissi", $borrowerType, $borrowerId, $loanAmount, $interestRate, $loanDate, $dueDate, $description, $accountId, $paymentMethod, $reference, $_SESSION['userId']);
    
    if($stmt->execute()) {
        $loan_id = $connect->insert_id;
        
        // Update account balance
        $updateAccount = "UPDATE accounts SET balance = balance - ? WHERE account_id = ?";
        $stmt2 = $connect->prepare($updateAccount);
        $stmt2->bind_param("di", $loanAmount, $accountId);
        
        if($stmt2->execute()) {
            // Create transaction record
            $transactionSql = "INSERT INTO transactions (transaction_type, reference_id, amount, transaction_date, description, account_id, created_by) 
                              VALUES ('loan_disbursement', ?, ?, ?, ?, ?, ?)";
            $stmt3 = $connect->prepare($transactionSql);
            $desc = "Loan disbursement - Loan #" . $loan_id;
            $stmt3->bind_param("idssii", $loan_id, $loanAmount, $loanDate, $desc, $accountId, $_SESSION['userId']);
            
            if($stmt3->execute()) {
                $valid['success'] = true;
                $valid['messages'] = "Loan created successfully";
            } else {
                $valid['success'] = false;
                $valid['messages'] = "Error while creating transaction record";
            }
        } else {
            $valid['success'] = false;
            $valid['messages'] = "Error while updating account balance";
        }
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while creating loan";
    }
    
    $connect->close();
    echo json_encode($valid);
} 