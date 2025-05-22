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
    // Prepare the SQL query with proper joins and conditions
    $sql = "SELECT 
                p.id as product_id,
                p.product_code,
                p.name,
                p.selling_price,
                p.current_stock,
                p.category_id,
                p.description,
                p.unit,
                c.name as category_name
            FROM production_products p
            LEFT JOIN production_categories c ON p.category_id = c.id
            WHERE p.status = 'active' 
            ORDER BY p.name ASC";

    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Database error: " . $connect->error);
    }

    $products = array();
    while ($row = $result->fetch_assoc()) {
        // Format numeric values
        $row['selling_price'] = number_format((float)$row['selling_price'], 2, '.', '');
        $row['current_stock'] = (float)$row['current_stock']; // Changed to float since it's decimal in DB
        
        // Add a default placeholder image since we don't have product images
        $row['product_image'] = 'data:image/svg+xml;base64,' . base64_encode('<?xml version="1.0" encoding="UTF-8"?><svg width="100" height="100" version="1.1" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="#f0f0f0"/><text x="50" y="50" text-anchor="middle" dominant-baseline="middle" fill="#999">No Image</text></svg>');
        
        $products[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $products
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