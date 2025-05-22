<?php
require_once 'core.php';

// Check if user has admin role
if(!isset($_SESSION['roleId']) || $_SESSION['roleId'] !== 1) {
    $response = array('success' => false, 'message' => 'Access denied. Admin privileges required.');
    echo json_encode($response);
    exit();
}

$response = array('success' => false, 'message' => '');

// Validate and sanitize input
$companyName = isset($_POST['company_name']) ? $connect->real_escape_string($_POST['company_name']) : '';
$companyAddress = isset($_POST['company_address']) ? $connect->real_escape_string($_POST['company_address']) : '';
$companyPhone = isset($_POST['company_phone']) ? $connect->real_escape_string($_POST['company_phone']) : '';
$companyEmail = isset($_POST['company_email']) ? $connect->real_escape_string($_POST['company_email']) : '';
$companyWebsite = isset($_POST['company_website']) ? $connect->real_escape_string($_POST['company_website']) : '';
$companyTin = isset($_POST['company_tin']) ? $connect->real_escape_string($_POST['company_tin']) : '';

// Validate input
if(empty($companyName)) {
    $response['message'] = 'Company name is required.';
    echo json_encode($response);
    exit();
}

// Start transaction
$connect->begin_transaction();

try {
    // Handle logo upload if present
    $logoPath = null;
    if(isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === 0) {
        $allowedTypes = array('image/jpeg', 'image/png', 'image/gif');
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if(!in_array($_FILES['company_logo']['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG and GIF files are allowed.');
        }
        
        if($_FILES['company_logo']['size'] > $maxSize) {
            throw new Exception('File size too large. Maximum size allowed is 5MB.');
        }
        
        $extension = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $extension;
        $uploadPath = '../assets/images/company/' . $filename;
        
        // Create directory if it doesn't exist
        if(!file_exists('../assets/images/company')) {
            mkdir('../assets/images/company', 0777, true);
        }
        
        if(move_uploaded_file($_FILES['company_logo']['tmp_name'], $uploadPath)) {
            $logoPath = $filename;
            
            // Delete old logo if exists
            $sql = "SELECT setting_value FROM company_settings WHERE setting_key = 'company_logo'";
            $result = $connect->query($sql);
            if($result->num_rows > 0) {
                $oldLogo = $result->fetch_array()[0];
                if($oldLogo && file_exists('../assets/images/company/' . $oldLogo)) {
                    unlink('../assets/images/company/' . $oldLogo);
                }
            }
        } else {
            throw new Exception('Error uploading file.');
        }
    }

    // Update settings
    $settings = array(
        'company_name' => $companyName,
        'company_address' => $companyAddress,
        'company_phone' => $companyPhone,
        'company_email' => $companyEmail,
        'company_website' => $companyWebsite,
        'company_tin' => $companyTin
    );

    if($logoPath) {
        $settings['company_logo'] = $logoPath;
    }

    foreach($settings as $key => $value) {
        $sql = "INSERT INTO company_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('ss', $key, $value);
        
        if(!$stmt->execute()) {
            throw new Exception($connect->error);
        }
        $stmt->close();
    }

    // Commit transaction
    $connect->commit();
    
    $response['success'] = true;
    $response['message'] = 'Company information updated successfully.';
    if($logoPath) {
        $response['logo'] = $logoPath;
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    $response['message'] = 'Error occurred while updating company information: ' . $e->getMessage();
}

// Close database connection
$connect->close();

header('Content-Type: application/json');
echo json_encode($response); 