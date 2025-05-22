<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
switch($dateRange) {
    case 'today':
        $dateFilter = "DATE(created_at) = CURRENT_DATE()";
        break;
    case 'yesterday':
        $dateFilter = "DATE(created_at) = DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)";
        break;
    case 'last7days':
        $dateFilter = "DATE(created_at) >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";
        break;
    case 'last30days':
        $dateFilter = "DATE(created_at) >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
        break;
    case 'thisMonth':
        $dateFilter = "EXTRACT(YEAR_MONTH FROM created_at) = EXTRACT(YEAR_MONTH FROM CURRENT_DATE())";
        break;
    case 'lastMonth':
        $dateFilter = "EXTRACT(YEAR_MONTH FROM created_at) = EXTRACT(YEAR_MONTH FROM DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
        break;
    case 'custom':
        if (!empty($startDate) && !empty($endDate)) {
            $startDate = $connect->real_escape_string($startDate);
            $endDate = $connect->real_escape_string($endDate);
            $dateFilter = "DATE(created_at) BETWEEN '$startDate' AND '$endDate'";
        }
        break;
}

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator('Production System')
    ->setLastModifiedBy('Production System')
    ->setTitle(ucwords(str_replace('_', ' ', $reportType)) . ' Report')
    ->setSubject(ucwords(str_replace('_', ' ', $reportType)) . ' Report')
    ->setDescription('Report generated on ' . date('Y-m-d H:i:s'));

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
        
        // Set headers
        $headers = ['Order #', 'Product', 'Target Qty', 'Completed Qty', 'Efficiency', 'Start Date', 'Completion Date'];
        $columns = ['order_number', 'product_name', 'target_quantity', 'completed_quantity', 'efficiency', 'start_date', 'actual_completion_date'];
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
        
        // Set headers
        $headers = ['Material Code', 'Material Name', 'Total Consumed', 'Avg Consumption', 'Unit', 'Orders Count'];
        $columns = ['material_code', 'material_name', 'total_consumed', 'avg_consumption', 'unit', 'order_count'];
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
        
        // Set headers
        $headers = ['Code', 'Material', 'Current Stock', 'Unit', 'Min Quantity', 'Status'];
        $columns = ['material_code', 'name', 'current_stock', 'unit', 'minimum_quantity', 'stock_status'];
        break;

    default:
        die("Invalid report type");
}

// Execute query
$result = $connect->query($sql);
if (!$result) {
    die("Error in query: " . $connect->error);
}

// Set column headers
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

// Style the header row
$lastCol = chr(ord('A') + count($headers) - 1);
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4'],
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
];
$sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);

// Add data rows
$row = 2;
while ($data = $result->fetch_assoc()) {
    $col = 'A';
    foreach ($columns as $column) {
        $value = $data[$column];
        // Format numbers
        if (is_numeric($value) && strpos($column, 'quantity') !== false || strpos($column, 'consumed') !== false) {
            $value = number_format($value, 2);
        }
        // Add % to efficiency
        if ($column === 'efficiency') {
            $value .= '%';
        }
        $sheet->setCellValue($col . $row, $value);
        $col++;
    }
    $row++;
}

// Auto-size columns
foreach(range('A', $lastCol) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Create the writer
$writer = new Xlsx($spreadsheet);

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $reportType . '_report_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

// Save file to PHP output
$writer->save('php://output');

$connect->close(); 