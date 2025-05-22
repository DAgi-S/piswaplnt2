<?php
require_once 'core.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => '',
    'data' => array(
        'stockAlerts' => array(),
        'delayedOrders' => array(),
        'productionMetrics' => array()
    )
);

try {
    // Start transaction for consistent reads
    $connect->begin_transaction();

    // 1. Get stock alerts
    $stockSql = "SELECT 
                    m.id,
                    m.name as material_name,
                    m.material_code,
                    m.current_stock,
                    m.minimum_stock,
                    COALESCE(SUM(pom.required_quantity - pom.consumed_quantity), 0) as pending_consumption
                FROM raw_materials m
                LEFT JOIN production_order_materials pom ON m.id = pom.material_id
                    AND pom.status != 'completed'
                GROUP BY m.id
                HAVING current_stock <= minimum_stock 
                    OR (current_stock - pending_consumption) <= minimum_stock
                ORDER BY 
                    CASE 
                        WHEN current_stock <= 0 THEN 1
                        WHEN current_stock <= minimum_stock THEN 2
                        WHEN (current_stock - pending_consumption) <= minimum_stock THEN 3
                        ELSE 4
                    END";

    $stockResult = $connect->query($stockSql);
    while($row = $stockResult->fetch_assoc()) {
        $response['data']['stockAlerts'][] = array(
            'material_id' => $row['id'],
            'material_name' => $row['material_name'],
            'material_code' => $row['material_code'],
            'current_stock' => $row['current_stock'],
            'minimum_stock' => $row['minimum_stock'],
            'pending_consumption' => $row['pending_consumption'],
            'projected_stock' => $row['current_stock'] - $row['pending_consumption'],
            'alert_type' => $row['current_stock'] <= 0 ? 'critical' : 
                          ($row['current_stock'] <= $row['minimum_stock'] ? 'warning' : 'info')
        );
    }

    // 2. Get delayed orders
    $ordersSql = "SELECT 
                    po.id,
                    po.order_number,
                    po.product_id,
                    po.target_quantity,
                    po.completed_quantity,
                    po.start_date,
                    po.expected_completion_date,
                    pp.name as product_name,
                    DATEDIFF(CURRENT_DATE, po.expected_completion_date) as days_delayed,
                    (SELECT COUNT(*) FROM production_order_materials pom 
                     WHERE pom.production_order_id = po.id 
                     AND pom.status = 'insufficient_stock') as materials_pending
                FROM production_orders po
                JOIN production_products pp ON po.product_id = pp.id
                WHERE po.status IN ('confirmed', 'in_progress')
                    AND (
                        (po.expected_completion_date < CURRENT_DATE)
                        OR
                        (po.target_quantity > po.completed_quantity 
                         AND DATEDIFF(po.expected_completion_date, CURRENT_DATE) <= 3)
                    )
                ORDER BY days_delayed DESC, po.expected_completion_date ASC";

    $ordersResult = $connect->query($ordersSql);
    while($row = $ordersResult->fetch_assoc()) {
        $response['data']['delayedOrders'][] = array(
            'order_id' => $row['id'],
            'order_number' => $row['order_number'],
            'product_name' => $row['product_name'],
            'target_quantity' => $row['target_quantity'],
            'completed_quantity' => $row['completed_quantity'],
            'completion_percentage' => ($row['completed_quantity'] / $row['target_quantity']) * 100,
            'expected_completion_date' => $row['expected_completion_date'],
            'days_delayed' => max(0, $row['days_delayed']),
            'materials_pending' => $row['materials_pending'],
            'alert_type' => $row['days_delayed'] > 0 ? 'critical' : 'warning'
        );
    }

    // 3. Get production metrics
    $metricsSql = "SELECT 
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as active_orders,
                    COUNT(CASE 
                        WHEN status IN ('confirmed', 'in_progress') 
                        AND expected_completion_date < CURRENT_DATE THEN 1 
                    END) as delayed_orders,
                    COALESCE(AVG(CASE 
                        WHEN status = 'completed' 
                        THEN DATEDIFF(actual_completion_date, start_date) 
                    END), 0) as avg_completion_days,
                    COUNT(DISTINCT CASE 
                        WHEN status IN ('confirmed', 'in_progress') 
                        THEN product_id 
                    END) as active_products
                FROM production_orders
                WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";

    $metricsResult = $connect->query($metricsSql);
    $metrics = $metricsResult->fetch_assoc();
    
    $response['data']['productionMetrics'] = array(
        'completed_orders' => $metrics['completed_orders'],
        'active_orders' => $metrics['active_orders'],
        'delayed_orders' => $metrics['delayed_orders'],
        'avg_completion_days' => round($metrics['avg_completion_days'], 1),
        'active_products' => $metrics['active_products'],
        'period' => 'Last 30 days'
    );

    // Commit transaction
    $connect->commit();
    
    $response['success'] = true;
    $response['messages'] = 'Monitoring status retrieved successfully';

} catch(Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    
    $response['messages'] = $e->getMessage();
    error_log('Error in fetchMonitoringStatus.php: ' . $e->getMessage());
}

echo json_encode($response);
$connect->close(); 