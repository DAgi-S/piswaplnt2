<?php
require_once '../../php_action/core.php';

// Get the year parameter, default to current year if not provided
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$response = array();

try {
    // Get quality metrics overview
    $metricsQuery = "SELECT 
                       COUNT(*) as total_checks,
                       COUNT(CASE WHEN quality_status = 'pass' THEN 1 END) as passed_checks,
                       COUNT(CASE WHEN quality_status = 'fail' THEN 1 END) as failed_checks
                     FROM quality_checks
                     WHERE YEAR(check_date) = ?";
    
    $stmt = $connect->prepare($metricsQuery);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $metricsData = $stmt->get_result()->fetch_assoc();

    // Calculate rates
    $totalChecks = intval($metricsData['total_checks']);
    $passRate = $totalChecks > 0 ? ($metricsData['passed_checks'] / $totalChecks) * 100 : 0;
    $rejectionRate = $totalChecks > 0 ? ($metricsData['failed_checks'] / $totalChecks) * 100 : 0;

    // Get defect analysis
    $defectQuery = "SELECT 
                      defect_type,
                      COUNT(*) as count,
                      severity,
                      impact_level
                    FROM quality_defects
                    WHERE YEAR(reported_date) = ?
                    GROUP BY defect_type, severity, impact_level
                    ORDER BY count DESC
                    LIMIT 5";
    
    $stmt = $connect->prepare($defectQuery);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $defectResult = $stmt->get_result();
    
    $defectAnalysis = array();
    while ($row = $defectResult->fetch_assoc()) {
        $defectAnalysis[] = $row;
    }

    // Get critical defects count
    $criticalQuery = "SELECT COUNT(*) as critical_count
                     FROM quality_defects
                     WHERE YEAR(reported_date) = ?
                     AND severity = 'critical'";
    
    $stmt = $connect->prepare($criticalQuery);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $criticalDefects = $stmt->get_result()->fetch_assoc()['critical_count'];

    // Get quality trend data
    $trendQuery = "SELECT 
                     DATE_FORMAT(check_date, '%Y-%m') as month,
                     COUNT(*) as total_checks,
                     COUNT(CASE WHEN quality_status = 'pass' THEN 1 END) as passed_checks
                   FROM quality_checks
                   WHERE YEAR(check_date) = ?
                   GROUP BY DATE_FORMAT(check_date, '%Y-%m')
                   ORDER BY month";
    
    $stmt = $connect->prepare($trendQuery);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $trendResult = $stmt->get_result();
    
    $qualityTrend = array(
        'labels' => array(),
        'rates' => array()
    );
    
    while ($row = $trendResult->fetch_assoc()) {
        $qualityTrend['labels'][] = date('M Y', strtotime($row['month'] . '-01'));
        $rate = $row['total_checks'] > 0 ? 
                ($row['passed_checks'] / $row['total_checks']) * 100 : 0;
        $qualityTrend['rates'][] = round($rate, 2);
    }

    // Calculate overall trend
    $trendChange = 0;
    if (count($qualityTrend['rates']) >= 2) {
        $firstRate = $qualityTrend['rates'][0];
        $lastRate = end($qualityTrend['rates']);
        $trendChange = $firstRate > 0 ? 
                      (($lastRate - $firstRate) / $firstRate) * 100 : 0;
    }

    // Prepare response
    $response = array(
        'success' => true,
        'data' => array(
            'metrics' => array(
                'totalChecks' => $totalChecks,
                'passedChecks' => intval($metricsData['passed_checks']),
                'failedChecks' => intval($metricsData['failed_checks']),
                'passRate' => round($passRate, 2),
                'rejectionRate' => round($rejectionRate, 2)
            ),
            'defectAnalysis' => $defectAnalysis,
            'statistics' => array(
                'totalInspections' => $totalChecks,
                'criticalDefects' => intval($criticalDefects)
            ),
            'qualityTrend' => $qualityTrend,
            'trendChange' => round($trendChange, 2)
        )
    );

} catch (Exception $e) {
    $response = array(
        'success' => false,
        'error' => $e->getMessage()
    );
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 