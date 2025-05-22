<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/response.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validate token
$token = validateToken();
if (!$token) {
    sendError('Unauthorized', 401);
    exit();
}

// Get user ID from token
$userId = $token['user_id'];

// Get order ID from URL
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$orderId) {
    sendError('Invalid order ID');
    exit();
}

try {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT 
            po.id,
            po.order_number,
            po.order_date,
            po.status,
            po.total_amount,
            po.notes,
            s.id as supplier_id,
            s.name as supplier_name,
            s.email as supplier_email,
            s.phone as supplier_phone,
            s.address as supplier_address
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.id
        WHERE po.id = :id AND po.created_by = :user_id
    ");
    
    $stmt->execute([
        ':id' => $orderId,
        ':user_id' => $userId
    ]);
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        sendError('Order not found');
        exit();
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT 
            poi.id,
            poi.product_id,
            p.name as product_name,
            poi.quantity,
            poi.unit_price,
            poi.total_price
        FROM purchase_order_items poi
        LEFT JOIN products p ON poi.product_id = p.id
        WHERE poi.purchase_order_id = :order_id
    ");
    
    $stmt->execute([':order_id' => $orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add items to order data
    $order['items'] = $items;
    
    sendSuccess($order);
    
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage());
} 