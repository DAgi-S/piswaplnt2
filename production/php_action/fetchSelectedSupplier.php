<?php
require_once 'core.php';

// Set headers to prevent caching and ensure proper JSON response
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Initialize response array
$response = array(
    'success' => false,
    'messages' => '',
    'data' => null
);

try {
    // Validate supplier ID
    if (!isset($_POST['supplierId']) || empty($_POST['supplierId'])) {
        throw new Exception('Supplier ID is required');
    }

    $supplierId = intval($_POST['supplierId']);

    // Prepare and execute query
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
    error_log("Error in fetchSelectedSupplier.php: " . $e->getMessage());
}

// Close database connection
if ($connect) {
    $connect->close();
}

// Ensure clean output
if (ob_get_length()) ob_clean();
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); 