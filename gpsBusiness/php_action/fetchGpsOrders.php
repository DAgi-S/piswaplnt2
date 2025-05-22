<?php
require_once '../../php_action/core.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize the response array
$output = array(
    'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => array(),
    'error' => null
);

try {
    // Debug database connection
    error_log('Checking database connection...');
    
    // Check if database connection exists and is valid
    if (!isset($connect)) {
        throw new Exception("Database connection variable not set");
    }
    
    if ($connect->connect_error) {
        throw new Exception("Database connection failed: " . $connect->connect_error);
    }

    // First, get total records for pagination
    $totalRecordsQuery = "SELECT COUNT(*) as total FROM gps_orders";
    $totalResult = $connect->query($totalRecordsQuery);
    $totalRow = $totalResult->fetch_assoc();
    $output['recordsTotal'] = $output['recordsFiltered'] = intval($totalRow['total']);

    // Query to fetch orders
    $sql = "SELECT * FROM gps_orders ORDER BY order_date DESC";

    error_log('Executing query: ' . $sql);
    
    $result = $connect->query($sql);

    if ($result) {
        error_log('Query executed successfully. Found ' . $result->num_rows . ' rows');

        while($row = $result->fetch_assoc()) {
            // Format credit status and amount
            $hasCredit = intval($row['has_credit']);
            $creditAmount = $hasCredit ? floatval($row['credit_amount']) : 0;

            $output['data'][] = array(
                'id' => intval($row['id']),
                'order_number' => $row['order_number'],
                'order_date' => date('d/m/Y', strtotime($row['order_date'])),
                'unit_price' => floatval($row['unit_price']),
                'quantity' => intval($row['quantity']),
                'total_price' => floatval($row['total_price']),
                'has_credit' => $hasCredit,
                'credit_amount' => $creditAmount
            );
        }

        error_log('Processed data: ' . json_encode($output['data']));
    } else {
        throw new Exception($connect->error);
    }
} catch (Exception $e) {
    $output['error'] = $e->getMessage();
    error_log('Error in fetchGpsOrders.php: ' . $e->getMessage());
} finally {
    if (isset($connect)) {
        $connect->close();
    }
}

// Set proper JSON header
header('Content-Type: application/json');
echo json_encode($output); 