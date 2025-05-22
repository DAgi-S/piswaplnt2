<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => '',
    'data' => null
);

try {
    if (!isset($_POST['supplierId']) || empty($_POST['supplierId'])) {
        throw new Exception('Supplier ID is required');
    }

    $supplierId = intval($_POST['supplierId']);

    $sql = "SELECT id, company_name, contact_person, phone, email, address, tin, active 
            FROM suppliers 
            WHERE id = ?";
            
    $stmt = $connect->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $connect->error);
    }
    
    $stmt->bind_param("i", $supplierId);
    
    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['data'] = $result->fetch_assoc();
        $response['success'] = true;
    } else {
        throw new Exception("Supplier not found");
    }
    
    $stmt->close();

} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
    error_log("Error in fetchSelectedSupplierView.php: " . $e->getMessage());
}

if ($connect) {
    $connect->close();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); 