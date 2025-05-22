<?php
require_once 'core.php';

if(isset($_GET['id'])) {
    $letterId = $_GET['id'];
    
    // Fetch company settings
    $settingsQuery = "SELECT setting_key, setting_value FROM company_settings";
    $settingsResult = $connect->query($settingsQuery);
    $settings = array();
    while($row = $settingsResult->fetch_array()) {
        $settings[$row['setting_key']] = $row['setting_value'];
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
    $vehicleSql = "SELECT 
        plate_number,
        trailer_plate as trailer,
        chassis_number as chassis,
        motor_number as motor,
        imei_number as imei 
        FROM letter_vehicles 
        WHERE letter_id = ?";
    $vehicleStmt = $connect->prepare($vehicleSql);
    $vehicleStmt->bind_param("i", $letterId);
    $vehicleStmt->execute();
    $vehicles = $vehicleStmt->get_result();

    $table = '<!DOCTYPE html>
    <html>
    <head>
        <title>View Letter</title>
        <style>
            body { 
                font-family: Arial, sans-serif;
                margin: 20px;
                padding: 20px;
                font-size: 18px;
            }
            .preview-letter { 
                padding: 40px;
                max-width: 800px;
                margin: 0 auto;
                font-size: 18px;
            }
            .letter-header { 
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 1px solid #ddd;
                padding-bottom: 20px;
            }
            .company-logo img { 
                height: 100px;
                margin-bottom: 15px;
            }
            .company-info h2 { 
                margin: 10px 0;
                font-size: 22px;
            }
            .letter-info { 
                display: flex;
                justify-content: space-between;
                margin: 25px 0;
                font-size: 22px;
            }
            .client-info {
                margin: 25px 0;
                font-size: 22px;
            }
            .client-info div {
                margin: 5px 0;
                font-size: 22px;
            }
            .client-details {
                margin-left: 30px;
                font-size: 22px;
            }
            .letter-subject {
                margin: 25px 0;
                font-size: 22px;
            }
            .letter-subject h2 {
                font-size: 20px;
                font-weight: bold;
            }
            .vehicle-details {
                margin: 30px 0;
                font-size: 22px;
            }
            .vehicle-details table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
                font-size: 22px;
            }
            .vehicle-details th, .vehicle-details td {
                border: 1px solid #000;
                padding: 10px;
                text-align: left;
                font-size: 22px;
            }
            .vehicle-details th {
                background-color: #f5f5f5;
                font-size: 22px;
            }
            .letter-footer {
                margin-top: 800px;
                text-align: center;
                border-top: 1px solid #ddd;
                padding-top: 20px;
            }
            .footer-image img {
                max-width: 200%;
                height: auto;
                margin-top: 20px;
            }
            @media print {
                .no-print { display: none; }
                body { margin: 0; padding: 15px; }
            }
        </style>
    </head>
    <body>
    <div class="preview-letter">';

    // Header with company info
    $table .= '<div class="letter-header">';
    if(!empty($settings['company_logo'])) {
        $table .= '<img src="http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/../uploads/'.$settings['company_logo'].'" alt="Company Logo" class="logo">';
    }
    
  
    $table .= '</div></div>';

    // Letter Information
    $table .= '<div class="letter-info">
        <div class="letter-date">Date: ' . date('F d, Y', strtotime($letter['generated_date'])) . '</div>
        <div class="letter-ref">Ref: GPS/FS/'.$letter['fs_number'].'/'.date('Y', strtotime($letter['generated_date'])).'</div>
    </div>';

    // Client Information
    $table .= '<div class="client-info">
        <div class="to-section"><strong></strong> '. $letter['letter_for']. '</div>
        <div class="client-details">
            <div>'. $letter['location']. '</div>';

    $table .= '</div></div>';

    // Subject and Content
    $table .= '<div class="letter-subject">
        <h3>Subject: ' . $letter['letter_subject'] . '</h3>
    </div>
    <div class="letter-content">
        <p><h3>' . nl2br($letter['letter_content']) . '</h3></p>
    </div>';

    // Vehicle Details Table
    $table .= '<div class="vehicle-details">
        <h4>Vehicle Details:</h4>
        <table>
            <thead><tr>
                <th width="20%">Plate Number</th>
                <th width="20%">Trailer</th>
                <th width="20%">Chassis</th>
                <th width="20%">Motor</th>
                <th width="20%">IMEI</th>
            </tr></thead>
            <tbody>';

    while($vehicle = $vehicles->fetch_assoc()) {
        $table .= '<tr>
            <td>'.($vehicle['plate_number'] ?? 'N/A').'</td>
            <td>'.($vehicle['trailer'] ?? 'N/A').'</td>
            <td>'.($vehicle['chassis'] ?? 'N/A').'</td>
            <td>'.($vehicle['motor'] ?? 'N/A').'</td>
            <td>'.($vehicle['imei'] ?? 'N/A').'</td>
        </tr>';
    }

    $table .= '</tbody></table></div>';

    // Footer
    $table .= '<div class="letter-footer">';
    if(!empty($settings['footer_image'])) {
        $table .= '<img src="http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/../uploads/'.$settings['footer_image'].'" alt="Footer Image">';
    }
    $table .= '</div>';

    // Print button
    $table .= '<div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">
            <i class="glyphicon glyphicon-print"></i> Print Letter
        </button>
    </div>';

    $table .= '</div></body></html>';

    $connect->close();
    echo $table;
}
?>