<?php
require_once 'core.php';

// Set header type to JSON
header('Content-Type: application/json');

// Get the year parameter
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

try {
    // Check if production_analysis table exists
    $tableCheck = $connect->query("SHOW TABLES LIKE 'production_analysis'");
    if ($tableCheck->num_rows == 0) {
        // Create production_analysis table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS production_analysis (
            id INT AUTO_INCREMENT PRIMARY KEY,
            production_date DATE,
            target_production INT,
            actual_production INT,
            efficiency_rate DECIMAL(5,2),
            status ENUM('completed', 'in_progress', 'pending') DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $connect->query($createTable);

        // Insert some sample data for testing
        $sampleData = "INSERT INTO production_analysis 
            (production_date, target_production, actual_production, efficiency_rate, status)
            VALUES 
            (DATE_SUB(CURDATE(), INTERVAL 1 MONTH), 100, 85, 85.00, 'completed'),
            (CURDATE(), 100, 90, 90.00, 'completed')";
        $connect->query($sampleData);
    }

    // Get production statistics
    $statsQuery = "SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        ROUND(COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0 / COUNT(*), 2) as completion_rate
        FROM production_analysis 
        WHERE YEAR(production_date) = ?";
    
    $stmt = $connect->prepare($statsQuery);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $statsResult = $stmt->get_result();
    $statistics = $statsResult->fetch_assoc();

    // Get efficiency metrics
    $efficiencyQuery = "SELECT 
        ROUND(AVG(efficiency_rate), 2) as monthly_avg,
        ROUND(AVG(efficiency_rate), 2) as overall_avg,
        MAX(efficiency_rate) as peak
        FROM production_analysis 
        WHERE YEAR(production_date) = ?";
    
    $stmt = $connect->prepare($efficiencyQuery);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $efficiencyResult = $stmt->get_result();
    $efficiency = $efficiencyResult->fetch_assoc();

    // Get monthly production trends
    $trendsQuery = "SELECT 
        DATE_FORMAT(production_date, '%Y-%m') as month,
        SUM(target_production) as target,
        SUM(actual_production) as actual
        FROM production_analysis 
        WHERE YEAR(production_date) = ?
        GROUP BY month
        ORDER BY month";
    
    $stmt = $connect->prepare($trendsQuery);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $trendsResult = $stmt->get_result();

    $labels = [];
    $targetProduction = [];
    $actualProduction = [];

    while ($row = $trendsResult->fetch_assoc()) {
        $labels[] = date('M Y', strtotime($row['month'] . '-01'));
        $targetProduction[] = (int)$row['target'];
        $actualProduction[] = (int)$row['actual'];
    }

    // Prepare response
    $response = array(
        'success' => true,
        'statistics' => array(
            'totalOrders' => (int)$statistics['total_orders'],
            'inProgress' => (int)$statistics['in_progress'],
            'completed' => (int)$statistics['completed'],
            'completionRate' => (float)$statistics['completion_rate']
        ),
        'efficiency' => array(
            'monthly' => (float)$efficiency['monthly_avg'],
            'overall' => (float)$efficiency['overall_avg'],
            'peak' => (float)$efficiency['peak']
        ),
        'productionTrends' => array(
            'labels' => $labels,
            'targetProduction' => $targetProduction,
            'actualProduction' => $actualProduction
        )
    );

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
} 