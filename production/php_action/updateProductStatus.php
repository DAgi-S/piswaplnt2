<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

try {
    // Include database connection
    require_once '../includes/db_connect.php';

    // Clear any previous output
    ob_clean();

    // Set proper content type
    header('Content-Type: application/json');

    // Check if required parameters are present
    if (!isset($_POST['id']) || !isset($_POST['status'])) {
        throw new Exception("Missing required parameters");
    }

    // Validate status
    $validStatuses = array('active', 'inactive');
    if (!in_array($_POST['status'], $validStatuses)) {
        throw new Exception("Invalid status value");
    }

    // Prepare and execute the update query
    $sql = "UPDATE production_products SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $connect->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $connect->error);
    }

    $stmt->execute(array($_POST['status'], $_POST['id']));

    if ($stmt->rowCount() === 0) {
        throw new Exception("No product found with ID: " . $_POST['id']);
    }

    // Send success response
    echo json_encode(array(
        'success' => true,
        'messages' => 'Product status updated successfully'
    ));

} catch (Exception $e) {
    // Log the error
    error_log("Error in updateProductStatus.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    // Clear any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Send error response
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'messages' => $e->getMessage()
    ));
}

// End output buffering and send response
while (ob_get_level()) {
    ob_end_clean();
}
exit; 