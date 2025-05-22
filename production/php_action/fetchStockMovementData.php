<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../php_action/core.php';

// Initialize response array
$response = array();

try {
    // Get total inbound movements from both raw materials and products
    $inboundQuery = "SELECT COALESCE(SUM(quantity), 0) as total_inbound FROM (
                        SELECT quantity 
                        FROM raw_material_movements 
                        WHERE movement_type = 'in'
                        UNION ALL
                        SELECT quantity 
                        FROM stock_movements 
                        WHERE movement_type = 'in'
                    ) as combined_movements";
    $inboundResult = $connect->query($inboundQuery);
    if (!$inboundResult) {
        throw new Exception("Database error: " . $connect->error);
    }
    $inboundData = $inboundResult->fetch_assoc();
    $response['totalInbound'] = floatval($inboundData['total_inbound']);

    // Get total outbound movements from both raw materials and products
    $outboundQuery = "SELECT COALESCE(SUM(quantity), 0) as total_outbound FROM (
                        SELECT quantity 
                        FROM raw_material_movements 
                        WHERE movement_type = 'out'
                        UNION ALL
                        SELECT quantity 
                        FROM stock_movements 
                        WHERE movement_type = 'out'
                    ) as combined_movements";
    $outboundResult = $connect->query($outboundQuery);
    if (!$outboundResult) {
        throw new Exception("Database error: " . $connect->error);
    }
    $outboundData = $outboundResult->fetch_assoc();
    $response['totalOutbound'] = floatval($outboundData['total_outbound']);

    // Calculate net movement
    $response['netMovement'] = $response['totalInbound'] - $response['totalOutbound'];

    // Get current stock value (combining raw materials and products)
    $stockValueQuery = "SELECT COALESCE(
                            (SELECT SUM(current_stock * cost_per_unit) FROM raw_materials) +
                            (SELECT SUM(quantity * rate) FROM product_bom), 
                            0
                        ) as stock_value";
    $stockValueResult = $connect->query($stockValueQuery);
    if (!$stockValueResult) {
        throw new Exception("Database error: " . $connect->error);
    }
    $stockValueData = $stockValueResult->fetch_assoc();
    $response['currentStockValue'] = floatval($stockValueData['stock_value']);

    // Get total transactions count from both tables
    $transactionsQuery = "SELECT (
                            SELECT COUNT(*) FROM raw_material_movements
                            UNION ALL
                            SELECT COUNT(*) FROM stock_movements
                        ) as total_transactions";
    $transactionsResult = $connect->query($transactionsQuery);
    if (!$transactionsResult) {
        throw new Exception("Database error: " . $connect->error);
    }
    $transactionsData = $transactionsResult->fetch_assoc();
    $response['totalTransactions'] = intval($transactionsData['total_transactions']);

    // Get active locations count from warehouses
    $locationsQuery = "SELECT COUNT(*) as active_locations 
                      FROM warehouses 
                      WHERE status = 'active'";
    $locationsResult = $connect->query($locationsQuery);
    if (!$locationsResult) {
        throw new Exception("Database error: " . $connect->error);
    }
    $locationsData = $locationsResult->fetch_assoc();
    $response['activeLocations'] = intval($locationsData['active_locations']);

    // Calculate transfer rate (average daily transactions)
    $transferRateQuery = "SELECT ROUND(
                            (
                                SELECT COUNT(*) FROM (
                                    SELECT created_at FROM raw_material_movements
                                    UNION ALL
                                    SELECT created_at FROM stock_movements
                                ) as all_movements
                            ) / 
                            GREATEST(
                                DATEDIFF(
                                    COALESCE(MAX(latest_date), CURRENT_DATE),
                                    COALESCE(MIN(earliest_date), CURRENT_DATE)
                                ),
                                1
                            ),
                            2
                        ) as transfer_rate
                        FROM (
                            SELECT 
                                MIN(created_at) as earliest_date,
                                MAX(created_at) as latest_date
                            FROM (
                                SELECT created_at FROM raw_material_movements
                                UNION ALL
                                SELECT created_at FROM stock_movements
                            ) as dates
                        ) as date_range";
    $transferRateResult = $connect->query($transferRateQuery);
    if (!$transferRateResult) {
        throw new Exception("Database error: " . $connect->error);
    }
    $transferRateData = $transferRateResult->fetch_assoc();
    $response['transferRate'] = floatval($transferRateData['transfer_rate'] ?: 0);

    // Calculate turnover rate
    $turnoverQuery = "SELECT 
                        ROUND(
                            (
                                SELECT COALESCE(SUM(quantity), 0) FROM (
                                    SELECT quantity FROM raw_material_movements WHERE movement_type = 'out'
                                    UNION ALL
                                    SELECT quantity FROM stock_movements WHERE movement_type = 'out'
                                ) as outbound
                            ) /
                            NULLIF(
                                (
                                    SELECT COALESCE(SUM(current_stock), 0) FROM raw_materials
                                ) +
                                (
                                    SELECT COALESCE(SUM(current_stock), 0) FROM production_products
                                ),
                                0
                            ) * 100,
                            2
                        ) as turnover_rate";
    $turnoverResult = $connect->query($turnoverQuery);
    if (!$turnoverResult) {
        throw new Exception("Database error: " . $connect->error);
    }
    $turnoverData = $turnoverResult->fetch_assoc();
    $response['turnoverRate'] = floatval($turnoverData['turnover_rate']);

    // Get daily movement data for the chart (last 30 days)
    $dailyMovementQuery = "SELECT 
                            dates.date,
                            COALESCE(SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE 0 END), 0) as inbound,
                            COALESCE(SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END), 0) as outbound,
                            COALESCE(SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE -quantity END), 0) as net_change
                          FROM (
                              SELECT DATE(created_at) as date, movement_type, quantity
                              FROM raw_material_movements
                              WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                              UNION ALL
                              SELECT DATE(created_at) as date, movement_type, quantity
                              FROM stock_movements
                              WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                          ) as combined_movements
                          RIGHT JOIN (
                              SELECT DATE(date) as date
                              FROM (
                                  SELECT CURDATE() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as date
                                  FROM (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as a
                                  CROSS JOIN (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as b
                                  CROSS JOIN (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as c
                              ) as dates
                              WHERE date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                              AND date <= CURRENT_DATE
                          ) as dates ON dates.date = DATE(combined_movements.date)
                          GROUP BY dates.date
                          ORDER BY dates.date";
    
    $dailyMovementResult = $connect->query($dailyMovementQuery);
    if (!$dailyMovementResult) {
        throw new Exception("Database error: " . $connect->error);
    }
    
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
    $recentMovementsQuery = "SELECT * FROM (
                                SELECT 
                                    rm.created_at as date,
                                    rm.movement_type as type,
                                    COALESCE(r.name, 'Unknown') as item_name,
                                    rm.quantity,
                                    COALESCE(w.name, 'N/A') as location,
                                    rm.reference_type,
                                    rm.reference_id,
                                    'Completed' as status
                                FROM raw_material_movements rm
                                LEFT JOIN raw_materials r ON rm.material_id = r.id
                                LEFT JOIN warehouses w ON rm.source_location_id = w.id
                                UNION ALL
                                SELECT 
                                    sm.created_at as date,
                                    sm.movement_type as type,
                                    COALESCE(p.name, 'Unknown') as item_name,
                                    sm.quantity,
                                    COALESCE(w.name, 'N/A') as location,
                                    sm.reference_type,
                                    sm.reference_id,
                                    'Completed' as status
                                FROM stock_movements sm
                                LEFT JOIN production_products p ON sm.product_id = p.id
                                LEFT JOIN warehouses w ON sm.source_location_id = w.id
                            ) as combined_movements
                            ORDER BY date DESC
                            LIMIT 10";
    
    $recentMovementsResult = $connect->query($recentMovementsQuery);
    if (!$recentMovementsResult) {
        throw new Exception("Database error: " . $connect->error);
    }
    
    $recentMovements = array();
    while ($row = $recentMovementsResult->fetch_assoc()) {
        $recentMovements[] = array(
            'date' => date('Y-m-d', strtotime($row['date'])),
            'type' => ucfirst($row['type']),
            'item' => $row['item_name'],
            'quantity' => floatval($row['quantity']),
            'location' => $row['location'],
            'reference' => ($row['reference_id'] ? $row['reference_type'] . ' #' . $row['reference_id'] : $row['reference_type']),
            'status' => $row['status']
        );
    }
    
    $response['recentMovements'] = $recentMovements;
    $response['success'] = true;

} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// Clean output buffer
if (ob_get_length()) ob_clean();

// Send response as JSON
header('Content-Type: application/json');
echo json_encode($response, JSON_NUMERIC_CHECK); 