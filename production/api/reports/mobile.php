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

// Get report type and parameters
$type = isset($_GET['type']) ? $_GET['type'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

try {
    switch ($type) {
        case 'sales_summary':
            $data = getSalesSummary($dateFrom, $dateTo);
            break;
        case 'inventory_status':
            $data = getInventoryStatus();
            break;
        case 'purchase_summary':
            $data = getPurchaseSummary($dateFrom, $dateTo);
            break;
        case 'production_summary':
            $data = getProductionSummary($dateFrom, $dateTo);
            break;
        default:
            sendError('Invalid report type');
            exit();
    }
    
    sendSuccess($data);
    
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage());
}

function getSalesSummary($dateFrom, $dateTo) {
    global $pdo;
    
    // Get total sales
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            SUM(paid_amount) as total_paid,
            AVG(total_amount) as average_order_value
        FROM sales_orders
        WHERE order_date BETWEEN :date_from AND :date_to
    ");
    
    $stmt->execute([
        ':date_from' => $dateFrom,
        ':date_to' => $dateTo
    ]);
    
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get sales by status
    $stmt = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as amount
        FROM sales_orders
        WHERE order_date BETWEEN :date_from AND :date_to
        GROUP BY status
    ");
    
    $stmt->execute([
        ':date_from' => $dateFrom,
        ':date_to' => $dateTo
    ]);
    
    $summary['status_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $summary;
}

function getInventoryStatus() {
    global $pdo;
    
    // Get inventory summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_products,
            SUM(quantity) as total_quantity,
            SUM(quantity * unit_price) as total_value
        FROM products
        WHERE status = 'active'
    ");
    
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get low stock items
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.quantity,
            p.min_quantity,
            p.unit_price
        FROM products p
        WHERE p.quantity <= p.min_quantity
        AND p.status = 'active'
        ORDER BY p.quantity ASC
        LIMIT 10
    ");
    
    $stmt->execute();
    $summary['low_stock_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $summary;
}

function getPurchaseSummary($dateFrom, $dateTo) {
    global $pdo;
    
    // Get total purchases
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_amount,
            AVG(total_amount) as average_order_value
        FROM purchase_orders
        WHERE order_date BETWEEN :date_from AND :date_to
    ");
    
    $stmt->execute([
        ':date_from' => $dateFrom,
        ':date_to' => $dateTo
    ]);
    
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get purchases by status
    $stmt = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as amount
        FROM purchase_orders
        WHERE order_date BETWEEN :date_from AND :date_to
        GROUP BY status
    ");
    
    $stmt->execute([
        ':date_from' => $dateFrom,
        ':date_to' => $dateTo
    ]);
    
    $summary['status_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $summary;
}

function getProductionSummary($dateFrom, $dateTo) {
    global $pdo;
    
    // Get total production orders
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(quantity) as total_quantity,
            AVG(quantity) as average_quantity
        FROM production_orders
        WHERE order_date BETWEEN :date_from AND :date_to
    ");
    
    $stmt->execute([
        ':date_from' => $dateFrom,
        ':date_to' => $dateTo
    ]);
    
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get production by status
    $stmt = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(quantity) as quantity
        FROM production_orders
        WHERE order_date BETWEEN :date_from AND :date_to
        GROUP BY status
    ");
    
    $stmt->execute([
        ':date_from' => $dateFrom,
        ':date_to' => $dateTo
    ]);
    
    $summary['status_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $summary;
} 