<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['company_name']) || empty($input['company_name'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Company name is required'
    ]);
    exit;
}

try {
    // Prepare the SQL query
    $sql = "INSERT INTO clients (
                company_name,
                phone,
                email,
                status,
                created_at
            ) VALUES (?, ?, ?, 1, NOW())";

    // Prepare statement
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $connect->error);
    }

    // Bind parameters
    $stmt->bind_param(
        "sss",
        $input['company_name'],
        $input['phone'],
        $input['email']
    );

    // Execute query
    if (!$stmt->execute()) {
        throw new Exception("Error creating client: " . $stmt->error);
    }

    // Get the new client ID
    $clientId = $connect->insert_id;

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Client created successfully',
        'client_id' => $clientId
    ]);

} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Close database connection
$connect->close(); 