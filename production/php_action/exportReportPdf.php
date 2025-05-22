<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Get report type
$reportType = isset($_GET['type']) ? $_GET['type'] : '';
if (empty($reportType)) {
    die("Report type is required");
}

// Get date range parameters
$dateRange = isset($_GET['dateRange']) ? $_GET['dateRange'] : 'today';
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';

// Build date filter
$dateFilter = "1=1";
$dateRangeText = "All Time";
switch($dateRange) {
    case 'today':
        $dateFilter = "DATE(created_at) = CURRENT_DATE()";
        $dateRangeText = "Today";
        break;
    case 'yesterday':
        $dateFilter = "DATE(created_at) = DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)";
        $dateRangeText = "Yesterday";
        break;
    case 'last7days':
        $dateFilter = "DATE(created_at) >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";
        $dateRangeText = "Last 7 Days";
        break;
    case 'last30days':
        $dateFilter = "DATE(created_at) >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
        $dateRangeText = "Last 30 Days";
        break;
    case 'thisMonth':
        $dateFilter = "EXTRACT(YEAR_MONTH FROM created_at) = EXTRACT(YEAR_MONTH FROM CURRENT_DATE())";
        $dateRangeText = "This Month";
        break;
    case 'lastMonth':
        $dateFilter = "EXTRACT(YEAR_MONTH FROM created_at) = EXTRACT(YEAR_MONTH FROM DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
        $dateRangeText = "Last Month";
        break;
    case 'custom':
        if (!empty($startDate) && !empty($endDate)) {
            $startDate = $connect->real_escape_string($startDate);
            $endDate = $connect->real_escape_string($endDate);
            $dateFilter = "DATE(created_at) BETWEEN '$startDate' AND '$endDate'";
            $dateRangeText = "From $startDate to $endDate";
        }
        break;
}

// Initialize HTML content
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>' . ucwords(str_replace('_', ' ', $reportType)) . ' Report</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .meta { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4472C4; color: white; }
        .summary-cards { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .summary-card { border: 1px solid #ddd; padding: 10px; text-align: center; width: 30%; }
        .text-success { color: #70AD47; }
        .text-warning { color: #FFB84D; }
        .text-danger { color: #FF6B6B; }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . ucwords(str_replace('_', ' ', $reportType)) . ' Report</h1>
        <div class="meta">
            <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
            <p>Date Range: ' . $dateRangeText . '</p>
        </div>
    </div>
';

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
                    po.actual_completion_date
                FROM production_orders po
                LEFT JOIN production_products p ON po.product_id = p.id
                WHERE $dateFilter AND po.status = 'completed'
                ORDER BY po.start_date DESC";
        
        $result = $connect->query($sql);
        if (!$result) {
            die("Error in query: " . $connect->error);
        }

        $totalEfficiency = 0;
        $orderCount = 0;
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
            $totalEfficiency += floatval($row['efficiency']);
            $orderCount++;
        }
        $avgEfficiency = $orderCount > 0 ? round($totalEfficiency / $orderCount, 2) : 0;

        $html .= '
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Average Efficiency</h3>
                    <h2>' . $avgEfficiency . '%</h2>
                </div>
                <div class="summary-card">
                    <h3>Total Orders</h3>
                    <h2>' . $orderCount . '</h2>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Product</th>
                        <th>Target Qty</th>
                        <th>Completed Qty</th>
                        <th>Efficiency</th>
                        <th>Start Date</th>
                        <th>Completion Date</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($data as $row) {
            $efficiencyClass = $row['efficiency'] >= 90 ? 'text-success' : 
                            ($row['efficiency'] >= 70 ? 'text-warning' : 'text-danger');
            
            $html .= '<tr>
                <td>' . $row['order_number'] . '</td>
                <td>' . $row['product_name'] . '</td>
                <td>' . $row['target_quantity'] . '</td>
                <td>' . $row['completed_quantity'] . '</td>
                <td class="' . $efficiencyClass . '">' . $row['efficiency'] . '%</td>
                <td>' . $row['start_date'] . '</td>
                <td>' . $row['actual_completion_date'] . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';
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
            die("Error in query: " . $connect->error);
        }

        $html .= '
            <table>
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
                <tbody>';

        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>
                <td>' . $row['material_code'] . '</td>
                <td>' . $row['material_name'] . '</td>
                <td>' . number_format($row['total_consumed'], 2) . '</td>
                <td>' . number_format($row['avg_consumption'], 2) . '</td>
                <td>' . $row['unit'] . '</td>
                <td>' . $row['order_count'] . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';
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
            die("Error in query: " . $connect->error);
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

        $html .= '
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Low Stock</h3>
                    <h2 class="text-danger">' . $lowStock . '</h2>
                </div>
                <div class="summary-card">
                    <h3>Warning</h3>
                    <h2 class="text-warning">' . $warning . '</h2>
                </div>
                <div class="summary-card">
                    <h3>Good</h3>
                    <h2 class="text-success">' . $good . '</h2>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Material</th>
                        <th>Current Stock</th>
                        <th>Unit</th>
                        <th>Min Quantity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($data as $row) {
            $statusClass = $row['stock_status'] == 'Good' ? 'text-success' : 
                        ($row['stock_status'] == 'Warning' ? 'text-warning' : 'text-danger');
            
            $html .= '<tr>
                <td>' . $row['material_code'] . '</td>
                <td>' . $row['name'] . '</td>
                <td>' . number_format($row['current_stock'], 2) . '</td>
                <td>' . $row['unit'] . '</td>
                <td>' . number_format($row['minimum_quantity'], 2) . '</td>
                <td class="' . $statusClass . '">' . $row['stock_status'] . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';
        break;

    default:
        die("Invalid report type");
}

$html .= '</body></html>';

// Initialize PDF options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);

// Create PDF
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF
$dompdf->stream($reportType . '_report_' . date('Y-m-d') . '.pdf', array('Attachment' => true));

$connect->close(); 