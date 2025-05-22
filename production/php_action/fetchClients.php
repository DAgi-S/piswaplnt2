<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'core.php';
require_once 'db_connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Prepare the SQL query
    $sql = "SELECT 
                id,
                company_name,
                tin_number,
                phone,
                email,
                address
            FROM clients
            WHERE status = 1
            ORDER BY company_name ASC";

    // Execute query
    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Error executing query: " . $connect->error);
    }

    // Fetch all clients
    $clients = array();
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $clients
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