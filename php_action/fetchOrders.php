<?php 
require_once 'core.php';

$sql = "SELECT order_id, order_date, client_name, client_contact, grand_total, payment_status, fsnum 
        FROM orders 
        WHERE order_status = 1";

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
            'payment_status' => $row['payment_status'],
            'order_id' => $row['order_id']
        );
    }
}

$connect->close();
echo json_encode($output); 