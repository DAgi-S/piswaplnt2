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

    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Database error: " . $connect->error);
    }

    $clients = array();
    while ($row = $result->fetch_assoc()) {
        // Sanitize output
        foreach ($row as $key => $value) {
            $row[$key] = htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
        }
        $clients[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $clients
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