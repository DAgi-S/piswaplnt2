<?php
require_once 'core.php';

$response = array(
    'success' => false,
    'messages' => array(),
    'data' => null
);

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];

        $sql = "SELECT * FROM gps_payment_followup WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $response['success'] = true;
            $response['data'] = array(
                'id' => $row['id'],
                'payment_date' => $row['payment_date'],
                'paid_by' => $row['paid_by'],
                'currency' => $row['currency'],
                'amount' => $row['amount'],
                'rate' => $row['rate'],
                'transfer_to' => $row['transfer_to'],
                'bank_platform_name' => $row['bank_platform_name'],
                'comment' => $row['comment'],
                'payment_image' => $row['payment_image']
            );
        } else {
            throw new Exception("Payment follow-up not found");
        }

        $stmt->close();

    } catch (Exception $e) {
        $response['messages'][] = $e->getMessage();
    }
} else {
    $response['messages'][] = "Invalid request";
}

$connect->close();

header('Content-Type: application/json');
echo json_encode($response); 