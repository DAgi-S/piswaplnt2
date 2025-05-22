<?php
require_once 'php_action/core.php';

if(isset($_GET['id'])) {
    $letterId = $_GET['id'];
    
    // Fetch company settings
    $settingsQuery = "SELECT setting_key, setting_value FROM company_settings";
    $settingsResult = $connect->query($settingsQuery);
    
    // Initialize settings array
    $settings = array();
    
    // Convert result to key-value pairs
    if($settingsResult && $settingsResult->num_rows > 0) {
        while($row = $settingsResult->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    // Fetch letter details
    $sql = "SELECT gl.*, lt.letter_subject, lt.letter_content, lt.letter_for, lt.location 
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

    // Start HTML output
    $table = '<!DOCTYPE html>
    <html>
    <head>
        <title>View Letter</title>
        <style>
            @page {
                margin: 10mm 10mm 10mm 10mm;
            }
            body { 
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                font-size: 12px;
                counter-reset: page;
            }
            .preview-letter { 
                padding: 20px;
                max-width: 800px;
                margin: 0 auto;
                position: relative;
            }
            .letter-header {
                position: relative;
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 20px 40px;
                margin-bottom: 30px;
            }
            .logo-section {
                flex: 0 0 auto;
                width: 200px;
            }
            .logo-section img {
                max-width: 100px;
                height: auto;
                margin-top: 0;
            }
            .company-info {
                flex: 1;
                text-align: right;
                padding-left: 20px;
            }
            .company-info h2 {
                margin: 0 0 5px 0;
                font-size: 18px;
                font-weight: bold;
            }
            .company-info p {
                margin: 2px 0;
                font-size: 14px;
                line-height: 1.2;
            }
            .content-wrapper {
                margin-top: 20px;
                margin-bottom: 200px;
                padding: 0 40px;
            }
            .letter-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                padding: 20px 40px;
                text-align: center;
            }
            @media print {
                .no-print { display: none; }
                .letter-header {
                    position: relative;
                    break-after: avoid;
                }
                .letter-footer {
                    position: fixed;
                    bottom: 0;
                    break-before: avoid;
                }
                .content-wrapper {
                    page-break-inside: auto;
                }
                .vehicle-details {
                    page-break-inside: avoid;
                }
                /* Ensure header appears on every page */
                .letter-header {
                    display: flex;
                    page-break-before: always;
                }
                /* First page header shouldnt have a break before */
                .letter-header:first-of-type {
                    page-break-before: avoid;
                }
            }
            .vehicle-details {
                margin-top: 20px;
                width: 100%;
            }
            .vehicle-details table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            .vehicle-details th, 
            .vehicle-details td {
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
                font-size: 12px;
            }
            .vehicle-details th {
                background-color: #f5f5f5;
            }
            .letter-info {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                text-align: right;
                margin-bottom: 20px;
                padding-right: 40px;
            }
            .letter-date, .letter-ref {
                margin: 2px 0;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
    <div class="preview-letter">
        <div class="letter-header">
            <div class="logo-section">';
            if(!empty($settings['company_logo'])) {
                $table .= '<img src="uploads/'.$settings['company_logo'].'" alt="Company Logo">';
            }
            $table .= '</div>
            <div class="company-info">
                <h2>'.($settings['company_name'] ?? 'LEBAWI NET TRADING PLC').'</h2>
                <p>'.($settings['company_address'] ?? 'Addis Ababa, Ethiopia').'</p>
                <p>TIN: '.($settings['company_tin'] ?? '000').'</p>
                <p>'.($settings['company_website'] ?? 'www.lebawi.net').'</p>
            </div>
        </div>
        <div class="content-wrapper">';

        // Letter Information
        $table .= '<div class="letter-info">
            <div class="letter-date">Date: ' . date('F d, Y', strtotime($letter['created_at'])) . '</div>
            <div class="letter-ref">Ref: ' . $letter['reference_no'] . '</div>
        </div>';

        // Client Information
        // $table .= '<div class="client-info">
        //     <p>To: ' . $letter['client_name'] . '</p>';
        // if($letter['address']) {
        //     $table .= '<p>Address: ' . $letter['address'] . '</p>';
        // }
        // if($letter['phone']) {
        //     $table .= '<p>Phone: ' . $letter['phone'] . '</p>';
        // }
        // if($letter['tin_number']) {
        //     $table .= '<p>TIN: ' . $letter['tin_number'] . '</p>';
        // }
        // $table .= '</div>';

        // Letter Content
        $table .= '<div class="letter-subject">
            <h3>' . $letter['letter_for'] . '</h3>
        </div>
        <div class="letter-content">
            ' . nl2br($letter['location']) . '
        </div>';
        
      
        $table .= '<div class="letter-subject">
            <h3>' . $letter['letter_subject'] . '</h3>
        </div>'; 
 
        



        $table .= '<div class="letter-content">
            ' . nl2br($letter['letter_content']) . '
        </div>';

        $table .= '<div class="client-info">
            <p>ባለንብረት : ' . $letter['client_name'] . '</p>';
        if($letter['phone']) $table .= '<p>ስልክ: ' . $letter['phone'] . '</p>';
        $table .= '</div>';

        // Vehicle Details
        $table .= '<div class="vehicle-details">
            <h4>Vehicle Details:</h4>
            <table>
                <thead>
                    <tr>
                        <th>Plate Number</th>
                        <th>Trailer</th>
                        <th>Chassis</th>
                        <th>Motor</th>
                        <th>IMEI</th>
                    </tr>
                </thead>
                <tbody>';

        while($vehicle = $vehicles->fetch_assoc()) {
            $table .= '<tr>
                <td>'.($vehicle['plate_number'] ?? '').'</td>
                <td>'.($vehicle['trailer_plate'] ?? '').'</td>
                <td>'.($vehicle['chassis_number'] ?? '').'</td>
                <td>'.($vehicle['motor_number'] ?? '').'</td>
                <td>'.($vehicle['imei_number'] ?? '').'</td>
            </tr>';
        }

        $table .= '</tbody>
            </table>
        </div>';

        // Signature Section
        

        // Updated footer section
        $table .= '<div class="letter-footer">
            <div class="footer-image">';
            if(!empty($settings['company_stamp'])) {
                $table .= '<img src="uploads/'.$settings['company_stamp'].'" alt="Company Stamp">';
            }
            $table .= '</div>
            <div class="business-partner">YOUR BUSINESS PARTNER</div>
            <div class="footer-contact">
                +251924067895 | +251901000251<br>
                info.lebawi.new | www.lebawi.new<br>
                205, Rewina Building, 22 Square, Bole, Addis Ababa, Ethiopia
            </div>
        </div>';

        // Print Button
        $table .= '<div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">
                <i class="glyphicon glyphicon-print"></i> Print Letter
            </button>
        </div>';

        $table .= '</div></body></html>';

        echo $table;
    }
?> 