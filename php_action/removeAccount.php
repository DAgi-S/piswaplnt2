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
        $accountId = (int)$_POST['id'];

        // Begin transaction
        $connect->begin_transaction();

        // Check if account has transactions
        $stmt = $connect->prepare("SELECT COUNT(*) as count FROM digitalswap WHERE account_id = ?");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if($result['count'] > 0) {
            throw new Exception("Cannot delete account with existing transactions");
        }

        // Delete account
        $stmt = $connect->prepare("DELETE FROM accounts WHERE id = ?");
        $stmt->bind_param("i", $accountId);
        
        if($stmt->execute()) {
            $connect->commit();
            $response['success'] = true;
            $response['messages'] = "Account deleted successfully";
        } else {
            throw new Exception("Error deleting account: " . $stmt->error);
        }

    } catch(Exception $e) {
        $connect->rollback();
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }

    echo json_encode($response);
} 