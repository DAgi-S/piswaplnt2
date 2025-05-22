<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log access to this script
error_log("fetchSaleData.php accessed at " . date('Y-m-d H:i:s'));

// Include database connection
require_once '../../php_action/core.php';

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Log session info
error_log("Session info - userId: " . (isset($_SESSION['userId']) ? $_SESSION['userId'] : 'not set'));

$output = array(
    'data' => array()
);

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        throw new Exception("Unauthorized access");
    }

    // Check database connection
    if (!isset($connect)) {
        throw new Exception("Database connection variable not set");
    }

    if ($connect->connect_error) {
        throw new Exception("Database connection failed: " . $connect->connect_error);
    }

    // Test database connection
    if (!$connect->ping()) {
        throw new Exception("Database connection lost");
    }

    // Log database connection info
    error_log("Database connection successful. Host info: " . $connect->host_info);

    // Fetch sales data with all columns
    $sql = "SELECT 
            id,
            buyer_name,
            contact,
            sales_type,
            quantity,
            unit_price,
            total,
            currency,
            rate,
            sale_date,
            created_at
            FROM gps_sales 
            ORDER BY created_at DESC";

    error_log("Executing query: " . $sql);

    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Error executing query: " . $connect->error);
    }

    error_log("Query executed successfully. Number of rows: " . $result->num_rows);

    while ($row = $result->fetch_assoc()) {
        error_log("Processing row ID: " . $row['id']);
        $output['data'][] = array(
            'id' => $row['id'],
            'sale_number' => 'SALE-' . str_pad($row['id'], 5, '0', STR_PAD_LEFT),
            'sale_date' => !empty($row['sale_date']) ? $row['sale_date'] : $row['created_at'],
            'buyer_name' => $row['buyer_name'],
            'sales_type' => $row['sales_type'],
            'quantity' => intval($row['quantity']),
            'unit_price' => floatval($row['unit_price']),
            'total' => floatval($row['total']),
            'currency' => $row['currency'] ?: 'ETB',
            'rate' => floatval($row['rate'] ?: 1)
        );
    }

    error_log("Data processing complete. Number of records: " . count($output['data']));

} catch (Exception $e) {
    error_log("Error in fetchSaleData.php: " . $e->getMessage());
    $output['error'] = $e->getMessage();
} finally {
    if (isset($result)) {
        $result->free();
    }
    if (isset($connect) && $connect->ping()) {
        $connect->close();
    }
}

// Log final output
error_log("Final output: " . json_encode($output));

echo json_encode($output);
exit;
?> 