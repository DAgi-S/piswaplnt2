<?php
// Prevent any unwanted output
ob_start();

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
ob_clean();

// Set proper content type
header('Content-Type: application/json');

// Default response
$response = array(
    'success' => false,
    'data' => array(),
    'messages' => array()
);

try {
    // Check if user has permission to view sales orders
    if (!isset($_SESSION['userId'])) {
        throw new Exception("User session not found.");
    }

    // Query to fetch orders that have unpaid balance
    $sql = "SELECT 
                so.id,
                so.order_number,
                so.transaction_id,
                c.company_name as client_name,
                so.total_amount,
                so.paid_amount,
                (so.total_amount - so.paid_amount) as balance,
                so.order_date,
                so.order_status,
                so.payment_status
            FROM sales_orders so
            JOIN clients c ON so.client_id = c.id
            WHERE so.payment_status IN ('unpaid', 'partial')
            AND so.order_status != 'cancelled'
            AND (so.total_amount - so.paid_amount) > 0
            ORDER BY so.order_date DESC";

    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Error executing query: " . $connect->error);
    }

    $orders = array();
    while ($row = $result->fetch_assoc()) {
        // Format the balance for display
        $balance = floatval($row['balance']);
        
        // Only include orders with positive balance
        if ($balance > 0) {
            $orders[] = array(
                'id' => $row['id'],
                'order_number' => $row['order_number'],
                'transaction_id' => $row['transaction_id'],
                'client_name' => $row['client_name'],
                'total_amount' => number_format(floatval($row['total_amount']), 2, '.', ''),
                'paid_amount' => number_format(floatval($row['paid_amount']), 2, '.', ''),
                'balance' => number_format($balance, 2, '.', ''),
                'order_date' => date('Y-m-d', strtotime($row['order_date'])),
                'order_status' => $row['order_status'],
                'payment_status' => $row['payment_status']
            );
        }
    }

    // Set success response
    $response['success'] = true;
    $response['data'] = $orders;
    $response['count'] = count($orders);

} catch (Exception $e) {
    // Log the error
    error_log("Error in fetchOrdersForPayment.php: " . $e->getMessage());
    
    // Set error response
    $response['messages'][] = $e->getMessage();
}

// Close database connection
$connect->close();

// Send response
echo json_encode($response);
exit(); 