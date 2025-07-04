<?php
require_once 'db_connect.php';
require_once 'core.php';

/**
 * Get Telegram bot settings from database
 * @return array|bool Bot settings or false if not found
 */
function getTelegramBotSettings() {
    global $connect;
    
    $sql = "SELECT * FROM telegram_bot_settings WHERE is_active = 1 LIMIT 1";
    $result = $connect->query($sql);
    
    if($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Get notification template by template key
 * @param string $templateKey Template key
 * @return array|bool Template data or false if not found
 */
function getNotificationTemplate($templateKey) {
    global $connect;
    
    $sql = "SELECT * FROM telegram_notification_templates WHERE template_key = ? AND is_active = 1 LIMIT 1";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $templateKey);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Log notification to database
 * @param int|null $templateId Template ID
 * @param string $message Message text
 * @param string $status Status (pending, sent, failed)
 * @param string|null $errorMessage Error message if any
 * @return int|bool ID of the created log or false on failure
 */
function logNotification($templateId, $message, $status = 'pending', $errorMessage = null) {
    global $connect;
    
    $sql = "INSERT INTO telegram_notification_logs (template_id, message, status, error_message, sent_at) 
            VALUES (?, ?, ?, ?, " . ($status == 'sent' ? 'NOW()' : 'NULL') . ")";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("isss", $templateId, $message, $status, $errorMessage);
    
    if($stmt->execute()) {
        return $connect->insert_id;
    }
    
    return false;
}

/**
 * Update notification log status
 * @param int $logId Log ID
 * @param string $status Status
 * @param string|null $errorMessage Error message if any
 * @return bool Success status
 */
function updateNotificationStatus($logId, $status, $errorMessage = null) {
    global $connect;
    
    $sql = "UPDATE telegram_notification_logs SET status = ?, error_message = ?" . 
           ($status == 'sent' ? ", sent_at = NOW()" : "") . 
           " WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ssi", $status, $errorMessage, $logId);
    
    return $stmt->execute();
}

/**
 * Replace placeholder variables in a template
 * @param string $template Template text
 * @param array $data Data for replacement
 * @return string Processed template
 */
function processTemplate($template, $data = []) {
    foreach($data as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }
    return $template;
}

/**
 * Send notification with template
 * @param string $templateKey Template key
 * @param array $data Data for template variables
 * @return bool Success status
 */
function sendTemplateNotification($templateKey, $data = []) {
    $template = getNotificationTemplate($templateKey);
    if(!$template) {
        return false;
    }
    
    $message = processTemplate($template['message_template'], $data);
    $logId = logNotification($template['id'], $message);
    
    $success = sendTelegramNotification($message);
    $status = $success ? 'sent' : 'failed';
    $errorMessage = $success ? null : "Failed to send notification";
    
    if($logId) {
        updateNotificationStatus($logId, $status, $errorMessage);
    }
    
    return $success;
}

/**
 * Send telegram notification
 * @param string $message Message to send
 * @return bool Success status
 */
function sendTelegramNotification($message) {
    try {
        $botSettings = getTelegramBotSettings();
        if(!$botSettings) {
            error_log('sendTelegramNotification: No active bot settings found in DB.');
            return false;
        }
        $botToken = $botSettings['bot_token'];
        $chatId = $botSettings['chat_id'];
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
            error_log("Failed to send Telegram notification: " . error_get_last()['message']);
            return false;
        }
        $response = json_decode($result, true);
        if (!isset($response['ok']) || $response['ok'] !== true) {
            error_log("Telegram API error: " . $result);
            return false;
        }
        return true;
    } catch (Exception $e) {
        error_log("Exception while sending Telegram notification: " . $e->getMessage());
        return false;
    }
} 
