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
    'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => []
);

try {
    // Check database connection
    if (!isset($connect) || $connect->connect_error) {
        throw new Exception("Database connection failed");
    }

    // Get cycle ID from POST data
    $cycleId = isset($_POST['cycle_id']) ? intval($_POST['cycle_id']) : 0;
    if ($cycleId <= 0) {
        throw new Exception("Invalid cycle ID");
    }

    // Get total records count
    $sql = "SELECT COUNT(*) as count 
            FROM gps_business_cycle_sales bcs 
            WHERE bcs.business_cycle_id = ?";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing count query: " . $connect->error);
    }
    
    $stmt->bind_param("i", $cycleId);
    if (!$stmt->execute()) {
        throw new Exception("Error executing count query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $response['recordsTotal'] = $response['recordsFiltered'] = $row['count'];

    // Fetch sales for this cycle
    $sql = "SELECT s.*, bcs.created_at as added_date 
            FROM gps_sales s 
            JOIN gps_business_cycle_sales bcs ON s.id = bcs.sale_id 
            WHERE bcs.business_cycle_id = ? 
            ORDER BY s.sale_date DESC";
    
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing sales query: " . $connect->error);
    }
    
    $stmt->bind_param("i", $cycleId);
    if (!$stmt->execute()) {
        throw new Exception("Error executing sales query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Error getting result set: " . $stmt->error);
    }

    while ($row = $result->fetch_assoc()) {
        $response['data'][] = array(
            'DT_RowId' => 'row_' . $row['id'],
            'date' => date('Y-m-d', strtotime($row['sale_date'])),
            'buyer' => $row['buyer_name'],
            'amount' => number_format($row['total'], 2),
            'currency' => $row['currency'],
            'type' => $row['sales_type'],
            'action' => '<button class="btn btn-info btn-sm" onclick="viewSale('.$row['id'].')">
                            <i class="fas fa-eye"></i> View
                        </button>'
        );
    }

} catch (Exception $e) {
    error_log("Error in fetchCycleSales.php: " . $e->getMessage());
    $response['error'] = $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($connect)) {
        $connect->close();
    }
}

echo json_encode($response);
exit;