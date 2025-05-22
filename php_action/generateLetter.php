<?php
require_once 'core.php';
require_once 'telegram_notification.php';

if($_POST) {
    // Get POST data
    $templateId = $_POST['templateId'];
    $clientName = $_POST['clientName'];
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $tinNumber = isset($_POST['tinNumber']) ? $_POST['tinNumber'] : '';
    $fsNumber = isset($_POST['fsNumber']) ? $_POST['fsNumber'] : '';
    
    // Generate reference number (GPS/FSNumber/Year)
    $referenceNo = 'GPS/FS/' . $fsNumber . '/' . date('Y');
    
    // Get vehicle arrays with validation
    $plates = isset($_POST['plate']) ? $_POST['plate'] : array();
    $trailers = isset($_POST['trailer']) ? $_POST['trailer'] : array();
    $chassis = isset($_POST['chassis']) ? $_POST['chassis'] : array();
    $motors = isset($_POST['motor']) ? $_POST['motor'] : array();
    $imeis = isset($_POST['imei']) ? $_POST['imei'] : array();

    // First save to generated_letters
    $sql = "INSERT INTO generated_letters (template_id, reference_no, client_name, phone, address, tin_number, fs_number, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("issssss", 
        $templateId,
        $referenceNo, 
        $clientName, 
        $phone, 
        $address, 
        $tinNumber,
        $fsNumber
    );
    
    if($stmt->execute()) {
        $letterId = $stmt->insert_id;
        
        // Save vehicle details
        $vehicleSql = "INSERT INTO letter_vehicles (letter_id, plate_number, trailer_plate, chassis_number, motor_number, imei_number, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $vehicleStmt = $connect->prepare($vehicleSql);
        
        foreach($plates as $i => $plate) {
            if(!empty($plate)) {
                $vehicleStmt->bind_param("isssss", 
                    $letterId,
                    $plate,
                    $trailers[$i],
                    $chassis[$i],
                    $motors[$i],
                    $imeis[$i]
                );
                $vehicleStmt->execute();
            }
        }
        $vehicleStmt->close();
        
        // Fetch template details for preview
        $sql = "SELECT * FROM letter_templates WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();

        // Generate HTML preview
        $htmlTemplate = '<!DOCTYPE html>
        <html>
        <head>
            <title>Letter Preview</title>
            <style>
                body { 
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    margin: 20px;
                }
                .letter-header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .letter-header img {
                    max-width: 200px;
                    height: auto;
                }
                .letter-info {
                    margin-bottom: 20px;
                }
                .client-info {
                    margin-bottom: 30px;
                }
                .letter-content {
                    margin-bottom: 30px;
                }
                .vehicle-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 30px;
                }
                .vehicle-table th, .vehicle-table td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                .signature-section {
                    margin-top: 50px;
                    text-align: center;
                }
                .signature-section img {
                    max-width: 150px;
                    height: auto;
                }
                @media print {
                    .no-print { display: none; }
                    body { margin: 0; padding: 15px; }
                }
            </style>
        </head>
        <body>
            <div class="letter-header">
                <img src="../assets/images/logo.png" alt="Company Logo">
                <h2>LEBAWI NET TRADING PLC</h2>
            </div>

            <div class="letter-info">
                <p>Date: '.date('F d, Y').'</p>
                <p>Ref: GPS/'.$fsNumber.'/'.date('Y').'</p>
            </div>

            <div class="client-info">
                <p>To: '.$clientName.'</p>';
                
        if($address) $htmlTemplate .= '<p>Address: '.$address.'</p>';
        if($phone) $htmlTemplate .= '<p>Phone: '.$phone.'</p>';
        if($tinNumber) $htmlTemplate .= '<p>TIN: '.$tinNumber.'</p>';

        $htmlTemplate .= '
            </div>

            <div class="letter-content">
                <h3>Subject: '.$template['letter_subject'].'</h3>
                <div>'.$template['letter_content'].'</div>
            </div>

            <table class="vehicle-table">
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

        foreach($plates as $i => $plate) {
            if(!empty($plate)) {
                $htmlTemplate .= '<tr>
                    <td>'.$plate.'</td>
                    <td>'.$trailers[$i].'</td>
                    <td>'.$chassis[$i].'</td>
                    <td>'.$motors[$i].'</td>
                    <td>'.$imeis[$i].'</td>
                </tr>';
            }
        }

        $htmlTemplate .= '</tbody>
            </table>

            <div class="signature-section">
                <img src="../uploads/signature.png" alt="Signature">
                <p>Manager</p>
            </div>

            <div class="no-print" style="text-align: center; margin-top: 20px;">
                <button onclick="window.print()">Print Letter</button>
                <a href="../generated_letters.php" class="btn btn-primary">Back to Letters</a>
            </div>
        </body>
        </html>';

        // Count number of vehicles (number of plate numbers submitted)
        $vehicleCount = count($_POST['plate']);

        // Format phone number
        if (!empty($phone)) {
            $phone = '+251' . $phone;
        }

        // Create Telegram notification message
        $telegramMessage = "📝 <b>New GPS Letter Generated</b>\n\n".
            "Date: " . date('d M Y') . "\n".
            "Reference #: " . (isset($_POST['fsNumber']) ? $_POST['fsNumber'] : 'N/A') . "\n".
            "Client: " . $_POST['clientName'] . "\n".
            "Phone: " . (!empty($phone) ? $phone : 'N/A') . "\n".
            "Number of Vehicles: " . $vehicleCount;

        // Send Telegram notification
        sendTelegramNotification($telegramMessage);

        echo $htmlTemplate;
    } else {
        echo "Error generating letter. Please try again.";
    }
    $stmt->close();
    exit();
}