<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

header('Content-Type: application/json');

try {
    $uploadDir = '../assets/images/company/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception("Failed to create upload directory");
        }
    }

    // Begin transaction
    $connect->begin_transaction();

    // Handle file uploads first
    $uploadErrors = [];
    
    // Handle company logo
    if(isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] != 4) {
        if($_FILES['company_logo']['error'] == 0) {
            // Validate file size (5MB limit)
            if($_FILES['company_logo']['size'] > 5000000) {
                $uploadErrors[] = "Company logo file is too large (max 5MB)";
            } else {
                $fileName = 'logo_' . time() . '.' . pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
                if(!move_uploaded_file($_FILES['company_logo']['tmp_name'], $uploadDir . $fileName)) {
                    $uploadErrors[] = "Failed to upload company logo";
                } else {
                    $logoPath = 'assets/images/company/' . $fileName;
                    $stmt = $connect->prepare("UPDATE company_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'company_logo'");
                    $stmt->bind_param("s", $logoPath);
                    $stmt->execute();
                    
                    if($stmt->affected_rows == 0) {
                        $stmt = $connect->prepare("INSERT INTO company_settings (setting_key, setting_value, created_at, updated_at) VALUES ('company_logo', ?, NOW(), NOW())");
                        $stmt->bind_param("s", $logoPath);
                        $stmt->execute();
                    }
                }
            }
        } else {
            $uploadErrors[] = "Error uploading company logo: " . error_get_last()['message'];
        }
    }
    
    // Similar handling for footer image
    if(isset($_FILES['footer_image']) && $_FILES['footer_image']['error'] != 4) {
        // ... (similar validation and handling as company logo)
    }

    // Validate required fields
    $requiredFields = ['company_name', 'company_phone', 'company_email'];
    $missingFields = [];
    
    foreach($requiredFields as $field) {
        if(!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $missingFields[] = ucwords(str_replace('_', ' ', $field));
        }
    }
    
    if(!empty($missingFields)) {
        throw new Exception("Required fields missing: " . implode(", ", $missingFields));
    }

    // Update text fields
    $settings = [
        'company_name',
        'company_tin',
        'company_phone',
        'company_email',
        'company_website',
        'company_address'
    ];

    foreach($settings as $key) {
        if(isset($_POST[$key])) {
            $value = trim($_POST[$key]);
            
            $stmt = $connect->prepare("UPDATE company_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            $stmt->bind_param("ss", $value, $key);
            $stmt->execute();
            
            if($stmt->affected_rows == 0) {
                $stmt = $connect->prepare("INSERT INTO company_settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                $stmt->bind_param("ss", $key, $value);
                $stmt->execute();
            }
        }
    }

    $connect->commit();
    
    $response = ['success' => true, 'messages' => 'Company settings updated successfully'];
    if(!empty($uploadErrors)) {
        $response['warnings'] = $uploadErrors;
    }
    
    echo json_encode($response);

} catch (Exception $e) {
    $connect->rollback();
    error_log('Error in updateCompanySettings.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'messages' => 'Error: ' . $e->getMessage()
    ]);
}

$connect->close(); 