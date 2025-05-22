<?php
require_once 'core.php';

header('Content-Type: application/json');

try {
    // Get counts for different stock statuses
    $query = "SELECT 
                CASE
                    WHEN current_stock = 0 THEN 'outOfStock'
                    WHEN current_stock <= min_stock_level * 0.5 THEN 'critical'
                    WHEN current_stock <= min_stock_level THEN 'low'
                    ELSE 'normal'
                END as stock_status,
                COUNT(*) as count
              FROM raw_materials
              WHERE active = 1
              GROUP BY 
                CASE
                    WHEN current_stock = 0 THEN 'outOfStock'
                    WHEN current_stock <= min_stock_level * 0.5 THEN 'critical'
                    WHEN current_stock <= min_stock_level THEN 'low'
                    ELSE 'normal'
                END";
    
    $result = $connect->query($query);
    
    if (!$result) {
        throw new Exception("Error executing query: " . $connect->error);
    }

    // Initialize counts
    $stockStatus = [
        'normal' => 0,
        'low' => 0,
        'critical' => 0,
        'outOfStock' => 0
    ];
    
    // Fill in actual counts
    while ($row = $result->fetch_assoc()) {
        $stockStatus[$row['stock_status']] = (int)$row['count'];
    }
    
    // Prepare response data
    $response = [
        'success' => true,
        'data' => $stockStatus
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'messages' => $e->getMessage()
    ];
}

$connect->close();
echo json_encode($response); 