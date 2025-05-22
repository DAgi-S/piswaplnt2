<?php
require_once 'core.php';

header('Content-Type: application/json');

try {
    // Get movement data for the last 7 days
    $query = "SELECT 
                DATE(date) as movement_date,
                movement_type,
                COUNT(*) as movement_count
              FROM stock_movements
              WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                AND status = 'completed'
              GROUP BY DATE(date), movement_type
              ORDER BY DATE(date)";
    
    $result = $connect->query($query);
    
    if (!$result) {
        throw new Exception("Error executing query: " . $connect->error);
    }

    // Initialize arrays for chart data
    $dates = [];
    $inboundData = [];
    $outboundData = [];
    
    // Get unique dates and initialize counts to 0
    $startDate = new DateTime(date('Y-m-d', strtotime('-6 days')));
    $endDate = new DateTime(date('Y-m-d'));
    $interval = new DateInterval('P1D');
    $dateRange = new DatePeriod($startDate, $interval, $endDate);
    
    foreach ($dateRange as $date) {
        $dateStr = $date->format('Y-m-d');
        $dates[] = $date->format('M d'); // Format for display
        $inboundData[$dateStr] = 0;
        $outboundData[$dateStr] = 0;
    }
    $dates[] = date('M d'); // Add today
    $inboundData[date('Y-m-d')] = 0;
    $outboundData[date('Y-m-d')] = 0;
    
    // Fill in actual counts
    while ($row = $result->fetch_assoc()) {
        $dateStr = $row['movement_date'];
        if ($row['movement_type'] === 'inbound') {
            $inboundData[$dateStr] = (int)$row['movement_count'];
        } else {
            $outboundData[$dateStr] = (int)$row['movement_count'];
        }
    }
    
    // Prepare response data
    $response = [
        'success' => true,
        'data' => [
            'labels' => $dates,
            'inbound' => array_values($inboundData),
            'outbound' => array_values($outboundData)
        ]
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'messages' => $e->getMessage()
    ];
}

$connect->close();
echo json_encode($response); 