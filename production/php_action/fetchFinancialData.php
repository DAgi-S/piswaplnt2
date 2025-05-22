<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../php_action/core.php';

// Get the year parameter, default to current year if not provided
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Initialize response array
$response = array();

try {
    // Get total revenue (from sales)
    $revenueQuery = "SELECT COALESCE(SUM(total_amount), 0) as total_revenue 
                     FROM sales_orders 
                     WHERE order_status != 'cancelled'
                     AND YEAR(created_at) = ?";
    $stmt = $connect->prepare($revenueQuery);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $revenueResult = $stmt->get_result();
    if (!$revenueResult) {
        throw new Exception("Database error: " . $connect->error);
    }
    $revenueData = $revenueResult->fetch_assoc();
    $totalRevenue = floatval($revenueData['total_revenue']);

    // Get total expenses (from purchases)
    $expensesQuery = "SELECT COALESCE(SUM(grand_total), 0) as total_expenses 
                      FROM purchases 
                      WHERE status = 1
                      AND YEAR(created_at) = ?";
    $stmt = $connect->prepare($expensesQuery);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $expensesResult = $stmt->get_result();
    if (!$expensesResult) {
        throw new Exception("Database error: " . $connect->error);
    }
    $expensesData = $expensesResult->fetch_assoc();
    $totalExpenses = floatval($expensesData['total_expenses']);

    // Calculate profits
    $grossProfit = $totalRevenue - $totalExpenses;
    $netProfit = $grossProfit; // For now, net profit is same as gross profit

    // Calculate metrics
    $profitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0;
    
    // Get cash flow (simplified as net movement of money)
    $cashFlow = $netProfit;

    // Calculate revenue growth (comparing to previous year)
    $prevRevenueQuery = "SELECT COALESCE(SUM(total_amount), 0) as prev_revenue 
                         FROM sales_orders 
                         WHERE order_status != 'cancelled' 
                         AND YEAR(created_at) = ? - 1";
    $stmt = $connect->prepare($prevRevenueQuery);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $prevRevenueResult = $stmt->get_result();
    if (!$prevRevenueResult) {
        throw new Exception("Database error: " . $connect->error);
    }
    $prevRevenueData = $prevRevenueResult->fetch_assoc();
    $prevRevenue = floatval($prevRevenueData['prev_revenue']);
    
    $revenueGrowth = $prevRevenue > 0 ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 2) : 0;

    // Calculate cost ratio
    $costRatio = $totalRevenue > 0 ? round(($totalExpenses / $totalRevenue) * 100, 2) : 0;

    // Get monthly data for chart
    $chartQuery = "SELECT 
                     DATE_FORMAT(dates.month, '%Y-%m') as month,
                     COALESCE(SUM(s.total_amount), 0) as revenue,
                     COALESCE(SUM(p.grand_total), 0) as expenses
                   FROM 
                     (SELECT DATE_ADD(DATE_FORMAT(?, '%Y-01-01'), INTERVAL n MONTH) as month
                      FROM (
                          SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3
                          UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7
                          UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11
                      ) numbers) dates
                   LEFT JOIN sales_orders s ON DATE_FORMAT(s.created_at, '%Y-%m') = DATE_FORMAT(dates.month, '%Y-%m')
                        AND s.order_status != 'cancelled'
                   LEFT JOIN purchases p ON DATE_FORMAT(p.created_at, '%Y-%m') = DATE_FORMAT(dates.month, '%Y-%m')
                        AND p.status = 1
                   GROUP BY dates.month
                   ORDER BY dates.month";
    
    $stmt = $connect->prepare($chartQuery);
    $yearStart = $year . '-01-01';
    $stmt->bind_param("s", $yearStart);
    $stmt->execute();
    $chartResult = $stmt->get_result();
    if (!$chartResult) {
        throw new Exception("Database error: " . $connect->error);
    }

    $chartData = array(
        'labels' => array(),
        'revenue' => array(),
        'expenses' => array()
    );

    while ($row = $chartResult->fetch_assoc()) {
        $chartData['labels'][] = date('M Y', strtotime($row['month'] . '-01'));
        $chartData['revenue'][] = floatval($row['revenue']);
        $chartData['expenses'][] = floatval($row['expenses']);
    }

    // Prepare response
    $response = array(
        'totalRevenue' => $totalRevenue,
        'totalExpenses' => $totalExpenses,
        'grossProfit' => $grossProfit,
        'netProfit' => $netProfit,
        'profitMargin' => $profitMargin,
        'cashFlow' => $cashFlow,
        'revenueGrowth' => $revenueGrowth,
        'costRatio' => $costRatio,
        'chartData' => $chartData
    );

} catch (Exception $e) {
    // Send error response
    http_response_code(500);
    $response = array(
        'error' => $e->getMessage()
    );
}

// Clean output buffer
if (ob_get_length()) ob_clean();

// Send response as JSON
header('Content-Type: application/json');
echo json_encode($response, JSON_NUMERIC_CHECK); 