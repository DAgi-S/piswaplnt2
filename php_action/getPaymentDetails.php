<?php 
require_once 'core.php';

if($_POST) {
    $paymentId = $_POST['paymentId'];
    
    $sql = "SELECT d.*, a.name 
            FROM digitalswap d 
            LEFT JOIN accounts a ON d.account_id = a.id 
            WHERE d.id = $paymentId";
            
    $result = $connect->query($sql);
    
    if($result->num_rows > 0) {
        $row = $result->fetch_array();
        $data = array(
            'id' => $row['id'],
            'name' => $row['name'],
            'type' => ucfirst($row['type']),
            'platform' => $row['platform'],
            'amount' => $row['amount'],
            'transaction_date' => date('Y-m-d H:i', strtotime($row['transaction_date'])),
            'comment' => $row['comment']
        );
        echo json_encode($data);
    }

    $connect->close();
} 