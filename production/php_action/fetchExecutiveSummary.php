<?php
require_once '../../php_action/core.php';

// Set header type to JSON
header('Content-Type: application/json');

// Get the year parameter, default to current year if not provided
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$response = array();

try {
    // Check if quality_checks table exists
    $tableCheck = $connect->query("SHOW TABLES LIKE 'quality_checks'");
    if ($tableCheck->num_rows == 0) {
        // Create quality_checks table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS quality_checks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            production_id INT,
            check_date DATE,
            status ENUM('pass', 'fail'),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $connect->query($createTable);

        // Insert sample data
        $sampleData = "INSERT INTO quality_checks 
            (production_id, check_date, status, notes)
            VALUES 
            (1, CURDATE(), 'pass', 'Regular quality check'),
            (2, CURDATE(), 'pass', 'Standard inspection'),
            (3, CURDATE(), 'fail', 'Failed quality standards')";
        $connect->query($sampleData);
    }

    // Get total production products count
    $productsQuery = "SELECT COUNT(*) as total 
                      FROM products 
                      WHERE status = 1 
                      AND YEAR(created_at) = ?";
    $stmt = $connect->prepare($productsQuery);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $productsCount = $result->fetch_assoc()['total'];

    // Get raw materials count
    $materialsQuery = "SELECT COUNT(*) as total 
                      FROM raw_materials 
                      WHERE status = 1 
                      AND YEAR(created_at) = ?";
    $stmt = $connect->prepare($materialsQuery);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $materialsCount = $result->fetch_assoc()['total'];

    // Get quality rate
    $qualityQuery = "SELECT 
        COUNT(CASE WHEN status = 'pass' THEN 1 END) * 100.0 / COUNT(*) as quality_rate
        FROM quality_checks 
        WHERE YEAR(check_date) = ?";
    $stmt = $connect->prepare($qualityQuery);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $qualityResult = $stmt->get_result();
    $qualityRate = $qualityResult->fetch_assoc()['quality_rate'] ?? 0;

    // Get sales orders data
    $salesQuery = "SELECT 
                     COUNT(*) as total_orders,
                     COALESCE(SUM(grand_total), 0) as total_amount
                   FROM orders 
                   WHERE YEAR(order_date) = ? 
                   AND order_status != 'cancelled'";
    $stmt = $connect->prepare($salesQuery);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $salesResult = $stmt->get_result();
    $salesData = $salesResult->fetch_assoc();

    // Get purchases data
    $purchasesQuery = "SELECT 
                         COALESCE(SUM(grand_total), 0) as total_amount
                       FROM purchases 
                       WHERE YEAR(purchase_date) = ? 
                       AND status = 1";
    $stmt = $connect->prepare($purchasesQuery);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $purchaseResult = $stmt->get_result();
    $purchasesData = $purchaseResult->fetch_assoc();

    // Calculate net revenue
    $netRevenue = $salesData['total_amount'] - $purchasesData['total_amount'];

    // Calculate VAT
    $salesVAT = $salesData['total_amount'] * 0.15;
    $purchaseVAT = $purchasesData['total_amount'] * 0.15;
    $netVAT = $salesVAT - $purchaseVAT;

    // Prepare response
    $response = array(
        'success' => true,
        'data' => array(
            'totalProducts' => intval($productsCount),
            'totalMaterials' => intval($materialsCount),
            'qualityRate' => floatval($qualityRate),
            'totalOrders' => intval($salesData['total_orders']),
            'totalSalesAmount' => floatval($salesData['total_amount']),
            'totalPurchases' => floatval($purchasesData['total_amount']),
            'netRevenue' => floatval($netRevenue),
            'totalSalesVAT' => floatval($salesVAT),
            'totalPurchaseVAT' => floatval($purchaseVAT),
            'netVAT' => floatval($netVAT)
        )
    );

} catch (Exception $e) {
    $response = array(
        'success' => false,
        'error' => $e->getMessage()
    );
}

// Send JSON response
echo json_encode($response); 