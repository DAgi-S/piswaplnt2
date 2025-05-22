<?php
require_once 'core.php';

// Initialize response array
$response = array();

try {
    // Get total inbound movements
    $inboundQuery = "SELECT COALESCE(SUM(quantity), 0) as total_inbound 
                     FROM raw_material_movements 
                     WHERE movement_type = 'in'";
    $inboundResult = $connect->query($inboundQuery);
    $inboundData = $inboundResult->fetch_assoc();
    $response['totalInbound'] = $inboundData['total_inbound'];

    // Get total outbound movements
    $outboundQuery = "SELECT COALESCE(SUM(quantity), 0) as total_outbound 
                      FROM raw_material_movements 
                      WHERE movement_type = 'out'";
    $outboundResult = $connect->query($outboundQuery);
    $outboundData = $outboundResult->fetch_assoc();
    $response['totalOutbound'] = $outboundData['total_outbound'];

    // Calculate net movement
    $response['netMovement'] = $response['totalInbound'] - $response['totalOutbound'];

    // Get current stock value
    $stockValueQuery = "SELECT COALESCE(SUM(current_stock * cost_per_unit), 0) as stock_value 
                        FROM raw_materials";
    $stockValueResult = $connect->query($stockValueQuery);
    $stockValueData = $stockValueResult->fetch_assoc();
    $response['currentStockValue'] = $stockValueData['stock_value'];

    // Get total transactions count
    $transactionsQuery = "SELECT COUNT(*) as total_transactions 
                         FROM raw_material_movements";
    $transactionsResult = $connect->query($transactionsQuery);
    $transactionsData = $transactionsResult->fetch_assoc();
    $response['totalTransactions'] = $transactionsData['total_transactions'];

    // Get active locations count
    $locationsQuery = "SELECT COUNT(DISTINCT source_location_id) as active_locations 
                      FROM raw_material_movements 
                      WHERE source_location_id IS NOT NULL";
    $locationsResult = $connect->query($locationsQuery);
    $locationsData = $locationsResult->fetch_assoc();
    $response['activeLocations'] = $locationsData['active_locations'];

    // Calculate transfer rate (average daily transactions)
    $transferRateQuery = "SELECT ROUND(COUNT(*) / DATEDIFF(MAX(created_at), MIN(created_at)), 2) as transfer_rate 
                         FROM raw_material_movements";
    $transferRateResult = $connect->query($transferRateQuery);
    $transferRateData = $transferRateResult->fetch_assoc();
    $response['transferRate'] = $transferRateData['transfer_rate'] ?: 0;

    // Calculate turnover rate
    $turnoverQuery = "SELECT 
                        (SELECT COALESCE(SUM(quantity), 0) FROM raw_material_movements WHERE movement_type = 'out') /
                        (SELECT COALESCE(SUM(current_stock), 0) FROM raw_materials) * 100 as turnover_rate";
    $turnoverResult = $connect->query($turnoverQuery);
    $turnoverData = $turnoverResult->fetch_assoc();
    $response['turnoverRate'] = round($turnoverData['turnover_rate'] ?: 0, 2);

    // Get daily movement data for the chart
    $dailyMovementQuery = "SELECT 
                            DATE(created_at) as date,
                            SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE 0 END) as inbound,
                            SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END) as outbound,
                            SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE -quantity END) as net_change
                          FROM raw_material_movements
                          WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                          GROUP BY DATE(created_at)
                          ORDER BY DATE(created_at)";
    $dailyMovementResult = $connect->query($dailyMovementQuery);
    
    $chartData = array(
        'labels' => array(),
        'inbound' => array(),
        'outbound' => array(),
        'netChange' => array()
    );
    
    while ($row = $dailyMovementResult->fetch_assoc()) {
        $chartData['labels'][] = date('Y-m-d', strtotime($row['date']));
        $chartData['inbound'][] = floatval($row['inbound']);
        $chartData['outbound'][] = floatval($row['outbound']);
        $chartData['netChange'][] = floatval($row['net_change']);
    }
    
    $response['dailyMovement'] = $chartData;

    // Get recent movements
    $recentMovementsQuery = "SELECT 
                                rm.created_at as date,
                                rm.movement_type as type,
                                CASE 
                                    WHEN r.name IS NOT NULL THEN r.name
                                    ELSE p.name
                                END as item_name,
                                rm.quantity,
                                COALESCE(l.name, 'N/A') as location,
                                rm.reference_type,
                                rm.reference_id,
                                CASE 
                                    WHEN rm.quantity > 0 THEN 'Completed'
                                    ELSE 'Pending'
                                END as status
                            FROM raw_material_movements rm
                            LEFT JOIN raw_materials r ON rm.material_id = r.id
                            LEFT JOIN production_products p ON rm.product_id = p.id
                            LEFT JOIN inventory_locations l ON rm.source_location_id = l.id
                            ORDER BY rm.created_at DESC
                            LIMIT 10";
    $recentMovementsResult = $connect->query($recentMovementsQuery);
    
    $recentMovements = array();
    while ($row = $recentMovementsResult->fetch_assoc()) {
        $recentMovements[] = array(
            'date' => date('Y-m-d', strtotime($row['date'])),
            'type' => ucfirst($row['type']),
            'item' => $row['item_name'],
            'quantity' => $row['quantity'],
            'location' => $row['location'],
            'reference' => $row['reference_type'] . ' #' . $row['reference_id'],
            'status' => $row['status']
        );
    }
    
    $response['recentMovements'] = $recentMovements;

    // Send success response
    $response['success'] = true;

} catch (Exception $e) {
    // Send error response
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// Send response as JSON
header('Content-Type: application/json');
echo json_encode($response); 