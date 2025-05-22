<?php
require_once '../../php_action/core.php';

// Initialize response array


$sql = "SELECT 
            p.*,
            o.order_number,
            i.name as investor_name,
            i.balance as current_balance,
            COALESCE(ba.balance, i.balance) as balance_at_payment
        FROM gps_payments p
        LEFT JOIN gps_orders o ON p.gps_order_id = o.id
        LEFT JOIN gps_investors i ON p.paid_by = i.id
        LEFT JOIN gps_balance_accounts ba ON p.id = ba.transaction_id 
            AND ba.transaction_type = 'payment'
        ORDER BY p.payment_date DESC";

$result = $connect->query($sql);

$output = array('data' => array());

if($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $output['data'][] = array(
            'id' => $row['id'],
            'payment_number' => $row['payment_number'],
            'order_number' => $row['order_number'] ?? 'N/A',
            'payment_date' => $row['payment_date'],
            'payment_type' => $row['payment_type'],
            'investor_name' => $row['investor_name'],
            'paid_amount' => $row['paid_amount'],
            'currency' => $row['currency'],
            'rate' => $row['rate'],
            'bank' => $row['bank'],
            'balance' => $row['balance_at_payment'] ?? $row['current_balance']
        );
    }
}

$connect->close();
echo json_encode($output);
?> 