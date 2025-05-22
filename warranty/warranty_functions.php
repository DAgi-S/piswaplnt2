<?php
// Check if BASEPATH is defined to prevent direct access
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// Include necessary files
require_once __DIR__ . '/../php_action/db_connect.php';
require_once __DIR__ . '/../php_action/core.php';

/**
 * Get list of clients for dropdown
 */
function getClientsList() {
    global $connect;
    $html = '';
    
    try {
        $query = "SELECT id,tin_number, company_name FROM clients";
        $result = $connect->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= sprintf(
                    '<option value="%d">%s - %s</option>',
                    $row['id'],
                    htmlspecialchars($row['tin_number']),
                    htmlspecialchars($row['company_name'])
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error in getClientsList: " . $e->getMessage());
    }
    
    return $html;
}

/**
 * Get default terms and conditions
 */
function getDefaultTermsAndConditions() {
    return "1. This warranty covers defects in materials and workmanship for the specified duration.
2. The warranty period starts from the date of installation/delivery.
3. This warranty does not cover:
   - Normal wear and tear
   - Damage caused by misuse or improper maintenance
   - Modifications made without authorization
   - Acts of nature or force majeure
4. All warranty claims must be submitted in writing with supporting documentation.
5. Lebawi Net Trading plc reserves the right to repair or replace defective parts at its discretion.
6. This warranty is non-transferable and applies only to the original purchaser.";
}

/**
 * Generate unique certificate number
 */
function generateCertificateNumber() {
    $prefix = 'WC';
    $year = date('Y');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefix . $year . $random;
}

/**
 * Save warranty certificate to database
 */
function saveWarrantyCertificate($data) {
    global $connect;
    
    try {
        $certificate_number = generateCertificateNumber();
        
        $query = "INSERT INTO warranty_certificates (
            certificate_number, client_id, company_name, services_provided,
            serial_reference, invoice_date, installation_date, certificate_date,
            warranty_duration, terms_conditions, authorized_by, signature_image,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $connect->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connect->error);
        }
        
        $stmt->bind_param(
            "sissssssiissi",
            $certificate_number,
            $data['client_id'],
            $data['company_name'],
            $data['services_provided'],
            $data['serial_reference'],
            $data['invoice_date'],
            $data['installation_date'],
            $data['certificate_date'],
            $data['warranty_duration'],
            $data['terms_conditions'],
            $data['authorized_by'],
            $data['signature_image'],
            $_SESSION['userId']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return $certificate_number;
    } catch (Exception $e) {
        error_log("Error in saveWarrantyCertificate: " . $e->getMessage());
        return false;
    }
}

/**
 * Get client information
 */
function getClientInfo($client_id) {
    global $connect;
    
    try {
        $query = "SELECT * FROM clients WHERE id = ?";
        $stmt = $connect->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connect->error);
        }
        
        $stmt->bind_param("i", $client_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    } catch (Exception $e) {
        error_log("Error in getClientInfo: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Handle signature image upload
 */
function handleSignatureUpload($file) {
    $target_dir = __DIR__ . "/../uploads/signatures/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    try {
        // Check if image file is a actual image
        if (!getimagesize($file["tmp_name"])) {
            throw new Exception("File is not an image.");
        }
        
        // Check file size (limit to 2MB)
        if ($file["size"] > 2000000) {
            throw new Exception("File is too large.");
        }
        
        // Allow certain file formats
        if ($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg") {
            throw new Exception("Only JPG, JPEG & PNG files are allowed.");
        }
        
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return $new_filename;
        }
        
        throw new Exception("Failed to move uploaded file.");
    } catch (Exception $e) {
        error_log("Error in handleSignatureUpload: " . $e->getMessage());
        return false;
    }
} 