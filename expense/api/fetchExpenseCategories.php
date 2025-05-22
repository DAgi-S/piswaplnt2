<?php
require_once '../../php_action/core.php';
require_once '../../php_action/db_connect.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        throw new Exception('User not authenticated');
    }

    // Query to get all active expense categories
    $query = "SELECT id, name, description, is_active 
              FROM expense_categories 
              WHERE is_active = 1 
              ORDER BY name ASC";
    
    $result = $connect->query($query);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $connect->error);
    }

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);

} catch (Exception $e) {
    error_log("Error in fetchExpenseCategories.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 