<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define BASEPATH to prevent direct access
define('BASEPATH', true);

// Include necessary files
require_once __DIR__ . '/../php_action/core.php';
require_once __DIR__ . '/../php_action/db_connect.php';

// Set headers
header('Content-Type: application/json; charset=UTF-8');

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        throw new Exception('User not logged in');
    }

    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get client ID from POST data
    $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
    
    if ($client_id <= 0) {
        throw new Exception('Invalid client ID');
    }

    // Prepare and execute query
    $query = "SELECT id, company_name, tin_number, address, phone, email 
             FROM clients 
             WHERE id = ? AND status = 1 
             LIMIT 1";
    
    $stmt = $connect->prepare($query);
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $connect->error);
    }

    $stmt->bind_param("i", $client_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Failed to get result set: " . $stmt->error);
    }

    $client_info = $result->fetch_assoc();
    
    if (!$client_info) {
        throw new Exception('Client not found or inactive');
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $client_info['id'],
            'company_name' => $client_info['company_name'],
            'tin_number' => $client_info['tin_number'],
            'address' => $client_info['address'],
            'phone' => $client_info['phone'],
            'email' => $client_info['email']
        ]
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Error in get_client_info.php: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 