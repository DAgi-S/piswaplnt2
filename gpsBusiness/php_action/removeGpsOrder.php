<?php
require_once '../../php_action/core.php';

$response = array(
    'success' => false,
    'messages' => array()
);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['orderId'])) {
    $orderId = (int)$_POST['orderId'];

    if ($orderId <= 0) {
        $response['messages'] = 'Invalid order ID.';
    } else {
        $sql = "DELETE FROM gps_orders WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('i', $orderId);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['messages'] = 'Order deleted successfully.';
            } else {
                $response['messages'] = 'Order not found.';
            }
        } else {
            $response['messages'] = 'Error deleting order: ' . $stmt->error;
        }

        $stmt->close();
    }
}

$connect->close();

header('Content-Type: application/json');
echo json_encode($response); 