<?php
require_once 'core.php';

// Set proper JSON header
header('Content-Type: application/json');

try {
    // Simple direct query to check accounts
    $query = "SELECT * FROM accounts WHERE status = 1";
    $result = $connect->query($query);
    
    if ($result) {
        $accounts = array();
        while ($row = $result->fetch_assoc()) {
            $accounts[] = array(
                'id' => $row['id'],
                'name' => isset($row['account_platform']) ? $row['account_platform'] : 'Unknown',
                'owner' => isset($row['account_owner']) ? $row['account_owner'] : 'Unknown',
                'currency' => isset($row['currency']) ? $row['currency'] : 'USD'
            );
        }
        
        // Response
        echo json_encode(array(
            'success' => true,
            'data' => $accounts,
            'count' => count($accounts)
        ));
    } else {
        throw new Exception("Error executing query: " . $connect->error);
    }
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
} 