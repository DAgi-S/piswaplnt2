<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        'success' => false,
        'messages' => 'Session expired. Please log in again.'
    ]);
    exit();
}

require_once '../../php_action/core.php';

$response = array(
    'success' => false,
    'messages' => array(),
    'data' => array()
);

try {
    // Fetch orders that have payments less than their total price
    $sql = "SELECT o.id, o.order_number, o.total_price, o.order_date,
            COALESCE(SUM(p.paid_amount), 0) as total_paid
            FROM gps_orders o
            LEFT JOIN gps_payments p ON o.id = p.gps_order_id
            GROUP BY o.id
            HAVING o.total_price > COALESCE(SUM(p.paid_amount), 0)
            OR COALESCE(SUM(p.paid_amount), 0) IS NULL
            ORDER BY o.order_date DESC";

    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Error fetching orders: " . $connect->error);
    }

    while ($row = $result->fetch_assoc()) {
        $remaining = $row['total_price'] - $row['total_paid'];
        $response['data'][] = array(
            'id' => $row['id'],
            'text' => $row['order_number'] . ' (Remaining: ETB ' . number_format($remaining, 2) . ')',
            'remaining' => $remaining
        );
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
} finally {
    if (isset($connect)) {
        $connect->close();
    }
}

header('Content-Type: application/json');
echo json_encode($response);