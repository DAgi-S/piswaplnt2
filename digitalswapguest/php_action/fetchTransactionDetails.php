<?php
require_once 'php_action/core.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID is required']);
    exit();
}

$transactionId = $_GET['id'];

// Fetch transaction details with joins to get all related information
$sql = "SELECT 
            t.*, 
            a.account_owner,
            a.account_platform as platform,
            a.Currency as currency,
            tt.name as type_name,
            tt.description as type_description
        FROM digitalswap t
        LEFT JOIN accounts a ON t.account_id = a.id
        LEFT JOIN transaction_types tt ON t.type_id = tt.id
        WHERE t.id = ?";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $transactionId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $transaction = $result->fetch_assoc();
    
    // Format the response data
    $response = [
        'success' => true,
        'data' => [
            'id' => $transaction['id'],
            'transaction_date' => $transaction['transaction_date'],
            'account_owner' => $transaction['account_owner'],
            'platform' => $transaction['platform'],
            'type' => $transaction['type_name'],
            'type_description' => $transaction['type_description'],
            'amount' => $transaction['amount'],
            'currency' => $transaction['currency'],
            'status' => $transaction['status'],
            'comment' => $transaction['comment'],
            'has_receipt' => !empty($transaction['image']),
            'receipt_url' => !empty($transaction['image']) ? '../uploads/digitalswap/' . $transaction['image'] : null
        ]
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Transaction not found'
    ];
}

// Close statement
$stmt->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response); 