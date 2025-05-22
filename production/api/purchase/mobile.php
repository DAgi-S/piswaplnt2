<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/response.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetRequest();
        break;
    case 'POST':
        handlePostRequest();
        break;
    default:
        sendError('Method not allowed', 405);
        break;
}

function handleGetRequest() {
    global $pdo, $userId;
    
    try {
        // Get query parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        // Build base query
        $query = "SELECT 
                    po.id,
                    po.order_number,
                    po.order_date,
                    po.status,
                    po.total_amount,
                    s.name as supplier_name,
                    COUNT(poi.id) as item_count
                FROM purchase_orders po
                LEFT JOIN suppliers s ON po.supplier_id = s.id
                LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
                WHERE po.created_by = :user_id";
        
        $params = [':user_id' => $userId];
        
        // Add search filter
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $query .= " AND (po.order_number LIKE :search OR s.name LIKE :search)";
            $params[':search'] = '%' . $_GET['search'] . '%';
        }
        
        // Add status filter
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $query .= " AND po.status = :status";
            $params[':status'] = $_GET['status'];
        }
        
        // Add date range filter
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $query .= " AND po.order_date >= :date_from";
            $params[':date_from'] = $_GET['date_from'];
        }
        
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $query .= " AND po.order_date <= :date_to";
            $params[':date_to'] = $_GET['date_to'];
        }
        
        // Add group by and order
        $query .= " GROUP BY po.id ORDER BY po.order_date DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        // Get total count
        $countQuery = str_replace(
            "SELECT po.id, po.order_number, po.order_date, po.status, po.total_amount, s.name as supplier_name, COUNT(poi.id) as item_count",
            "SELECT COUNT(DISTINCT po.id) as total",
            $query
        );
        $countQuery = preg_replace('/LIMIT.*$/', '', $countQuery);
        
        $stmt = $pdo->prepare($countQuery);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get orders
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendSuccess([
            'orders' => $orders,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
        
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

function handlePostRequest() {
    global $pdo, $userId;
    
    try {
        // Get request body
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['supplier_id']) || !isset($data['items']) || !is_array($data['items'])) {
            sendError('Invalid request data');
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Generate order number
        $orderNumber = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert purchase order
        $stmt = $pdo->prepare("
            INSERT INTO purchase_orders (
                order_number,
                supplier_id,
                order_date,
                status,
                created_by,
                created_at
            ) VALUES (
                :order_number,
                :supplier_id,
                NOW(),
                'pending',
                :created_by,
                NOW()
            )
        ");
        
        $stmt->execute([
            ':order_number' => $orderNumber,
            ':supplier_id' => $data['supplier_id'],
            ':created_by' => $userId
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Insert order items
        $totalAmount = 0;
        $stmt = $pdo->prepare("
            INSERT INTO purchase_order_items (
                purchase_order_id,
                product_id,
                quantity,
                unit_price,
                total_price,
                created_at
            ) VALUES (
                :purchase_order_id,
                :product_id,
                :quantity,
                :unit_price,
                :total_price,
                NOW()
            )
        ");
        
        foreach ($data['items'] as $item) {
            if (!isset($item['product_id']) || !isset($item['quantity']) || !isset($item['unit_price'])) {
                $pdo->rollBack();
                sendError('Invalid item data');
                return;
            }
            
            $totalPrice = $item['quantity'] * $item['unit_price'];
            $totalAmount += $totalPrice;
            
            $stmt->execute([
                ':purchase_order_id' => $orderId,
                ':product_id' => $item['product_id'],
                ':quantity' => $item['quantity'],
                ':unit_price' => $item['unit_price'],
                ':total_price' => $totalPrice
            ]);
        }
        
        // Update total amount
        $stmt = $pdo->prepare("
            UPDATE purchase_orders 
            SET total_amount = :total_amount 
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':total_amount' => $totalAmount,
            ':id' => $orderId
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        sendSuccess(['order_id' => $orderId]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        sendError('Database error: ' . $e->getMessage());
    }
} 