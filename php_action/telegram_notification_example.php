<?php
/**
 * Telegram Notification Implementation Examples
 * 
 * This file provides examples of how to integrate Telegram notifications
 * into various parts of the system.
 */

// Include the notification helper
require_once 'telegram_notification_helper.php';

/**
 * EXAMPLE 1: Sending a notification when a new order is created
 * 
 * Add this code to the order creation process (e.g., in orders.php or similar)
 */
function notifyNewOrder($orderId) {
    // Get order details from database
    global $connect;
    
    $sql = "SELECT o.*, c.name AS customer_name 
            FROM sales_orders o 
            LEFT JOIN clients c ON o.client_id = c.id
            WHERE o.id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $order = $result->fetch_assoc();
        
        // Count items
        $itemsSql = "SELECT COUNT(*) as item_count FROM sales_order_items WHERE order_id = ?";
        $itemsStmt = $connect->prepare($itemsSql);
        $itemsStmt->bind_param("i", $orderId);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        $itemCount = ($itemsResult && $itemsResult->num_rows > 0) ? $itemsResult->fetch_assoc()['item_count'] : 0;
        
        // Format data for the notification
        $notificationData = [
            'order_id' => $orderId,
            'customer_name' => $order['customer_name'],
            'amount' => number_format($order['grand_total'], 2),
            'item_count' => $itemCount,
            'order_date' => date('Y-m-d H:i:s')
        ];
        
        // Send the notification
        sendTelegramNotification('order_create', $notificationData);
    }
}

/**
 * EXAMPLE 2: Sending a notification when product stock is low
 * 
 * Add this code to the stock update process (e.g., after order completion or inventory check)
 */
function checkAndNotifyLowStock($productId) {
    global $connect;
    
    $sql = "SELECT p.*, c.name AS category_name 
            FROM products p
            LEFT JOIN categories c ON p.categories_id = c.id
            WHERE p.id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Check if stock is below reorder level
        if ($product['quantity'] <= $product['reorder_level']) {
            // Format data for the notification
            $notificationData = [
                'product_name' => $product['name'],
                'product_code' => $product['product_code'],
                'category' => $product['category_name'],
                'current_stock' => $product['quantity'],
                'reorder_level' => $product['reorder_level'],
                'alert_time' => date('Y-m-d H:i:s')
            ];
            
            // Send the notification
            sendTelegramNotification('stock_low', $notificationData);
        }
    }
}

/**
 * EXAMPLE 3: Sending a notification for digital swap approval
 * 
 * Add this code to the digital swap approval process
 */
function notifyDigitalSwapApproval($swapId, $approverId) {
    global $connect;
    
    $sql = "SELECT ds.*, u.name AS username, a.name AS from_account, b.name AS to_account, 
                  u2.name AS approver_name
            FROM digitalswap ds
            LEFT JOIN users u ON ds.user_id = u.user_id
            LEFT JOIN accounts a ON ds.from_account_id = a.id
            LEFT JOIN accounts b ON ds.to_account_id = b.id
            LEFT JOIN users u2 ON u2.user_id = ?
            WHERE ds.id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ii", $approverId, $swapId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $swap = $result->fetch_assoc();
        
        // Format data for the notification
        $notificationData = [
            'swap_id' => $swapId,
            'username' => $swap['username'],
            'amount' => number_format($swap['amount'], 2),
            'from_account' => $swap['from_account'],
            'to_account' => $swap['to_account'],
            'approver' => $swap['approver_name'],
            'approval_time' => date('Y-m-d H:i:s'),
            'reference' => $swap['reference'] ?? 'N/A'
        ];
        
        // Send the notification
        sendTelegramNotification('digital_swap_approve', $notificationData);
    }
}

/**
 * EXAMPLE 4: Sending a notification for failed login attempts
 * 
 * Add this code to the login process where failed attempts are handled
 */
function notifyFailedLogin($username, $attemptCount, $ipAddress) {
    $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Format data for the notification
    $notificationData = [
        'username' => $username,
        'ip_address' => $ipAddress,
        'device' => $deviceInfo,
        'attempt_time' => date('Y-m-d H:i:s'),
        'attempt_count' => $attemptCount
    ];
    
    // Send the notification
    sendTelegramNotification('login_failed', $notificationData);
}

/**
 * EXAMPLE 5: Sending a notification for GPS letter generation
 * 
 * Add this code to the GPS letter generation process
 */
function notifyGPSLetterGenerated($letterId, $userId) {
    global $connect;
    
    $sql = "SELECT l.*, lt.name AS letter_type, u.name AS generated_by_name 
            FROM generated_letters l
            LEFT JOIN letter_templates lt ON l.template_id = lt.id
            LEFT JOIN users u ON u.user_id = ?
            WHERE l.id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ii", $userId, $letterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $letter = $result->fetch_assoc();
        
        // Format data for the notification
        $notificationData = [
            'letter_id' => $letterId,
            'letter_type' => $letter['letter_type'],
            'recipient' => $letter['recipient_name'],
            'generated_by' => $letter['generated_by_name'],
            'generation_time' => date('Y-m-d H:i:s')
        ];
        
        // Send the notification
        sendTelegramNotification('gps_letter_generated', $notificationData);
    }
}

/**
 * HOW TO IMPLEMENT IN YOUR CODE:
 * 
 * 1. Include the helper file at the top of your PHP file:
 *    require_once 'php_action/telegram_notification_helper.php';
 * 
 * 2. After the relevant action (e.g., creating an order), call the notification function:
 *    
 *    // Example: After creating an order
 *    $orderId = $connect->insert_id; // Get the new order ID
 *    
 *    // Send notification
 *    sendTelegramNotification('order_create', [
 *        'order_id' => $orderId,
 *        'customer_name' => $customerName,
 *        'amount' => $orderTotal,
 *        'item_count' => count($orderItems),
 *        'order_date' => date('Y-m-d H:i:s')
 *    ]);
 * 
 * 3. Make sure you've configured the notification in the Telegram Notification Management page
 *    by selecting the appropriate template for the 'order_create' event.
 */ 