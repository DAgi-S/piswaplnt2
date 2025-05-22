<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get query parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

        // Build query
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(o.order_number LIKE ? OR c.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($status)) {
            $where[] = "o.status = ?";
            $params[] = $status;
        }

        if (!empty($dateFrom)) {
            $where[] = "DATE(o.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $where[] = "DATE(o.created_at) <= ?";
            $params[] = $dateTo;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM orders o $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        // Get orders
        $offset = ($page - 1) * $limit;
        $sql = "
            SELECT 
                o.id,
                o.order_number,
                o.total_amount,
                o.status,
                o.created_at,
                c.name as client_name,
                u.name as created_by
            FROM orders o
            LEFT JOIN clients c ON o.client_id = c.id
            LEFT JOIN users u ON o.created_by = u.id
            $whereClause
            ORDER BY o.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'orders' => $orders,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } else {
        // Handle POST request for creating new order
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['client_id']) || !isset($data['items']) || !is_array($data['items'])) {
            throw new Exception('Invalid order data');
        }

        $pdo->beginTransaction();

        try {
            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    order_number,
                    client_id,
                    total_amount,
                    status,
                    created_by,
                    created_at
                ) VALUES (
                    CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 10000), 4, '0')),
                    ?,
                    0,
                    'pending',
                    ?,
                    NOW()
                )
            ");
            $stmt->execute([$data['client_id'], $userId]);
            $orderId = $pdo->lastInsertId();

            // Add order items
            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                if (!isset($item['product_id']) || !isset($item['quantity']) || !isset($item['price'])) {
                    throw new Exception('Invalid item data');
                }

                $stmt = $pdo->prepare("
                    INSERT INTO order_items (
                        order_id,
                        product_id,
                        quantity,
                        price,
                        total
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $itemTotal = $item['quantity'] * $item['price'];
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                    $itemTotal
                ]);
                $totalAmount += $itemTotal;

                // Update product quantity
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET quantity = quantity - ? 
                    WHERE id = ?
                ");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }

            // Update order total
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET total_amount = ? 
                WHERE id = ?
            ");
            $stmt->execute([$totalAmount, $orderId]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => $orderId
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 