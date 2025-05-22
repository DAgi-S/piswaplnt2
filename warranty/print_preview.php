<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for AJAX response
header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
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

    // Get data from either POST or GET
    $request = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

    // Process form data with default values for preview
    $data = [
        'client_id' => isset($request['client_id']) ? intval($request['client_id']) : 0,
        'company_name' => $request['company_name'] ?? '[Company Name]',
        'services_provided' => $request['services_provided'] ?? '',
        'serial_reference' => $request['serial_reference'] ?? '',
        'invoice_date' => !empty($request['invoice_date']) ? $request['invoice_date'] : date('Y-m-d'),
        'installation_date' => !empty($request['installation_date']) ? $request['installation_date'] : date('Y-m-d'),
        'certificate_date' => date('Y-m-d'),
        'warranty_duration' => isset($request['warranty_duration']) ? intval($request['warranty_duration']) : 12,
        'terms_conditions' => $request['terms_conditions'] ?? getDefaultTermsAndConditions(),
        'authorized_by' => $request['authorized_by'] ?? 'SALEM MOHAMMED'
    ];

    // Get client information if client_id is provided
    $client = null;
    if ($data['client_id'] > 0) {
        $client = getClientInfo($data['client_id']);
    } else {
        $client = [
            'company_name' => $data['company_name'],
            'tin_number' => '[TIN Number]',
            'address' => '[Address]',
            'phone' => '[Phone]',
            'email' => '[Email]'
        ];
    }

    // Generate certificate number
    $certificate_number = 'WC' . date('Y') . sprintf('%06d', rand(1, 999999));

    // CSS styles for the new design
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                background: #fff;
            }
            .certificate {
                max-width: 1000px;
                margin: 0 auto;
                background: #fff;
                padding: 40px;
                position: relative;
            }
            .header {
                text-align: center;
                margin-bottom: 50px;
            }
            .title {
                font-size: 48px;
                color: #333;
                margin: 0;
                font-weight: bold;
                text-transform: uppercase;
            }
            .subtitle {
                color: #B22222;
                font-size: 32px;
                margin: 10px 0;
                text-transform: uppercase;
            }
            .client-name {
                font-size: 36px;
                color: #B22222;
                margin: 30px 0;
            }
            .award-text {
                font-size: 18px;
                color: #333;
                margin: 20px 0;
            }
            .warranty-text {
                font-size: 16px;
                line-height: 1.6;
                color: #333;
                margin: 30px 0;
                text-align: justify;
            }
            .details {
                margin: 40px 0;
                display: flex;
                justify-content: space-between;
            }
            .signature-line {
                border-top: 2px solid #333;
                width: 250px;
                margin-top: 100px;
                text-align: center;
            }
            .signature-name {
                margin-top: 10px;
                font-weight: bold;
            }
            .signature-title {
                color: #666;
                font-size: 14px;
            }
            .seal {
                position: absolute;
                top: 20px;
                right: 40px;
                width: 150px;
                height: 150px;
            }
            .terms-section {
                margin-top: 40px;
                page-break-before: always;
            }
            .terms-title {
                color: #333;
                font-size: 24px;
                margin-bottom: 20px;
            }
            .terms-list {
                list-style-type: none;
                padding: 0;
            }
            .terms-list li {
                margin-bottom: 10px;
                font-size: 14px;
            }
            .contact-info {
                margin-top: 40px;
                text-align: center;
                color: #666;
            }
            .preview-watermark {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 100px;
                color: rgba(200, 200, 200, 0.2);
                pointer-events: none;
                z-index: 1000;
            }
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                }
                .certificate {
                    max-width: none;
                    margin: 0;
                    padding: 20px;
                }
                .page-break {
                    page-break-before: always;
                }
            }
        </style>
    </head>
    <body>
        ' . (isset($_GET['preview']) ? '<div class="preview-watermark">PREVIEW</div>' : '') . '
        <div class="certificate">
            <div class="header">
                <h1 class="title">CERTIFICATE</h1>
                <h2 class="subtitle">OF WARRANTY</h2>
            </div>

            <p class="award-text">THE FOLLOWING AWARD IS GIVEN TO</p>
            <p class="client-name">' . htmlspecialchars($client['company_name']) . '</p>

            <p class="warranty-text">
                Lebawi Net Trading plc warrants that the products and services provided shall be free from
                defects in materials, components, and workmanship for a period of ' . $data['warranty_duration'] . ' months from the
                date of delivery or installation, in accordance with the following conditions:
            </p>

            <div class="details">
                <div class="signature-block">
                    <div class="signature-line">
                        <div class="signature-name">SALEM MOHAMMED</div>
                        <div class="signature-title">MANAGING DIRECTOR</div>
                    </div>
                </div>
                <div class="signature-block">
                    <div class="signature-line">
                        <div class="signature-name">' . htmlspecialchars($data['authorized_by']) . '</div>
                        <div class="signature-title">PROJECT MANAGER</div>
                    </div>
                </div>
            </div>

            <div class="page-break"></div>

            <div class="terms-section">
                <h3 class="terms-title">Services Provided:</h3>
                <div class="terms-list">
                    ' . nl2br(htmlspecialchars($data['services_provided'])) . '
                </div>

                <h3 class="terms-title">Terms and Conditions:</h3>
                <div class="terms-list">
                    ' . nl2br(htmlspecialchars($data['terms_conditions'])) . '
                </div>

                <div class="contact-info">
                    <h3>Contact Us</h3>
                    <p>For support or warranty service, please contact:</p>
                    <p>📞 +251901000251 | +251901000279 | +251924067895</p>
                    <p>✉️ info@lebawinet.com | Salem@lebawi.net</p>
                    <p>🌐 www.lebawinet.com</p>
                    <p style="margin-top: 30px; font-size: 24px; color: #666;">YOUR BUSINESS PARTNER</p>
                </div>
            </div>
        </div>
    </body>
    </html>';

    echo $html;

} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
} 