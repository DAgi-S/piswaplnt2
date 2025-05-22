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
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    // Build query
    $sql = "SELECT c.* FROM clients c WHERE c.active = 1";
    
    $params = [];
    $types = "";
    
    if($search) {
        $sql .= " AND (c.client_name LIKE ? OR c.client_code LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ssss";
    }
    
    $sql .= " ORDER BY c.client_name LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $connect->prepare($sql);
    
    if(!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $clients = [];
    while($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM clients c WHERE c.active = 1";
    if($search) {
        $countSql .= " AND (c.client_name LIKE ? OR c.client_code LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)";
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
            'clients' => $clients,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch clients: ' . $e->getMessage()]);
} 