<?php
require_once 'db_connect.php';

// Prepare the response array
$output = array('data' => array());

$sql = "SELECT po.*, CONCAT(u.username) as created_by 
        FROM production_orders po 
        LEFT JOIN users u ON po.created_by = u.user_id 
        ORDER BY po.production_order_id DESC";

$result = $connect->query($sql);

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $output['data'][] = array(
            'production_order_id' => $row['production_order_id'],
            'order_number' => $row['order_number'],
            'status' => $row['status'],
            'target_quantity' => $row['target_quantity'],
            'completed_quantity' => $row['completed_quantity'],
            'start_date' => $row['start_date'],
            'completion_date' => $row['completion_date'],
            'created_by' => $row['created_by']
        );
    }
}

// Close database connection
$connect->close();

echo json_encode($output); 