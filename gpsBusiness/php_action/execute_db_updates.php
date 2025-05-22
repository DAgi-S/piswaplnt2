<?php
require_once 'core.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['userId']) || $_SESSION['userRole'] !== 'admin') {
    http_response_code(401);
    echo json_encode(array(
        'success' => false,
        'message' => 'Unauthorized access. Please log in as admin.'
    ));
    exit();
}

$response = array(
    'success' => false,
    'messages' => array()
);

try {
    // Read the SQL file
    $sql_file = file_get_contents('db_updates_2024_fix.sql');
    
    if ($sql_file === false) {
        throw new Exception("Could not read SQL file");
    }

    // Split into individual queries
    $queries = array_filter(
        array_map(
            'trim',
            explode(';', $sql_file)
        ),
        'strlen'
    );

    // Execute each query
    foreach ($queries as $query) {
        if (!empty($query)) {
            if (!$connect->query($query)) {
                throw new Exception("Error executing query: " . $connect->error . "\nQuery: " . $query);
            }
        }
    }

    $response['success'] = true;
    $response['messages'][] = "Database updates executed successfully";

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
    error_log("Error in execute_db_updates.php: " . $e->getMessage());
}

// Close the database connection
$connect->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response); 