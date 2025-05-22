<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'api_headers.php';
require_once 'core.php';
require_once 'db_connect.php';

// Debug log function
function debug_log($message) {
    $log_file = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Validate request
    if (!isset($_POST['type'])) {
        throw new Exception('Report type is required');
    }

    // Get and sanitize inputs
    $reportType = $_POST['type'];
    $dateRange = isset($_POST['dateRange']) ? $_POST['dateRange'] : 'today';
    $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : '';
    $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : '';

    debug_log("Report request - Type: $reportType, Range: $dateRange");

    // Build date filter based on report type
    $dateField = 'created_at'; // default
    switch($reportType) {
        case 'production_efficiency':
            $dateField = 'po.start_date';
            break;
        case 'material_consumption':
            $dateField = 'pom.created_at';
            break;
        case 'raw_materials_stock':
            $dateField = 'rm.last_updated';
            break;
    }

    // Build date filter
    $dateFilter = "1=1";
    $dateRangeText = "Today";
    
    switch($dateRange) {
        case 'today':
            $dateFilter = "DATE($dateField) = CURRENT_DATE()";
            $dateRangeText = "Today";
            break;
        case 'yesterday':
            $dateFilter = "DATE($dateField) = DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)";
            $dateRangeText = "Yesterday";
            break;
        case 'last7days':
            $dateFilter = "DATE($dateField) >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";
            $dateRangeText = "Last 7 Days";
            break;
        case 'last30days':
            $dateFilter = "DATE($dateField) >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
            $dateRangeText = "Last 30 Days";
            break;
        case 'thisMonth':
            $dateFilter = "EXTRACT(YEAR_MONTH FROM $dateField) = EXTRACT(YEAR_MONTH FROM CURRENT_DATE())";
            $dateRangeText = "This Month";
            break;
        case 'lastMonth':
            $dateFilter = "EXTRACT(YEAR_MONTH FROM $dateField) = EXTRACT(YEAR_MONTH FROM DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
            $dateRangeText = "Last Month";
            break;
        case 'custom':
            if (!empty($startDate) && !empty($endDate)) {
                $startDate = $connect->real_escape_string($startDate);
                $endDate = $connect->real_escape_string($endDate);
                $dateFilter = "DATE($dateField) BETWEEN '$startDate' AND '$endDate'";
                $dateRangeText = "From $startDate to $endDate";
            }
            break;
    }

    $html = '';
    $data = [];
    $summary = [];

    // Handle different report types
    switch($reportType) {
        case 'production_efficiency':
            $sql = "SELECT 
                        po.order_number,
                        p.name as product_name,
                        po.target_quantity,
                        po.completed_quantity,
                        ROUND((po.completed_quantity / po.target_quantity) * 100, 2) as efficiency,
                        po.start_date,
                        po.actual_completion_date,
                        po.status
                    FROM production_orders po
                    LEFT JOIN production_products p ON po.product_id = p.id
                    WHERE $dateFilter
                    ORDER BY po.start_date DESC";
            
            $result = $connect->query($sql);
            if (!$result) {
                throw new Exception("Error in query: " . $connect->error);
            }

            $totalEfficiency = 0;
            $orderCount = 0;
            $completedCount = 0;
            $data = [];

            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
                if ($row['status'] === 'completed') {
                    $totalEfficiency += floatval($row['efficiency']);
                    $completedCount++;
                }
                $orderCount++;
            }

            $avgEfficiency = $completedCount > 0 ? round($totalEfficiency / $completedCount, 2) : 0;

            $summary = [
                'avgEfficiency' => $avgEfficiency,
                'totalOrders' => $orderCount,
                'completedOrders' => $completedCount
            ];

            $html = "
                <div class='summary-cards'>
                    <div class='summary-card'>
                        <h4>Average Efficiency</h4>
                        <h3>{$avgEfficiency}%</h3>
                    </div>
                    <div class='summary-card'>
                        <h4>Total Orders</h4>
                        <h3>{$orderCount}</h3>
                    </div>
                    <div class='summary-card'>
                        <h4>Completed Orders</h4>
                        <h3>{$completedCount}</h3>
                    </div>
                </div>
                <table id='reportTable' class='table table-bordered table-striped'>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Product</th>
                            <th>Target Qty</th>
                            <th>Completed Qty</th>
                            <th>Efficiency</th>
                            <th>Start Date</th>
                            <th>Completion Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>";

            foreach ($data as $row) {
                $efficiencyClass = $row['efficiency'] >= 90 ? 'text-success' : 
                                ($row['efficiency'] >= 70 ? 'text-warning' : 'text-danger');
                
                $statusClass = $row['status'] === 'completed' ? 'text-success' : 
                            ($row['status'] === 'in_progress' ? 'text-warning' : 'text-danger');
                
                $html .= "<tr>
                    <td>{$row['order_number']}</td>
                    <td>{$row['product_name']}</td>
                    <td>" . number_format($row['target_quantity']) . "</td>
                    <td>" . number_format($row['completed_quantity']) . "</td>
                    <td class='{$efficiencyClass}'>{$row['efficiency']}%</td>
                    <td>" . date('Y-m-d', strtotime($row['start_date'])) . "</td>
                    <td>" . ($row['actual_completion_date'] ? date('Y-m-d', strtotime($row['actual_completion_date'])) : '-') . "</td>
                    <td class='{$statusClass}'>" . ucwords(str_replace('_', ' ', $row['status'])) . "</td>
                </tr>";
            }

            $html .= "</tbody></table>";
            break;

        case 'material_consumption':
            $sql = "SELECT 
                        rm.material_code,
                        rm.name as material_name,
                        rm.unit,
                        SUM(pom.consumed_quantity) as total_consumed,
                        AVG(pom.consumed_quantity) as avg_consumption,
                        COUNT(DISTINCT pom.production_order_id) as order_count
                    FROM production_order_materials pom
                    LEFT JOIN raw_materials rm ON pom.material_id = rm.id
                    WHERE $dateFilter
                    GROUP BY rm.id
                    ORDER BY total_consumed DESC";
            
            $result = $connect->query($sql);
            if (!$result) {
                throw new Exception("Error in query: " . $connect->error);
            }

            $html = "
                <table id='reportTable' class='table table-bordered table-striped'>
                    <thead>
                        <tr>
                            <th>Material Code</th>
                            <th>Material Name</th>
                            <th>Total Consumed</th>
                            <th>Avg Consumption</th>
                            <th>Unit</th>
                            <th>Orders Count</th>
                        </tr>
                    </thead>
                    <tbody>";

            while ($row = $result->fetch_assoc()) {
                $html .= "<tr>
                    <td>{$row['material_code']}</td>
                    <td>{$row['material_name']}</td>
                    <td>" . number_format($row['total_consumed'], 2) . "</td>
                    <td>" . number_format($row['avg_consumption'], 2) . "</td>
                    <td>{$row['unit']}</td>
                    <td>{$row['order_count']}</td>
                </tr>";
            }

            $html .= "</tbody></table>";
            break;

        case 'raw_materials_stock':
            $sql = "SELECT 
                        rm.material_code,
                        rm.name,
                        rm.quantity as current_stock,
                        rm.unit,
                        rm.minimum_quantity,
                        CASE 
                            WHEN rm.quantity <= rm.minimum_quantity THEN 'Low Stock'
                            WHEN rm.quantity <= (rm.minimum_quantity * 1.5) THEN 'Warning'
                            ELSE 'Good'
                        END as stock_status
                    FROM raw_materials rm
                    ORDER BY stock_status ASC, rm.name ASC";
            
            $result = $connect->query($sql);
            if (!$result) {
                throw new Exception("Error in query: " . $connect->error);
            }

            $lowStock = 0;
            $warning = 0;
            $good = 0;

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
                switch($row['stock_status']) {
                    case 'Low Stock': $lowStock++; break;
                    case 'Warning': $warning++; break;
                    case 'Good': $good++; break;
                }
            }

            $html = "
                <div class='summary-cards'>
                    <div class='summary-card'>
                        <h4>Low Stock</h4>
                        <h3 class='text-danger'>{$lowStock}</h3>
                    </div>
                    <div class='summary-card'>
                        <h4>Warning</h4>
                        <h3 class='text-warning'>{$warning}</h3>
                    </div>
                    <div class='summary-card'>
                        <h4>Good</h4>
                        <h3 class='text-success'>{$good}</h3>
                    </div>
                </div>
                <table id='reportTable' class='table table-bordered table-striped'>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Material</th>
                            <th>Current Stock</th>
                            <th>Unit</th>
                            <th>Minimum Qty</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>";

            foreach ($data as $row) {
                $statusClass = $row['stock_status'] === 'Low Stock' ? 'text-danger' : 
                            ($row['stock_status'] === 'Warning' ? 'text-warning' : 'text-success');
                
                $html .= "<tr>
                    <td>{$row['material_code']}</td>
                    <td>{$row['name']}</td>
                    <td>{$row['current_stock']}</td>
                    <td>{$row['unit']}</td>
                    <td>{$row['minimum_quantity']}</td>
                    <td class='{$statusClass}'>{$row['stock_status']}</td>
                </tr>";
            }

            $html .= "</tbody></table>";
            break;

        case 'material_cost':
            $sql = "SELECT 
                        rm.material_code,
                        rm.name as material_name,
                        rm.unit,
                        rm.cost_per_unit,
                        SUM(pom.consumed_quantity) as total_consumed,
                        SUM(pom.consumed_quantity * rm.cost_per_unit) as total_cost
                    FROM production_order_materials pom
                    LEFT JOIN raw_materials rm ON pom.material_id = rm.id
                    WHERE $dateFilter
                    GROUP BY rm.id
                    ORDER BY total_cost DESC";
            
            $result = $connect->query($sql);
            if (!$result) {
                throw new Exception("Error in query: " . $connect->error);
            }

            $totalCost = 0;
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
                $totalCost += floatval($row['total_cost']);
            }

            $html = "
                <div class='summary-cards'>
                    <div class='summary-card'>
                        <h4>Total Material Cost</h4>
                        <h3>" . number_format($totalCost, 2) . "</h3>
                    </div>
                </div>
                <table id='reportTable' class='table table-bordered table-striped'>
                    <thead>
                        <tr>
                            <th>Material Code</th>
                            <th>Material Name</th>
                            <th>Unit Cost</th>
                            <th>Total Consumed</th>
                            <th>Unit</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>";

            foreach ($data as $row) {
                $html .= "<tr>
                    <td>{$row['material_code']}</td>
                    <td>{$row['material_name']}</td>
                    <td>" . number_format($row['cost_per_unit'], 2) . "</td>
                    <td>" . number_format($row['total_consumed'], 2) . "</td>
                    <td>{$row['unit']}</td>
                    <td>" . number_format($row['total_cost'], 2) . "</td>
                </tr>";
            }

            $html .= "</tbody></table>";
            break;

        default:
            throw new Exception('Invalid report type');
    }

    // Return JSON response
    echo json_encode([
        'success' => true,
        'html' => $html,
        'dateRange' => $dateRangeText,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    debug_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$connect->close(); 