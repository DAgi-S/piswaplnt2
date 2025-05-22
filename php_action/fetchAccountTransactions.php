<?php
ob_start();
require_once 'core.php';
require_once 'db_connect.php';

// Clean any output buffered so far
ob_clean();

header('Content-Type: application/json');

// Check if user has permission
if(!hasPermission('view_transactions')) {
    echo json_encode(array('success' => false, 'messages' => 'Access Denied'));
    exit();
}

// Check if account_id is provided
if(!isset($_GET['account_id'])) {
    echo json_encode(array('success' => false, 'messages' => 'Account ID is required'));
    exit();
}

try {
    $accountId = $_GET['account_id'];

    // Fetch transactions for the account
    $sql = "SELECT 
                d.*,
                a.account_owner,
                a.account_platform as platform,
                t.name as type_name
            FROM digitalswap d
            JOIN accounts a ON d.account_id = a.id
            JOIN transaction_types t ON d.type_id = t.id
            WHERE d.account_id = ?
            ORDER BY d.transaction_date DESC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result) {
        $data = array();
        while($row = $result->fetch_assoc()) {
            $data[] = array(
                'id' => $row['id'],
                'transaction_date' => date('Y-m-d H:i:s', strtotime($row['transaction_date'])),
                'account_owner' => $row['account_owner'],
                'platform' => $row['platform'],
                'type' => $row['type_name'],
                'amount' => $row['amount'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            );
        }
        
        echo json_encode(array(
            'success' => true,
            'data' => $data
        ));
    } else {
        throw new Exception($connect->error);
    }
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'messages' => 'Error fetching transactions: ' . $e->getMessage()
    ));
}

$stmt->close();
$connect->close(); 