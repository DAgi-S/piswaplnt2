<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for session timeout or invalid session
if (!isset($_SESSION['userId'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        'success' => false,
        'messages' => ['Session expired or invalid. Please refresh the page and login again.'],
        'redirect' => '../index.php'
    ]);
    exit();
}

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once 'core.php';

$response = array(
    'success' => false,
    'messages' => [],
    'data' => []
);

try {
    // Check database connection
    if (!isset($connect) || $connect->connect_error) {
        throw new Exception("Database connection failed: " . ($connect->connect_error ?? "Connection not established"));
    }

    // Get cycle ID from POST data
    $cycleId = isset($_POST['cycle_id']) ? intval($_POST['cycle_id']) : 0;
    if ($cycleId <= 0) {
        throw new Exception("Invalid cycle ID");
    }

    // Fetch sales that are not already in any business cycle
    $sql = "SELECT s.id, s.buyer_name, s.total, s.currency, s.rate, s.sale_date 
            FROM gps_sales s 
            LEFT JOIN gps_business_cycle_sales bcs ON s.id = bcs.sale_id 
            WHERE bcs.id IS NULL 
            ORDER BY s.sale_date DESC";
    
    $result = $connect->query($sql);
    if (!$result) {
        throw new Exception("Error fetching sales: " . $connect->error);
    }

    while ($row = $result->fetch_assoc()) {
        $response['data'][] = array(
            'id' => $row['id'],
            'buyer_name' => $row['buyer_name'] ?: 'Sale #' . $row['id'], // Use buyer_name if available, otherwise use ID
            'total' => number_format($row['total'], 2),
            'currency' => $row['currency'],
            'sale_date' => $row['sale_date']
        );
    }

    $response['success'] = true;

} catch (Exception $e) {
    error_log("Error in fetchAvailableSales.php: " . $e->getMessage());
    $response['messages'][] = $e->getMessage();
} finally {
    if (isset($connect)) {
        $connect->close();
    }
}

echo json_encode($response);
exit; 