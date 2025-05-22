<?php
/**
 * Telegram Notification Helper Functions
 * 
 * This file contains helper functions to easily send Telegram notifications
 * from anywhere in the system.
 */

require_once 'db_connect.php';

/**
 * Send a notification based on an event key
 * 
 * @param string $eventKey The event key as defined in the notification management page
 * @param array $data Associative array of data to replace in the template
 * @return array Result including success status and message
 */
function sendTelegramNotification($eventKey, $data = []) {
    global $connect;
    
    // Check if there's a configured notification for this event
    $sql = "SELECT s.*, t.template_key, t.message_template, b.bot_token, b.chat_id 
            FROM telegram_notification_settings s
            JOIN telegram_notification_templates t ON s.template_id = t.id
            JOIN telegram_bot_settings b ON b.is_active = 1
            WHERE s.event_key = ? AND s.is_active = 1
            LIMIT 1";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $eventKey);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows == 0) {
        // No active notification configured for this event
        return ['success' => false, 'message' => 'No active notification configured for event: ' . $eventKey];
    }
    
    $notification = $result->fetch_assoc();
    
    // Replace variables in the template
    $message = $notification['message_template'];
    foreach ($data as $key => $value) {
        $message = str_replace('{{' . $key . '}}', $value, $message);
    }
    
    // Log the notification attempt
    $logSql = "INSERT INTO telegram_notification_logs (template_id, message) VALUES (?, ?)";
    $logStmt = $connect->prepare($logSql);
    $logStmt->bind_param("is", $notification['template_id'], $message);
    $logStmt->execute();
    $logId = $connect->insert_id;
    
    // Send the message
    $result = sendTelegramMessage($notification['bot_token'], $notification['chat_id'], $message, $logId);
    
    return $result;
}

/**
 * Send a Telegram message directly
 * 
 * @param string $botToken The Telegram bot token
 * @param string $chatId The Telegram chat ID
 * @param string $message The message to send
 * @param int $logId The ID of the log entry (optional)
 * @return array Result including success status and message
 */
function sendTelegramMessage($botToken, $chatId, $message, $logId = null) {
    global $connect;
    
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
            'ignore_errors' => true
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        $error = error_get_last()['message'];
        
        // Update log if we have a log ID
        if ($logId) {
            $updateSql = "UPDATE telegram_notification_logs SET status = 'failed', error_message = ? WHERE id = ?";
            $updateStmt = $connect->prepare($updateSql);
            $updateStmt->bind_param("si", $error, $logId);
            $updateStmt->execute();
        }
        
        return ['success' => false, 'message' => 'Failed to connect to Telegram API: ' . $error];
    }
    
    $response = json_decode($result, true);
    if (!isset($response['ok']) || $response['ok'] !== true) {
        $error = isset($response['description']) ? $response['description'] : 'Unknown error';
        
        // Update log if we have a log ID
        if ($logId) {
            $updateSql = "UPDATE telegram_notification_logs SET status = 'failed', error_message = ? WHERE id = ?";
            $updateStmt = $connect->prepare($updateSql);
            $updateStmt->bind_param("si", $error, $logId);
            $updateStmt->execute();
        }
        
        return ['success' => false, 'message' => 'Telegram API error: ' . $error];
    }
    
    // Update log if we have a log ID
    if ($logId) {
        $updateSql = "UPDATE telegram_notification_logs SET status = 'sent', sent_at = NOW() WHERE id = ?";
        $updateStmt = $connect->prepare($updateSql);
        $updateStmt->bind_param("i", $logId);
        $updateStmt->execute();
    }
    
    return ['success' => true, 'message' => 'Message sent successfully'];
}

/**
 * Example usage:
 * 
 * // Send a notification about a new order
 * sendTelegramNotification('order_create', [
 *     'order_id' => '12345',
 *     'customer_name' => 'John Doe',
 *     'amount' => '$123.45',
 *     'item_count' => '3',
 *     'order_date' => date('Y-m-d H:i:s')
 * ]);
 * 
 * // Send a notification about a stock alert
 * sendTelegramNotification('stock_low', [
 *     'product_name' => 'Widget XYZ',
 *     'current_stock' => '5',
 *     'reorder_level' => '10',
 *     'alert_time' => date('Y-m-d H:i:s')
 * ]);
 */ 