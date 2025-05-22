<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Validate token
    $headers = getallheaders();
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

    if (empty($token)) {
        throw new Exception('No token provided');
    }

    // Verify token
    $stmt = $pdo->prepare("
        SELECT u.id 
        FROM users u 
        JOIN user_tokens ut ON u.id = ut.user_id 
        WHERE ut.token = ? AND ut.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute([$token]);
    if (!$stmt->fetch()) {
        throw new Exception('Invalid or expired token');
    }

    // Get dashboard data
    $dashboardData = [
        'summary' => [
            'total_products' => 0,
            'total_orders' => 0,
            'total_purchases' => 0,
            'total_sales' => 0
        ],
        'recent_orders' => [],
        'recent_purchases' => [],
        'low_stock_products' => []
    ];

    // Get total products
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $dashboardData['summary']['total_products'] = $stmt->fetch()['count'];

    // Get total orders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $dashboardData['summary']['total_orders'] = $stmt->fetch()['count'];

    // Get total purchases
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM purchase_orders");
    $dashboardData['summary']['total_purchases'] = $stmt->fetch()['count'];

    // Get total sales amount
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders");
    $dashboardData['summary']['total_sales'] = $stmt->fetch()['total'];

    // Get recent orders
    $stmt = $pdo->query("
        SELECT o.id, o.order_number, o.total_amount, o.created_at, c.name as client_name
        FROM orders o
        LEFT JOIN clients c ON o.client_id = c.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $dashboardData['recent_orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent purchases
    $stmt = $pdo->query("
        SELECT po.id, po.purchase_number, po.total_amount, po.created_at, s.name as supplier_name
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.id
        ORDER BY po.created_at DESC
        LIMIT 5
    ");
    $dashboardData['recent_purchases'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get low stock products
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.quantity, p.min_quantity
        FROM products p
        WHERE p.quantity <= p.min_quantity
        ORDER BY p.quantity ASC
        LIMIT 5
    ");
    $dashboardData['low_stock_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $dashboardData
    ]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 