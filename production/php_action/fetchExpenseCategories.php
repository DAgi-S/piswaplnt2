<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php_action/core.php';
require_once '../php_action/db_connect.php';

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check database connection
    if ($connect->connect_error) {
        throw new Exception("Connection failed: " . $connect->connect_error);
    }

    $sql = "SELECT id, name FROM production_expense_categories WHERE status = 1 ORDER BY name ASC";
    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $connect->error);
    }

    $output = array();

    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $output[] = array(
                'id' => $row['id'],
                'name' => $row['name']
            );
        }
    }

    echo json_encode($output);

} catch (Exception $e) {
    error_log("Error in fetchExpenseCategories.php: " . $e->getMessage());
    echo json_encode(array(
        'error' => true,
        'message' => $e->getMessage()
    ));
} finally {
    if (isset($connect)) {
        $connect->close();
    }
} 