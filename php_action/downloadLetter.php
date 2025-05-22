<?php
require_once 'core.php';
require_once __DIR__ . '/../libraries/fpdf/fpdf.php';

if(isset($_GET['id'])) {
    $letterId = $_GET['id'];
    
    // Fetch letter details
    $sql = "SELECT gl.*, lt.letter_subject, lt.letter_content 
            FROM generated_letters gl
            LEFT JOIN letter_templates lt ON gl.template_id = lt.id
            WHERE gl.id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $letterId);
    $stmt->execute();
    $result = $stmt->get_result();
    $letter = $result->fetch_assoc();
    
    // Fetch vehicle details
    $vehicleSql = "SELECT * FROM letter_vehicles WHERE letter_id = ?";
    $vehicleStmt = $connect->prepare($vehicleSql);
    $vehicleStmt->bind_param("i", $letterId);
    $vehicleStmt->execute();
    $vehicles = $vehicleStmt->get_result();
    
    // Generate PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Add company logo and header
    $pdf->Image('../assets/images/logo.png', 10, 10, 30);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'LEBAWI NET TRADING PLC', 0, 1, 'C');
    
    // Add letter details
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Date: ' . date('F d, Y', strtotime($letter['generated_date'])), 0, 1, 'R');
    $pdf->Cell(0, 10, 'Ref: GPS/'.$letter['fs_number'].'/'.date('Y', strtotime($letter['generated_date'])), 0, 1, 'L');
    
    // Add client details
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'To: ' . $letter['client_name'], 0, 1, 'L');
    if($letter['address']) $pdf->Cell(0, 10, 'Address: ' . $letter['address'], 0, 1, 'L');
    if($letter['phone']) $pdf->Cell(0, 10, 'Phone: ' . $letter['phone'], 0, 1, 'L');
    if($letter['tin_number']) $pdf->Cell(0, 10, 'TIN: ' . $letter['tin_number'], 0, 1, 'L');
    
    // Add subject
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Subject: ' . $letter['letter_subject'], 0, 1, 'L');
    
    // Add vehicle details in table format
    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Vehicle Details:', 0, 1, 'L');
    
    // Table headers
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 10, 'Plate Number', 1);
    $pdf->Cell(40, 10, 'Trailer', 1);
    $pdf->Cell(40, 10, 'Chassis', 1);
    $pdf->Cell(40, 10, 'Motor', 1);
    $pdf->Cell(30, 10, 'IMEI', 1);
    $pdf->Ln();
    
    // Table data
    $pdf->SetFont('Arial', '', 10);
    while($vehicle = $vehicles->fetch_assoc()) {
        $pdf->Cell(40, 10, $vehicle['plate_number'], 1);
        $pdf->Cell(40, 10, $vehicle['trailer_plate'], 1);
        $pdf->Cell(40, 10, $vehicle['chassis_number'], 1);
        $pdf->Cell(40, 10, $vehicle['motor_number'], 1);
        $pdf->Cell(30, 10, $vehicle['imei_number'], 1);
        $pdf->Ln();
    }
    
    // Add letter content
    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 12);
    $pdf->MultiCell(0, 10, $letter['letter_content'], 0, 'L');
    
    // Output PDF as download
    $pdf->Output('D', 'GPS_Letter_'.$letter['fs_number'].'.pdf');
    
    $stmt->close();
    $vehicleStmt->close();
    $connect->close();
} 