<?php
require_once 'core.php';

// Set header type to JSON
header('Content-Type: application/json');

// Get the year parameter
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

try {
    // Get total sales statistics
    $salesQuery = "SELECT 
        COUNT(*) as total_sales,
        COALESCE(SUM(grand_total), 0) as total_revenue,
        COALESCE(SUM(paid_amount), 0) as total_paid,
        COALESCE(SUM(grand_total - paid_amount), 0) as total_pending
        FROM orders 
        WHERE YEAR(order_date) = ?";
    
    $stmt = $connect->prepare($salesQuery);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $salesResult = $stmt->get_result();
    $salesData = $salesResult->fetch_assoc();

    // Calculate average order value
    $averageOrderValue = $salesData['total_sales'] > 0 ? 
        $salesData['total_revenue'] / $salesData['total_sales'] : 0;

    // Calculate collection rate
    $collectionRate = $salesData['total_revenue'] > 0 ? 
        ($salesData['total_paid'] / $salesData['total_revenue']) * 100 : 0;

    // Get completed sales count
    $completedQuery = "SELECT COUNT(*) as completed
        FROM orders 
        WHERE YEAR(order_date) = ? 
        AND paid_amount >= grand_total";
    
    $stmt = $connect->prepare($completedQuery);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $completedResult = $stmt->get_result();
    $completedData = $completedResult->fetch_assoc();

    // Calculate growth rate (compare with previous year)
    $prevYearQuery = "SELECT COALESCE(SUM(grand_total), 0) as prev_revenue
        FROM orders 
        WHERE YEAR(order_date) = ? - 1";
    
    $stmt = $connect->prepare($prevYearQuery);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $prevYearResult = $stmt->get_result();
    $prevYearData = $prevYearResult->fetch_assoc();

    $growthRate = $prevYearData['prev_revenue'] > 0 ? 
        (($salesData['total_revenue'] - $prevYearData['prev_revenue']) / $prevYearData['prev_revenue']) * 100 : 0;

    // Get monthly sales data
    $monthlyQuery = "SELECT 
        DATE_FORMAT(order_date, '%Y-%m') as month,
        COUNT(*) as orders,
        COALESCE(SUM(grand_total), 0) as revenue,
        COALESCE(AVG(grand_total), 0) as average_value
        FROM orders 
        WHERE YEAR(order_date) = ?
        GROUP BY month
        ORDER BY month";
    
    $stmt = $connect->prepare($monthlyQuery);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $monthlyResult = $stmt->get_result();

    $monthlyData = array(
        'labels' => array(),
        'orders' => array(),
        'revenue' => array(),
        'averageValue' => array()
    );

    while ($row = $monthlyResult->fetch_assoc()) {
        $monthlyData['labels'][] = date('M Y', strtotime($row['month'] . '-01'));
        $monthlyData['orders'][] = (int)$row['orders'];
        $monthlyData['revenue'][] = (float)$row['revenue'];
        $monthlyData['averageValue'][] = (float)$row['average_value'];
    }

    // Get recent sales
    $recentSalesQuery = "SELECT 
        o.order_id as order_number,
        o.order_date,
        c.client_name,
        o.grand_total as total_amount,
        o.paid_amount,
        (o.grand_total - o.paid_amount) as balance,
        CASE 
            WHEN o.paid_amount >= o.grand_total THEN 'paid'
            WHEN o.paid_amount > 0 THEN 'partial'
            ELSE 'unpaid'
        END as payment_status
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        WHERE YEAR(o.order_date) = ?
        ORDER BY o.order_date DESC
        LIMIT 5";
    
    $stmt = $connect->prepare($recentSalesQuery);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $recentSales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Prepare response
    $response = array(
        'success' => true,
        'totalSales' => (int)$salesData['total_sales'],
        'totalRevenue' => (float)$salesData['total_revenue'],
        'averageOrderValue' => (float)$averageOrderValue,
        'collectionRate' => (float)$collectionRate,
        'completedSales' => (int)$completedData['completed'],
        'totalPaidAmount' => (float)$salesData['total_paid'],
        'totalPendingAmount' => (float)$salesData['total_pending'],
        'growthRate' => (float)$growthRate,
        'monthlyData' => $monthlyData,
        'recentSales' => $recentSales
    );

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
} 