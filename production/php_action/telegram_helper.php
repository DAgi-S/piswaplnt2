<?php
require_once 'core.php';
require_once __DIR__ . '/../../includes/db_connect.php';

class TelegramHelper {
    private $botToken;
    private $chatId;
    private $apiEndpoint = 'https://api.telegram.org/bot';

    public function __construct() {
        global $connect;
        $sql = "SELECT bot_token, chat_id FROM telegram_bot_settings WHERE is_active = 1 LIMIT 1";
        $result = $connect->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $this->botToken = $row['bot_token'];
            $this->chatId = $row['chat_id'];
        } else {
            error_log('TelegramHelper: No active bot settings found in DB.');
            $this->botToken = null;
            $this->chatId = null;
        }
    }

    public function sendMessage($message) {
        if (!$this->botToken || !$this->chatId) {
            error_log('TelegramHelper: Bot token or chat ID not set.');
            return false;
        }
        $url = $this->apiEndpoint . $this->botToken . '/sendMessage';
        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data)
            ]
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return json_decode($result, true);
    }

    public function sendProductionOrderNotification($orderData) {
        $message = "🏭 <b>New Production Order Created</b>\n\n";
        $message .= "📦 Order Number: " . $orderData['order_number'] . "\n";
        $message .= "🛠️ Product: " . $orderData['product_name'] . "\n";
        $message .= "📊 Quantity: " . $orderData['target_quantity'] . "\n";
        $message .= "📅 Start Date: " . $orderData['start_date'] . "\n";
        $message .= "⏳ Expected Completion: " . $orderData['completion_date'] . "\n";
        $message .= "👤 Created By: " . $orderData['created_by'];
        return $this->sendMessage($message);
    }
} 
