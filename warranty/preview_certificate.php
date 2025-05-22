<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for AJAX response
header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Define BASEPATH to prevent direct access
define('BASEPATH', true);

// Include necessary files
require_once __DIR__ . '/../php_action/core.php';
require_once __DIR__ . '/../php_action/db_connect.php';
require_once __DIR__ . '/warranty_functions.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        throw new Exception('User not logged in');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Process form data with default values for preview
    $data = [
        'client_id' => isset($_POST['client_id']) ? intval($_POST['client_id']) : 0,
        'company_name' => $_POST['company_name'] ?? '[Company Name]',
        'services_provided' => $_POST['services_provided'] ?? '[Services Provided]',
        'serial_reference' => $_POST['serial_reference'] ?? '[Serial/Reference Number]',
        'invoice_date' => !empty($_POST['invoice_date']) ? $_POST['invoice_date'] : date('Y-m-d'),
        'installation_date' => !empty($_POST['installation_date']) ? $_POST['installation_date'] : date('Y-m-d'),
        'certificate_date' => date('Y-m-d'),
        'warranty_duration' => isset($_POST['warranty_duration']) ? intval($_POST['warranty_duration']) : 12,
        'terms_conditions' => $_POST['terms_conditions'] ?? getDefaultTermsAndConditions(),
        'authorized_by' => $_POST['authorized_by'] ?? '[Authorized By]'
    ];

    // Get client information if client_id is provided
    $client = null;
    if ($data['client_id'] > 0) {
        $client = getClientInfo($data['client_id']);
    } else {
        // Use placeholder data for preview
        $client = [
            'company_name' => $data['company_name'],
            'tin_number' => '[TIN Number]',
            'address' => '[Address]',
            'phone' => '[Phone]',
            'email' => '[Email]'
        ];
    }

    // Generate a sample certificate number for preview
    $certificate_number = 'PREVIEW-' . generateCertificateNumber();

    // Generate preview HTML
    $html = '
    <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #333;">Lebawi Net Trading plc</h1>
            <h2 style="color: #666;">WARRANTY CERTIFICATE</h2>
            <p style="color: #888;"><strong>Certificate Number:</strong> ' . htmlspecialchars($certificate_number) . '</p>
            <p style="color: #999; font-style: italic;">Preview Mode</p>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd;"><strong>Company Name:</strong></td>
                <td style="width: 70%; padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($client['company_name']) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>TIN Number:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($client['tin_number']) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>Address:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($client['address']) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>Services Provided:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . nl2br(htmlspecialchars($data['services_provided'])) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>Serial/Reference Number:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($data['serial_reference']) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>Invoice Date:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . date('F j, Y', strtotime($data['invoice_date'])) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>Installation Date:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . date('F j, Y', strtotime($data['installation_date'])) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>Certificate Date:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . date('F j, Y', strtotime($data['certificate_date'])) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>Warranty Duration:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($data['warranty_duration']) . ' months</td>
            </tr>
        </table>

        <div style="margin: 20px 0;">
            <h3 style="color: #333;">Terms and Conditions</h3>
            <div style="text-align: justify; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
                ' . nl2br(htmlspecialchars($data['terms_conditions'])) . '
            </div>
        </div>

        <div style="margin-top: 40px; display: flex; justify-content: space-between;">
            <div style="width: 45%;">
                <strong>Authorized By:</strong><br>
                ' . htmlspecialchars($data['authorized_by']) . '<br>
                Lebawi Net Trading plc<br>
                Date: ' . date('F j, Y') . '
            </div>
            <div style="width: 45%; text-align: right;">
                <div style="border: 1px dashed #ccc; height: 100px; display: flex; align-items: center; justify-content: center;">
                    <em>Signature will appear here</em>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px; text-align: center; color: #888; font-size: 12px;">
            <p>This is a preview. The actual certificate may look slightly different.</p>
        </div>
    </div>';

    echo $html;

} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
} 