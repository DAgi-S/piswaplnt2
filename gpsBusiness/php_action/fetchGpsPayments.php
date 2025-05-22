<?php
require_once '../../php_action/core.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize the response array
$output = array(
    'draw' => 1,
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
    $totalRecordsQuery = "SELECT COUNT(*) as total FROM gps_payments";
    $totalResult = $connect->query($totalRecordsQuery);
    $totalRow = $totalResult->fetch_assoc();
    $output['recordsTotal'] = $output['recordsFiltered'] = intval($totalRow['total']);

    // Query to fetch payments with order details
    $sql = "SELECT p.*, o.order_number 
            FROM gps_payments p
            LEFT JOIN gps_orders o ON p.gps_order_id = o.id
            ORDER BY p.payment_date DESC";

    error_log('Executing query: ' . $sql);
    
    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        error_log('Query executed successfully. Found ' . $result->num_rows . ' rows');

        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Format the amount with proper currency conversion if needed
                $amount = $row['currency'] === 'USD' 
                    ? $row['paid_amount'] * $row['rate']
                    : $row['paid_amount'];

                $output['data'][] = array(
                    'id' => $row['id'],
                    'payment_number' => $row['payment_number'],
                    'order_number' => $row['order_number'],
                    'payment_date' => $row['payment_date'],
                    'payment_type' => $row['payment_type'],
                    'paid_by' => $row['paid_by'],
                    'paid_amount' => number_format($row['paid_amount'], 2),
                    'currency' => $row['currency'],
                    'rate' => $row['rate'],
                    'bank' => $row['bank'],
                    'deposited_to' => $row['deposited_to'],
                    'created_at' => $row['created_at'],
                    'amount_etb' => number_format($amount, 2)
                );
            }
        }

        error_log('Processed data: ' . json_encode($output['data']));
    } else {
        throw new Exception($connect->error);
    }
} catch (Exception $e) {
    $output['error'] = $e->getMessage();
    error_log('Error in fetchGpsPayments.php: ' . $e->getMessage());
} finally {
    if (isset($connect)) {
        $connect->close();
    }
}

// Function to generate action buttons HTML
function generateActionButtons($id) {
    return '<div class="btn-group">
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            Action <span class="caret"></span>
        </button>
        <ul class="dropdown-menu">
            <li><a href="#" class="editPayment" data-id="'.$id.'">
                <i class="fas fa-edit"></i> Edit</a></li>
            <li><a href="#" class="removePayment" data-id="'.$id.'">
                <i class="fas fa-trash"></i> Remove</a></li>
        </ul>
    </div>';
}

// Set proper JSON header
header('Content-Type: application/json');
echo json_encode($output); 