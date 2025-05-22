<?php
require_once 'core.php';

$sql = "SELECT id, order_number, order_date FROM gps_orders ORDER BY order_date DESC";
$result = $connect->query($sql);

$output = array();

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $output[] = $row;
    }
}

$connect->close();
echo json_encode($output); 