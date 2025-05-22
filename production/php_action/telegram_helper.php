<?php
require_once 'core.php';

class TelegramHelper {
    private $botToken = '8096776402:AAE6RwnKc78oxJHZqx-0aWtU9eLVijCqYUw';
    private $apiEndpoint = 'https://api.telegram.org/bot';

    public function sendMessage($message) {
        $chatId = $this->getDefaultChatId();
        
        $url = $this->apiEndpoint . $this->botToken . '/sendMessage';
        $data = [
            'chat_id' => $chatId,
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

    private function getDefaultChatId() {
        // You should store this in a configuration file or database
        // For now, using a default chat ID
        return '317393086'; // Replace with your actual chat ID
    }
} 