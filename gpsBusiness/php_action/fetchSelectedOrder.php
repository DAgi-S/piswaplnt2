<?php
require_once '../../php_action/core.php';

$response = array(
    'success' => false,
    'messages' => array(),
    'data' => null
);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['orderId'])) {
    $orderId = (int)$_POST['orderId'];

    $sql = "SELECT * FROM gps_orders WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $orderId);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $response['success'] = true;
            $response['data'] = array(
                'id' => $row['id'],
                'order_number' => $row['order_number'],
                'order_date' => date('Y-m-d', strtotime($row['order_date'])),
                'unit_price' => $row['unit_price'],
                'quantity' => $row['quantity'],
                'total_price' => $row['total_price'],
                'has_credit' => $row['has_credit'],
                'credit_amount' => $row['credit_amount']
            );
        } else {
            $response['messages'] = 'Order not found.';
        }
    } else {
        $response['messages'] = 'Error fetching order: ' . $stmt->error;
    }

    $stmt->close();
}

$connect->close();

header('Content-Type: application/json');
echo json_encode($response); 