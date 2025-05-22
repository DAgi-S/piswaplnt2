<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

header('Content-Type: application/json');

if (!hasPermission('manage_accounts')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied'
    ]);
    exit();
}

if($_POST) {
    $response = array();
    
    try {
        if(empty($_POST['accountId']) || empty($_POST['accountOwner']) || 
           empty($_POST['accountPlatform']) || empty($_POST['accountCurrency'])) {
            throw new Exception("All fields are required");
        }

        $accountId = (int)$_POST['accountId'];
        $accountOwner = mysqli_real_escape_string($connect, trim($_POST['accountOwner']));
        $accountPlatform = mysqli_real_escape_string($connect, trim($_POST['accountPlatform']));
        $accountCurrency = mysqli_real_escape_string($connect, trim($_POST['accountCurrency']));

        // Check if account exists (excluding current account)
        $stmt = $connect->prepare("SELECT id FROM accounts WHERE account_owner = ? AND account_platform = ? AND id != ?");
        $stmt->bind_param("ssi", $accountOwner, $accountPlatform, $accountId);
        $stmt->execute();
        if($stmt->get_result()->num_rows > 0) {
            throw new Exception("Account already exists for this owner and platform");
        }

        // Update account
        $stmt = $connect->prepare("UPDATE accounts SET account_owner = ?, account_platform = ?, Currency = ? WHERE id = ?");
        $stmt->bind_param("sssi", $accountOwner, $accountPlatform, $accountCurrency, $accountId);
        
        if($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = "Account updated successfully";
        } else {
            throw new Exception("Error updating account: " . $stmt->error);
        }

    } catch(Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }

    echo json_encode($response);
} 