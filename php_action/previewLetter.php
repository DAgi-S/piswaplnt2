<?php
require_once 'core.php';

if($_POST) {
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

    // Get POST data
    $templateId = $_POST['templateId'];
    $clientName = $_POST['clientName'];
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $tinNumber = isset($_POST['tinNumber']) ? $_POST['tinNumber'] : '';
    $fsNumber = isset($_POST['fsNumber']) ? $_POST['fsNumber'] : '';
    
    // Get vehicle arrays
    $plates = isset($_POST['plate']) ? $_POST['plate'] : array();
    $trailers = isset($_POST['trailer']) ? $_POST['trailer'] : array();
    $chassis = isset($_POST['chassis']) ? $_POST['chassis'] : array();
    $motors = isset($_POST['motor']) ? $_POST['motor'] : array();
    $imeis = isset($_POST['imei']) ? $_POST['imei'] : array();

    // Fetch template details
    $sql = "SELECT * FROM letter_templates WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $templateId);
    $stmt->execute();
    $result = $stmt->get_result();
    $template = $result->fetch_assoc();
    
    // Generate HTML preview with improved structure
    echo '<div class="preview-letter">';
    
    // Header Section with better alignment
    echo '<div class="letter-header">';
    if(!empty($settings['company_logo'])) {
        echo '<div class="company-logo">';
        echo '<img src="http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/../uploads/'.$settings['company_logo'].'" alt="Company Logo">';
        echo '</div>';
    }
    
    echo '<div class="company-info">';
    echo '<h2>'.$settings['company_name'].'</h2>';
    echo '<p>'.$settings['company_address'].'</p>';
    echo '<p>Tel: '.$settings['company_phone'].'</p>';
    if(!empty($settings['company_tin'])) {
        echo '<p>TIN: '.$settings['company_tin'].'</p>';
    }
    echo '</div>';
    echo '</div>';

    // Letter Information with better spacing
    echo '<div class="letter-info">';
    echo '<div class="letter-date">Date: ' . date('F d, Y') . '</div>';
    echo '<div class="letter-ref">Ref: GPS/'.$fsNumber.'/'.date('Y').'</div>';
    echo '</div>';
    
    // Client Information with improved formatting
    echo '<div class="client-info">';
    echo '<div class="to-section"><strong>To:</strong> '. $template['letter_for']. '</div>';
    echo '<div class="address-section">'. $template['location']. '</div>';
    echo '</div>';
    
    // Subject with better emphasis
    echo '<div class="letter-subject">';
    echo '<h3>Subject: ' . $template['letter_subject'] . '</h3>';
    echo '</div>';
    
    // Letter Content with improved readability
    echo '<div class="letter-content">';
    echo '<p>' . nl2br($template['letter_content']) . '</p>';
    echo '</div>';

    // Vehicle Details Table with better structure
    echo '<div class="vehicle-details">';
    echo '<h4>Vehicle Details:</h4>';
    echo '<table class="table table-bordered">';
    echo '<thead><tr>';
    echo '<th width="20%">Plate Number</th>';
    echo '<th width="20%">Trailer</th>';
    echo '<th width="20%">Chassis</th>';
    echo '<th width="20%">Motor</th>';
    echo '<th width="20%">IMEI</th>';
    echo '</tr></thead><tbody>';
    
    foreach($plates as $i => $plate) {
        if(!empty($plate)) {
            echo '<tr>';
            echo '<td>'.$plate.'</td>';
            echo '<td>'.$trailers[$i].'</td>';
            echo '<td>'.$chassis[$i].'</td>';
            echo '<td>'.$motors[$i].'</td>';
            echo '<td>'.$imeis[$i].'</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';
    echo '</div>';

    // Footer with improved styling
    echo '<div class="letter-footer">';
    if(!empty($settings['footer_image'])) {
        echo '<div class="footer-image">';
        echo '<img src="http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/../uploads/'.$settings['footer_image'].'" alt="Footer Image">';
        echo '</div>';
    }
    echo '</div>';
    
    echo '</div>';
}