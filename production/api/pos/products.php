<?php
header('Content-Type: application/json');
require_once '../../../includes/header.php';
require_once '../../../php_action/core.php';

// Check if user is logged in
if(!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Only allow GET method
if($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get query parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    // Build query
    $sql = "SELECT p.*, c.category_name, b.brand_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            LEFT JOIN brands b ON p.brand_id = b.brand_id 
            WHERE p.active = 1 AND p.quantity > 0";
    
    $params = [];
    $types = "";
    
    if($search) {
        $sql .= " AND (p.product_name LIKE ? OR p.product_code LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }
    
    if($category) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category;
        $types .= "i";
    }
    
    $sql .= " ORDER BY p.product_name LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $connect->prepare($sql);
    
    if(!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM products p WHERE p.active = 1 AND p.quantity > 0";
    if($search) {
        $countSql .= " AND (p.product_name LIKE ? OR p.product_code LIKE ?)";
    }
    if($category) {
        $countSql .= " AND p.category_id = ?";
    }
    
    $countStmt = $connect->prepare($countSql);
    
    if(!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'products' => $products,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch products: ' . $e->getMessage()]);
} 