<?php
require_once 'core.php';

// Set headers to prevent caching and ensure proper JSON response
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$response = array(
    'success' => false,
    'messages' => ''
);

try {
    if (!isset($_POST['supplierId']) || empty($_POST['supplierId'])) {
        throw new Exception('Supplier ID is required');
    }

    $supplierId = intval($_POST['supplierId']);
    $companyName = $_POST['editCompanyName'];
    $contactPerson = $_POST['editContactPerson']; 
    $phone = $_POST['editPhone'];
    $email = $_POST['editEmail'];
    $address = $_POST['editAddress'];
    $tin = $_POST['editTin'];
    $status = $_POST['editStatus'] === 'active' ? 1 : 0;

    $sql = "UPDATE suppliers 
            SET company_name = ?, 
                contact_person = ?, 
                phone = ?, 
                email = ?, 
                address = ?, 
                tin = ?,
                active = ?,
                updated_at = NOW()
            WHERE id = ?";

    $stmt = $connect->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $connect->error);
    }
    
    $stmt->bind_param('ssssssii', 
        $companyName, 
        $contactPerson, 
        $phone, 
        $email, 
        $address, 
        $tin, 
        $status, 
        $supplierId
    );
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = "Successfully updated supplier";    
    } else {
        throw new Exception("Error executing statement: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
    error_log("Error in editSupplier.php: " . $e->getMessage());
}

// Close database connection
if ($connect) {
    $connect->close();
}

// Ensure clean output
if (ob_get_length()) ob_clean();
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); 