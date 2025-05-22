<?php 	

require_once 'core.php';

// Check if user has permission to view orders
if (!hasPermission('order.view')) {
    $output = array('data' => array());
    $output['error'] = true;
    $output['messages'] = "You don't have permission to view orders";
    echo json_encode($output);
    exit();
}

// Fetch active orders
$sql = "SELECT order_id, order_date, fsnum, client_name, client_contact, payment_status, grand_total 
        FROM orders 
        WHERE order_status = 1 
        ORDER BY order_id DESC";

$result = $connect->query($sql);

$output = array('data' => array());

if($result->num_rows > 0) { 
    while($row = $result->fetch_array()) {
        $output['data'][] = array(
            'order_id' => $row['order_id'],
            'order_date' => date('d/m/Y', strtotime($row['order_date'])),
            'fsnum' => $row['fsnum'],
            'client_name' => $row['client_name'],
            'client_contact' => $row['client_contact'],
            'grand_total' => floatval($row['grand_total']),
            'payment_status' => $row['payment_status']
        );
    }
} 

$connect->close();

header('Content-Type: application/json');
echo json_encode($output);