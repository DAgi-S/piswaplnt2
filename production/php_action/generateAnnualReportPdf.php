<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once '../vendor/autoload.php'; // For TCPDF

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('TO DREAM');
    $pdf->SetTitle('Annual Business Report ' . date('Y'));

    // Set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'TO DREAM', 'Annual Business Report ' . date('Y'));

    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Add a page
    $pdf->AddPage();

    // Get report data
    $data = getAnnualReportData($connect);

    // Executive Summary
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Executive Summary', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);
    
    $pdf->Cell(60, 10, 'Total Products:', 0, 0);
    $pdf->Cell(30, 10, number_format($data['summary']['totalProducts']), 0, 1);
    
    $pdf->Cell(60, 10, 'Total Revenue:', 0, 0);
    $pdf->Cell(30, 10, '$' . number_format($data['summary']['totalRevenue'], 2), 0, 1);
    
    $pdf->Cell(60, 10, 'Production Orders:', 0, 0);
    $pdf->Cell(30, 10, number_format($data['summary']['totalOrders']), 0, 1);
    
    $pdf->Cell(60, 10, 'Quality Rate:', 0, 0);
    $pdf->Cell(30, 10, number_format($data['summary']['qualityRate'], 1) . '%', 0, 1);

    $pdf->Ln(10);

    // Inventory Overview
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Inventory Overview', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);

    // Low Stock Table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Low Stock Alert', 0, 1, 'L');
    
    // Table header
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(80, 7, 'Product', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Current Stock', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Min Level', 1, 1, 'C', true);

    // Table data
    $pdf->SetFont('helvetica', '', 12);
    foreach ($data['inventory']['lowStock'] as $item) {
        $pdf->Cell(80, 6, $item['name'], 1, 0, 'L');
        $pdf->Cell(40, 6, $item['current_stock'], 1, 0, 'C');
        $pdf->Cell(40, 6, $item['min_stock_level'], 1, 1, 'C');
    }

    $pdf->Ln(10);

    // Production Analysis
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Production Analysis', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);

    $pdf->Cell(60, 10, 'Production Efficiency:', 0, 0);
    $pdf->Cell(30, 10, number_format($data['production']['efficiency'], 1) . '%', 0, 1);

    $pdf->Ln(10);

    // Quality Control
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Quality Control', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);

    // Quality Metrics Table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Monthly Quality Metrics', 0, 1, 'L');
    
    // Table header
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(60, 7, 'Month', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Pass Rate', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Fail Rate', 1, 1, 'C', true);

    // Table data
    $pdf->SetFont('helvetica', '', 12);
    $metrics = $data['quality']['metrics'];
    for ($i = 0; $i < count($metrics['labels']); $i++) {
        $pdf->Cell(60, 6, $metrics['labels'][$i], 1, 0, 'L');
        $pdf->Cell(40, 6, number_format($metrics['passRate'][$i], 1) . '%', 1, 0, 'C');
        $pdf->Cell(40, 6, number_format($metrics['failRate'][$i], 1) . '%', 1, 1, 'C');
    }

    $pdf->Ln(10);

    // Financial Overview
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Financial Overview', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);

    $pdf->Cell(60, 10, 'Total Sales:', 0, 0);
    $pdf->Cell(30, 10, '$' . number_format($data['financial']['totalSales'], 2), 0, 1);
    
    $pdf->Cell(60, 10, 'Total Purchases:', 0, 0);
    $pdf->Cell(30, 10, '$' . number_format($data['financial']['totalPurchases'], 2), 0, 1);
    
    $pdf->Cell(60, 10, 'Net Profit:', 0, 0);
    $pdf->Cell(30, 10, '$' . number_format($data['financial']['netProfit'], 2), 0, 1);

    // Footer
    $pdf->SetY(-50);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'Report generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
    $pdf->Cell(0, 10, 'Generated by: ' . $_SESSION['userName'], 0, 1, 'L');

    // Output the PDF
    $pdf->Output('Annual_Business_Report_' . date('Y') . '.pdf', 'D');

} catch (Exception $e) {
    error_log("Error in generateAnnualReportPdf.php: " . $e->getMessage());
    echo json_encode(['status' => false, 'message' => 'Failed to generate PDF report']);
}

// Helper function to get report data
function getAnnualReportData($connect) {
    return [
        'summary' => getExecutiveSummary($connect),
        'inventory' => getInventoryData($connect),
        'production' => getProductionData($connect),
        'quality' => getQualityData($connect),
        'financial' => getFinancialData($connect),
        'movement' => getMovementData($connect)
    ];
}

// Include the data fetching functions from getAnnualReportData.php
require_once 'getAnnualReportData.php'; 