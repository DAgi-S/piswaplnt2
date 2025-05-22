<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Ensure database connection
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Sanitize and prepare filter inputs
$dateRange = isset($_GET['dateRange']) ? $_GET['dateRange'] : '';
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
$product = isset($_GET['product']) ? $_GET['product'] : '';
$orderStatus = isset($_GET['orderStatus']) ? $_GET['orderStatus'] : '';

// Build date filter based on selected range
$dateFilter = "";
switch($dateRange) {
    case 'today':
        $dateFilter = "DATE(po.start_date) = CURDATE()";
        break;
    case 'yesterday':
        $dateFilter = "DATE(po.start_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'last7days':
        $dateFilter = "DATE(po.start_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'last30days':
        $dateFilter = "DATE(po.start_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        break;
    case 'thisMonth':
        $dateFilter = "MONTH(po.start_date) = MONTH(CURDATE()) AND YEAR(po.start_date) = YEAR(CURDATE())";
        break;
    case 'lastMonth':
        $dateFilter = "DATE(po.start_date) >= DATE_SUB(DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE())-1 DAY), INTERVAL 1 MONTH) 
                      AND DATE(po.start_date) < DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE())-1 DAY)";
        break;
    case 'custom':
        if (!empty($startDate) && !empty($endDate)) {
            $startDate = $connect->real_escape_string($startDate);
            $endDate = $connect->real_escape_string($endDate);
            $dateFilter = "DATE(po.start_date) BETWEEN '$startDate' AND '$endDate'";
        }
        break;
    default:
        $dateFilter = "1=1"; // No date filter
}

// Build product filter
$productFilter = !empty($product) ? "AND po.product_id = " . $connect->real_escape_string($product) : "";

// Build status filter
$statusFilter = !empty($orderStatus) ? "AND po.status = '" . $connect->real_escape_string($orderStatus) . "'" : "";

// Main query
$sql = "SELECT 
            po.order_number,
            p.product_code,
            p.name as product_name,
            po.target_quantity,
            po.completed_quantity,
            po.start_date,
            po.expected_completion_date,
            po.actual_completion_date,
            po.status,
            COALESCE(SUM(pom.consumed_quantity * rm.cost_per_unit), 0) as total_cost
        FROM production_orders po
        LEFT JOIN production_products p ON po.product_id = p.id
        LEFT JOIN production_order_materials pom ON po.id = pom.production_order_id
        LEFT JOIN raw_materials rm ON pom.material_id = rm.id
        WHERE $dateFilter
        $productFilter
        $statusFilter
        GROUP BY po.id
        ORDER BY po.order_number DESC";

$result = $connect->query($sql);

if (!$result) {
    die("Error in query: " . $connect->error);
}

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator('Production System')
    ->setLastModifiedBy('Production System')
    ->setTitle('Production Orders Report')
    ->setSubject('Production Orders Report')
    ->setDescription('Production Orders Report generated on ' . date('Y-m-d H:i:s'));

// Set column headers
$sheet->setCellValue('A1', 'Order Number');
$sheet->setCellValue('B1', 'Product Code');
$sheet->setCellValue('C1', 'Product Name');
$sheet->setCellValue('D1', 'Target Quantity');
$sheet->setCellValue('E1', 'Completed Quantity');
$sheet->setCellValue('F1', 'Start Date');
$sheet->setCellValue('G1', 'Expected Completion');
$sheet->setCellValue('H1', 'Actual Completion');
$sheet->setCellValue('I1', 'Status');
$sheet->setCellValue('J1', 'Total Cost');
$sheet->setCellValue('K1', 'Efficiency');

// Style the header row
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

$sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

// Add data rows
$row = 2;
while ($data = $result->fetch_assoc()) {
    // Calculate efficiency
    $efficiency = 0;
    if ($data['target_quantity'] > 0) {
        $efficiency = ($data['completed_quantity'] / $data['target_quantity']) * 100;
        if ($data['status'] === 'completed' && 
            strtotime($data['actual_completion_date']) > strtotime($data['expected_completion_date'])) {
            $daysLate = ceil(
                (strtotime($data['actual_completion_date']) - strtotime($data['expected_completion_date'])) / (60 * 60 * 24)
            );
            $efficiency -= ($daysLate * 5); // Reduce 5% per day late
        }
        $efficiency = max(0, min(100, round($efficiency)));
    }

    $sheet->setCellValue('A' . $row, $data['order_number']);
    $sheet->setCellValue('B' . $row, $data['product_code']);
    $sheet->setCellValue('C' . $row, $data['product_name']);
    $sheet->setCellValue('D' . $row, $data['target_quantity']);
    $sheet->setCellValue('E' . $row, $data['completed_quantity']);
    $sheet->setCellValue('F' . $row, date('Y-m-d', strtotime($data['start_date'])));
    $sheet->setCellValue('G' . $row, date('Y-m-d', strtotime($data['expected_completion_date'])));
    $sheet->setCellValue('H' . $row, $data['actual_completion_date'] ? 
        date('Y-m-d', strtotime($data['actual_completion_date'])) : 'Not Completed');
    $sheet->setCellValue('I' . $row, strtoupper(str_replace('_', ' ', $data['status'])));
    $sheet->setCellValue('J' . $row, number_format($data['total_cost'], 2));
    $sheet->setCellValue('K' . $row, $efficiency . '%');

    // Style the row
    $rowStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ];
    $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($rowStyle);

    // Color code the efficiency cell
    $efficiencyColor = $efficiency >= 90 ? '70AD47' : // Green
                       $efficiency >= 70 ? 'FFB84D' : // Orange
                       'FF6B6B'; // Red
    $sheet->getStyle('K' . $row)->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->setStartColor(['rgb' => $efficiencyColor]);

    $row++;
}

// Auto-size columns
foreach(range('A','K') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Create the writer
$writer = new Xlsx($spreadsheet);

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Production_Orders_Report_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

// Save file to PHP output
$writer->save('php://output');

$connect->close(); 