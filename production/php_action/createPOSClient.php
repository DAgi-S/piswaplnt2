<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'core.php';
require_once 'db_connect.php';

// Set proper header for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (!isset($input['company_name']) || empty(trim($input['company_name']))) {
        throw new Exception('Company name is required');
    }

    // Sanitize input
    $company_name = $connect->real_escape_string(trim($input['company_name']));
    $phone = isset($input['phone']) ? $connect->real_escape_string(trim($input['phone'])) : '';
    $email = isset($input['email']) ? $connect->real_escape_string(trim($input['email'])) : '';

    // Validate email if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if company name already exists
    $check_sql = "SELECT id FROM clients WHERE company_name = ? AND status = 1";
    $check_stmt = $connect->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception("Database error: " . $connect->error);
    }

    $check_stmt->bind_param('s', $company_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        throw new Exception('A client with this company name already exists');
    }
    $check_stmt->close();

    // Insert new client
    $sql = "INSERT INTO clients (
                company_name, 
                phone, 
                email, 
                status, 
                created_at
            ) VALUES (?, ?, ?, 1, NOW())";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $connect->error);
    }

    $stmt->bind_param('sss', $company_name, $phone, $email);
    
    if (!$stmt->execute()) {
        throw new Exception("Error creating client: " . $stmt->error);
    }

    $client_id = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Client added successfully',
        'client' => [
            'id' => $client_id,
            'company_name' => $company_name,
            'phone' => $phone,
            'email' => $email
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($connect)) {
        $connect->close();
    }
} 