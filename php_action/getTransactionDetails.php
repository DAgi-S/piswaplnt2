<?php
require_once 'core.php';
require_once 'db_connect.php';

// Check if transaction_id is provided
if(!isset($_GET['transaction_id'])) {
    echo json_encode(array('success' => false, 'messages' => 'Transaction ID is required'));
    exit();
}

$transactionId = $_GET['transaction_id'];

// Fetch transaction details
$sql = "SELECT 
            t.*,
            a.account_name,
            u.username as created_by_user
        FROM account_transactions t
        JOIN accounts a ON t.account_id = a.account_id
        LEFT JOIN users u ON t.created_by = u.user_id
        WHERE t.transaction_id = ?";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $transactionId);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $data = array(
        'transaction_id' => $row['transaction_id'],
        'account_name' => $row['account_name'],
        'date' => date('Y-m-d H:i:s', strtotime($row['date'])),
        'type' => $row['type'],
        'amount' => $row['amount'],
        'balance_after' => $row['balance_after'],
        'description' => $row['description'],
        'status' => $row['status'],
        'created_by' => $row['created_by_user'],
        'created_at' => $row['created_at'],
        'reference_no' => $row['reference_no'],
        'notes' => $row['notes']
    );
    
    echo json_encode(array(
        'success' => true,
        'data' => $data
    ));
} else {
    echo json_encode(array(
        'success' => false,
        'messages' => 'Transaction not found'
    ));
}

$stmt->close(); 