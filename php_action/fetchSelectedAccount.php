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
    try {
        $accountId = (int)$_POST['id'];
        
        $stmt = $connect->prepare("SELECT * FROM accounts WHERE id = ?");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $account = $result->fetch_assoc();
            $account['success'] = true;
            echo json_encode($account);
        } else {
            throw new Exception("Account not found");
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'messages' => $e->getMessage()
        ]);
    }
} 