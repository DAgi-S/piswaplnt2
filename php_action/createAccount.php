<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

header('Content-Type: application/json');

if (!hasPermission('manage_accounts')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied. Insufficient privileges.'
    ]);
    exit();
}

if ($_POST) {
    $response = array();

    try {
        // Validate input
        if (empty($_POST['accountOwner']) || empty($_POST['accountPlatform']) || empty($_POST['accountCurrency'])) {
            throw new Exception("Account owner, platform and currency are required");
        }

        $accountOwner = mysqli_real_escape_string($connect, trim($_POST['accountOwner']));
        $accountPlatform = mysqli_real_escape_string($connect, trim($_POST['accountPlatform']));
        $accountCurrency = mysqli_real_escape_string($connect, trim($_POST['accountCurrency']));

        // Check if account already exists
        $stmt = $connect->prepare("SELECT id FROM accounts WHERE account_owner = ? AND account_platform = ?");
        $stmt->bind_param("ss", $accountOwner, $accountPlatform);
        $stmt->execute();
        if($stmt->get_result()->num_rows > 0) {
            throw new Exception("Account already exists for this owner and platform");
        }

        // Create new account with correct column name 'satstus'
        $stmt = $connect->prepare("INSERT INTO accounts (account_owner, account_platform, Currency, number_of_transactions, satstus) VALUES (?, ?, ?, 0, 1)");
        $stmt->bind_param("sss", $accountOwner, $accountPlatform, $accountCurrency);
        
        if($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = "Account created successfully";
        } else {
            throw new Exception("Error creating account: " . $stmt->error);
        }

    } catch(Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }

    echo json_encode($response);
} 