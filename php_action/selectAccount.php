<?php
require_once 'core.php';
require_once 'db_connect.php';

if (!isset($_POST['account_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Account ID is required'
    ]);
    exit();
}

$accountId = $_POST['account_id'];

// Verify account exists
$sql = "SELECT * FROM accounts WHERE id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $accountId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Account not found'
    ]);
    exit();
}

// Set the active account in session
$_SESSION['active_account'] = $accountId;

echo json_encode([
    'success' => true,
    'message' => 'Account selected successfully'
]); 