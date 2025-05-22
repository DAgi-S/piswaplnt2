<?php
require_once 'core.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

$sql = "SELECT 
            a.id,
            a.account_owner,
            a.account_platform,
            a.currency,
            COALESCE(SUM(CASE WHEN d.type = 'deposit' THEN d.amount ELSE -d.amount END), 0) as current_balance,
            COUNT(d.id) as number_of_transactions,
            a.created_at
        FROM accounts a
        LEFT JOIN digitalswap d ON a.id = d.account_id
        GROUP BY a.id, a.account_owner, a.account_platform, a.currency, a.created_at
        ORDER BY a.created_at DESC";

$result = $connect->query($sql);

if ($result) {
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = array(
            'id' => $row['id'],
            'account_owner' => $row['account_owner'],
            'account_platform' => $row['account_platform'],
            'currency' => $row['currency'],
            'current_balance' => number_format($row['current_balance'], 2),
            'number_of_transactions' => $row['number_of_transactions'],
            'created_at' => date('Y-m-d H:i:s', strtotime($row['created_at']))
        );
    }
    
    echo json_encode(array(
        'success' => true,
        'data' => $data
    ));
} else {
    echo json_encode(array(
        'success' => false,
        'messages' => 'Error fetching accounts: ' . $connect->error
    ));
}

$connect->close(); 