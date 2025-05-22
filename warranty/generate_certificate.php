<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define BASEPATH to prevent direct access
define('BASEPATH', true);

// Include necessary files
require_once __DIR__ . '/../php_action/core.php';
require_once __DIR__ . '/../php_action/db_connect.php';
require_once __DIR__ . '/warranty_functions.php';

// Include TCPDF library
require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        throw new Exception('User not logged in');
    }

    // Check if user has permission
    checkPermission('warranty.certificate.create');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Process form data
    $data = [
        'client_id' => isset($_POST['client_id']) ? intval($_POST['client_id']) : 0,
        'company_name' => $_POST['company_name'] ?? '',
        'services_provided' => $_POST['services_provided'] ?? '',
        'serial_reference' => $_POST['serial_reference'] ?? '',
        'invoice_date' => $_POST['invoice_date'] ?? '',
        'installation_date' => $_POST['installation_date'] ?? '',
        'certificate_date' => date('Y-m-d'),
        'warranty_duration' => isset($_POST['warranty_duration']) ? intval($_POST['warranty_duration']) : 12,
        'terms_conditions' => $_POST['terms_conditions'] ?? getDefaultTermsAndConditions(),
        'authorized_by' => $_POST['authorized_by'] ?? '',
        'signature_image' => null
    ];

    // Validate required fields
    $required_fields = ['client_id', 'company_name', 'services_provided', 'serial_reference', 'invoice_date', 'installation_date', 'authorized_by'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Required field missing: $field");
        }
    }

    // Validate dates
    $dates = ['invoice_date', 'installation_date'];
    foreach ($dates as $date_field) {
        if (!strtotime($data[$date_field])) {
            throw new Exception("Invalid date format for: $date_field");
        }
    }

    // Get client information before saving
    $client = getClientInfo($data['client_id']);
    if (!$client) {
        throw new Exception("Client information not found.");
    }

    // Handle signature upload if provided
    if (isset($_FILES['signature_image']) && $_FILES['signature_image']['error'] === UPLOAD_ERR_OK) {
        $signature = handleSignatureUpload($_FILES['signature_image']);
        if ($signature) {
            $data['signature_image'] = $signature;
        }
    }

    // Save certificate to database
    $certificate_number = saveWarrantyCertificate($data);
    if (!$certificate_number) {
        throw new Exception("Failed to generate warranty certificate.");
    }

    // Create PDF
    class WarrantyCertificatePDF extends TCPDF {
        public function Header() {
            $pageWidth = $this->getPageWidth();
            $this->SetY(10);
            
            // Logo - if you have one
            // $this->Image('path_to_logo.png', 15, 10, 30);
            
            // Company Name
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell($pageWidth, 10, 'Lebawi Net Trading plc', 0, 1, 'C');
            
            $this->Ln(5);
            
            // Title
            $this->SetFont('helvetica', 'B', 20);
            $this->Cell($pageWidth, 15, 'WARRANTY CERTIFICATE', 0, 1, 'C');
            
            $this->Ln(5);
        }
        
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
        }
    }

    // Create new PDF document
    $pdf = new WarrantyCertificatePDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Lebawi Net Trading plc');
    $pdf->SetAuthor('Lebawi Net Trading plc');
    $pdf->SetTitle('Warranty Certificate - ' . $certificate_number);

    // Set margins
    $pdf->SetMargins(15, 50, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 25);

    // Set font subsetting
    $pdf->setFontSubsetting(true);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 11);

    // Certificate content
    $html = '
    <div style="text-align:center;">
        <p><strong>Certificate Number:</strong> ' . htmlspecialchars($certificate_number) . '</p>
    </div>
    <br>
    <table cellpadding="5">
        <tr>
            <td width="30%"><strong>Company Name:</strong></td>
            <td width="70%">' . htmlspecialchars($client['company_name']) . '</td>
        </tr>
        <tr>
            <td><strong>TIN Number:</strong></td>
            <td>' . htmlspecialchars($client['tin_number']) . '</td>
        </tr>
        <tr>
            <td><strong>Address:</strong></td>
            <td>' . htmlspecialchars($client['address']) . '</td>
        </tr>
        <tr>
            <td><strong>Services Provided:</strong></td>
            <td>' . nl2br(htmlspecialchars($data['services_provided'])) . '</td>
        </tr>
        <tr>
            <td><strong>Serial/Reference Number:</strong></td>
            <td>' . htmlspecialchars($data['serial_reference']) . '</td>
        </tr>
        <tr>
            <td><strong>Invoice Date:</strong></td>
            <td>' . date('F j, Y', strtotime($data['invoice_date'])) . '</td>
        </tr>
        <tr>
            <td><strong>Installation Date:</strong></td>
            <td>' . date('F j, Y', strtotime($data['installation_date'])) . '</td>
        </tr>
        <tr>
            <td><strong>Certificate Date:</strong></td>
            <td>' . date('F j, Y', strtotime($data['certificate_date'])) . '</td>
        </tr>
        <tr>
            <td><strong>Warranty Duration:</strong></td>
            <td>' . htmlspecialchars($data['warranty_duration']) . ' months</td>
        </tr>
    </table>
    <br>
    <h3>Terms and Conditions</h3>
    <div style="text-align:justify;">
    ' . nl2br(htmlspecialchars($data['terms_conditions'])) . '
    </div>
    <br><br>
    <table cellpadding="5">
        <tr>
            <td width="50%">
                <strong>Authorized By:</strong><br>
                ' . htmlspecialchars($data['authorized_by']) . '<br>
                Lebawi Net Trading plc<br>
                Date: ' . date('F j, Y') . '
            </td>
            <td width="50%" style="text-align:right;">';

    if ($data['signature_image']) {
        $signaturePath = __DIR__ . '/../uploads/signatures/' . $data['signature_image'];
        if (file_exists($signaturePath)) {
            $imageData = base64_encode(file_get_contents($signaturePath));
            $html .= '<img src="@' . $imageData . '" style="max-width:150px; max-height:60px;">';
        }
    }

    $html .= '
            </td>
        </tr>
    </table>';

    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');

    // Close and output PDF document
    $pdf->Output('Warranty_Certificate_' . $certificate_number . '.pdf', 'D');
    exit;

} catch (Exception $e) {
    error_log("Error in generate_certificate.php: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 