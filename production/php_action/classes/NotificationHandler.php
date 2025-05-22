<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

class NotificationHandler {
    private $connect;
    private $emailConfig;
    private $telegramConfig;
    private $errors = [];

    public function __construct($connect) {
        $this->connect = $connect;
        $this->emailConfig = [
            'host' => 'mail.lebawi.net',
            'port' => 465,
            'username' => 'swapcapital@lebawi.net',
            'password' => 'swapcapital@0924',
            'from_email' => 'swapcapital@lebawi.net',
            'from_name' => 'Rama Manufacturing System'
        ];
        $this->telegramConfig = [
            'bot_token' => '8096776402:AAE6RwnKc78oxJHZqx-0aWtU9eLVijCqYUw',
            'chat_id' => '317393086'
        ];
    }

    public function getErrors() {
        return $this->errors;
    }

    public function sendLowStockNotification($item, $threshold) {
        $this->errors = [];
        $subject = "Low Stock Alert: " . $item['name'];
        $message = $this->createLowStockMessage($item, $threshold);
        
        // Send email notification
        $emailResult = $this->sendEmail($subject, $message);
        
        // Send Telegram notification
        $telegramResult = $this->sendTelegramMessage($message);
        
        // Log the notification
        $logResult = $this->logNotification($item['id'], $message);

        if (!empty($this->errors)) {
            throw new Exception(implode("\n", $this->errors));
        }
    }

    private function createLowStockMessage($item, $threshold) {
        return sprintf(
            "Low Stock Alert!\n\n" .
            "Item: %s\n" .
            "Current Stock: %s %s\n" .
            "Minimum Stock Level: %s %s\n" .
            "Threshold: %s%%\n" .
            "Warehouse: %s\n\n" .
            "Please take necessary action to replenish stock.",
            $item['name'],
            $item['current_stock'],
            $item['unit'],
            $item['min_stock_level'],
            $item['unit'],
            $threshold,
            $item['warehouse_name']
        );
    }

    private function sendEmail($subject, $message) {
        try {
            $mail = new PHPMailer(true);

            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host = $this->emailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->emailConfig['username'];
            $mail->Password = $this->emailConfig['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->emailConfig['port'];

            // Get users who have email notifications enabled
            $sql = "SELECT email FROM users WHERE notify_alerts = 1 AND status = 1";
            $result = $this->connect->query($sql);
            
            if ($result->num_rows == 0) {
                $this->errors[] = "No users found with email notifications enabled";
                return false;
            }

            // Recipients
            $mail->setFrom($this->emailConfig['from_email'], $this->emailConfig['from_name']);
            
            while ($row = $result->fetch_assoc()) {
                $mail->addAddress($row['email']);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = nl2br($message);
            $mail->AltBody = strip_tags($message);

            return $mail->send();
        } catch (Exception $e) {
            $this->errors[] = "Email error: " . $mail->ErrorInfo;
            return false;
        }
    }

    private function sendTelegramMessage($message) {
        try {
            $url = "https://api.telegram.org/bot" . $this->telegramConfig['bot_token'] . "/sendMessage";
            $data = [
                'chat_id' => $this->telegramConfig['chat_id'],
                'text' => $message,
                'parse_mode' => 'HTML'
            ];

            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($data)
                ]
            ];

            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            
            if ($result === false) {
                $this->errors[] = "Failed to send Telegram message";
                return false;
            }

            $response = json_decode($result, true);
            if (!$response['ok']) {
                $this->errors[] = "Telegram API error: " . ($response['description'] ?? 'Unknown error');
                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->errors[] = "Telegram error: " . $e->getMessage();
            return false;
        }
    }

    private function logNotification($itemId, $message) {
        try {
            $sql = "INSERT INTO notification_logs (item_id, message, created_at) VALUES (?, ?, NOW())";
            $stmt = $this->connect->prepare($sql);
            $stmt->bind_param("is", $itemId, $message);
            if (!$stmt->execute()) {
                $this->errors[] = "Failed to log notification: " . $stmt->error;
                return false;
            }
            return true;
        } catch (Exception $e) {
            $this->errors[] = "Logging error: " . $e->getMessage();
            return false;
        }
    }

    public function testNotifications() {
        $testItem = [
            'id' => 1,
            'name' => 'Test Product',
            'current_stock' => 5,
            'unit' => 'pcs',
            'min_stock_level' => 10,
            'warehouse_name' => 'Test Warehouse'
        ];
        
        $this->sendLowStockNotification($testItem, 50);
        return "Test notifications sent successfully!";
    }
}
?> 