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
    'messages' => []
);

try {
    // Check database connection
    if (!isset($connect) || $connect->connect_error) {
        throw new Exception("Database connection failed: " . ($connect->connect_error ?? "Connection not established"));
    }

    // Get POST data
    $cycleId = isset($_POST['cycle_id']) ? intval($_POST['cycle_id']) : 0;
    $saleId = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;

    // Validate inputs
    if ($cycleId <= 0 || $saleId <= 0) {
        throw new Exception("Invalid cycle ID or sale ID");
    }

    // Check if cycle exists and is active
    $sql = "SELECT status FROM gps_business_cycles WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $cycleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        throw new Exception("Business cycle not found");
    }
    
    $cycle = $result->fetch_assoc();
    if ($cycle['status'] !== 'active') {
        throw new Exception("Cannot add sale to a non-active business cycle");
    }

    // Check if sale exists and is not already in a cycle
    $sql = "SELECT s.* FROM gps_sales s 
            LEFT JOIN gps_business_cycle_sales bcs ON s.id = bcs.sale_id 
            WHERE s.id = ? AND bcs.id IS NULL";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $saleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        throw new Exception("Sale not found or already assigned to a cycle");
    }

    // Begin transaction
    $connect->begin_transaction();

    try {
        // Add sale to cycle
        $sql = "INSERT INTO gps_business_cycle_sales (business_cycle_id, sale_id, created_at) VALUES (?, ?, NOW())";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("ii", $cycleId, $saleId);
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding sale to cycle: " . $stmt->error);
        }

        // Update cycle's total sales amount
        $sql = "UPDATE gps_business_cycles bc 
                SET bc.total_sales_etb = (
                    SELECT COALESCE(SUM(
                        CASE 
                            WHEN s.currency = 'ETB' THEN s.total 
                            ELSE s.total * s.rate
                        END
                    ), 0)
                    FROM gps_business_cycle_sales bcs
                    JOIN gps_sales s ON bcs.sale_id = s.id
                    WHERE bcs.business_cycle_id = ?
                )
                WHERE bc.id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("ii", $cycleId, $cycleId);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating cycle total: " . $stmt->error);
        }

        // Update net profit
        $sql = "UPDATE gps_business_cycles 
                SET net_profit_etb = total_sales_etb - total_purchase_etb 
                WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $cycleId);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating net profit: " . $stmt->error);
        }

        $connect->commit();
        $response['success'] = true;
        $response['messages'][] = "Sale successfully added to cycle";

    } catch (Exception $e) {
        $connect->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in addSaleToCycle.php: " . $e->getMessage());
    $response['messages'][] = $e->getMessage();
} finally {
    if (isset($connect)) {
        $connect->close();
    }
}

echo json_encode($response);
exit; 