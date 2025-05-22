<?php
require_once 'db_connect.php';
require_once 'core.php';

// Check if user has permission to manage Telegram bot
function hasTelegramBotPermission($requiredPermission = 'api.telegram.view') {
    if (!isset($_SESSION['userId'])) {
        return false;
    }
    
    global $connect;
    
    // First check if user is admin
    $adminSql = "SELECT p.permission_name FROM permissions p 
                JOIN role_permissions rp ON p.permission_id = rp.permission_id
                JOIN users u ON u.role_id = rp.role_id
                WHERE u.user_id = ? AND (p.permission_name = 'admin.access' OR p.permission_name = 'system.admin')";
    
    $adminStmt = $connect->prepare($adminSql);
    $adminStmt->bind_param("i", $_SESSION['userId']);
    $adminStmt->execute();
    $adminResult = $adminStmt->get_result();
    
    if ($adminResult && $adminResult->num_rows > 0) {
        return true; // Admin has full access
    }
    
    // If not admin, check for specific permission
    $sql = "SELECT p.permission_name FROM permissions p 
            JOIN role_permissions rp ON p.permission_id = rp.permission_id
            JOIN users u ON u.role_id = rp.role_id
            WHERE u.user_id = ? AND p.permission_name = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $_SESSION['userId'], $requiredPermission);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result && $result->num_rows > 0;
}

// Create new Telegram bot settings
function createBotSettings($botName, $botToken, $chatId, $webhookUrl = null) {
    if (!hasTelegramBotPermission('api.telegram.configure')) {
        return ['success' => false, 'message' => 'You do not have permission to manage Telegram bot settings'];
    }
    
    global $connect;
    
    // Check if another bot is already active
    $sql = "SELECT id FROM telegram_bot_settings WHERE is_active = 1";
    $result = $connect->query($sql);
    
    // If an active bot already exists, deactivate it
    if ($result && $result->num_rows > 0) {
        $sql = "UPDATE telegram_bot_settings SET is_active = 0 WHERE is_active = 1";
        $connect->query($sql);
    }
    
    $sql = "INSERT INTO telegram_bot_settings (bot_name, bot_token, chat_id, webhook_url, created_by) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ssssi", $botName, $botToken, $chatId, $webhookUrl, $_SESSION['userId']);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Bot settings created successfully', 'id' => $connect->insert_id];
    } else {
        return ['success' => false, 'message' => 'Failed to create bot settings: ' . $connect->error];
    }
}

// Get all Telegram bot settings
function getAllBotSettings() {
    if (!hasTelegramBotPermission('api.telegram.view')) {
        return ['success' => false, 'message' => 'You do not have permission to view Telegram bot settings'];
    }
    
    global $connect;
    
    $sql = "SELECT b.*, u.username as created_by_username 
            FROM telegram_bot_settings b
            LEFT JOIN users u ON b.created_by = u.user_id
            ORDER BY b.is_active DESC, b.created_at DESC";
    $result = $connect->query($sql);
    
    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            // Mask bot token for security
            $row['bot_token'] = substr($row['bot_token'], 0, 8) . '...' . substr($row['bot_token'], -5);
            $data[] = $row;
        }
        return ['success' => true, 'data' => $data];
    } else {
        return ['success' => false, 'message' => 'Failed to fetch bot settings: ' . $connect->error];
    }
}

// Get a single Telegram bot setting
function getBotSetting($id) {
    if (!hasTelegramBotPermission('api.telegram.view')) {
        return ['success' => false, 'message' => 'You do not have permission to view Telegram bot settings'];
    }
    
    global $connect;
    
    $sql = "SELECT * FROM telegram_bot_settings WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return ['success' => true, 'data' => $result->fetch_assoc()];
    } else {
        return ['success' => false, 'message' => 'Bot setting not found'];
    }
}

// Update a Telegram bot setting
function updateBotSetting($id, $botName, $botToken, $chatId, $webhookUrl = null, $isActive = 1) {
    if (!hasTelegramBotPermission('api.telegram.configure')) {
        return ['success' => false, 'message' => 'You do not have permission to update Telegram bot settings'];
    }
    
    global $connect;
    
    // If this bot is being activated, deactivate all others
    if ($isActive == 1) {
        $sql = "UPDATE telegram_bot_settings SET is_active = 0 WHERE id != ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    
    $sql = "UPDATE telegram_bot_settings 
            SET bot_name = ?, bot_token = ?, chat_id = ?, webhook_url = ?, is_active = ? 
            WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ssssis", $botName, $botToken, $chatId, $webhookUrl, $isActive, $id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Bot settings updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to update bot settings: ' . $connect->error];
    }
}

// Delete a Telegram bot setting
function deleteBotSetting($id) {
    if (!hasTelegramBotPermission('api.telegram.configure')) {
        return ['success' => false, 'message' => 'You do not have permission to delete Telegram bot settings'];
    }
    
    global $connect;
    
    $sql = "DELETE FROM telegram_bot_settings WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Bot settings deleted successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to delete bot settings: ' . $connect->error];
    }
}

// Test the bot connection
function testBotConnection($botToken, $chatId) {
    if (!hasTelegramBotPermission('api.telegram.configure')) {
        return ['success' => false, 'message' => 'You do not have permission to test Telegram bot'];
    }
    
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => "Test message from PistockLNT system at " . date("Y-m-d H:i:s"),
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
        return ['success' => false, 'message' => 'Failed to connect to Telegram API: ' . error_get_last()['message']];
    }
    
    $response = json_decode($result, true);
    if (!isset($response['ok']) || $response['ok'] !== true) {
        return ['success' => false, 'message' => 'Telegram API error: ' . ($response['description'] ?? 'Unknown error')];
    }
    
    return ['success' => true, 'message' => 'Test message sent successfully'];
}

// CRUD operations for Notification Templates

// Create a new notification template
function createNotificationTemplate($templateName, $templateKey, $messageTemplate) {
    if (!hasTelegramBotPermission('api.telegram.manage')) {
        return ['success' => false, 'message' => 'You do not have permission to create notification templates'];
    }
    
    global $connect;
    
    // Check if template key already exists
    $sql = "SELECT id FROM telegram_notification_templates WHERE template_key = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $templateKey);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return ['success' => false, 'message' => 'Template key already exists'];
    }
    
    $sql = "INSERT INTO telegram_notification_templates (template_name, template_key, message_template, created_by) 
            VALUES (?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("sssi", $templateName, $templateKey, $messageTemplate, $_SESSION['userId']);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Notification template created successfully', 'id' => $connect->insert_id];
    } else {
        return ['success' => false, 'message' => 'Failed to create notification template: ' . $connect->error];
    }
}

// Get all notification templates
function getAllNotificationTemplates() {
    if (!hasTelegramBotPermission('api.telegram.view')) {
        return ['success' => false, 'message' => 'You do not have permission to view notification templates'];
    }
    
    global $connect;
    
    $sql = "SELECT t.*, u.username as created_by_username 
            FROM telegram_notification_templates t
            LEFT JOIN users u ON t.created_by = u.user_id
            ORDER BY t.template_name ASC";
    $result = $connect->query($sql);
    
    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return ['success' => true, 'data' => $data];
    } else {
        return ['success' => false, 'message' => 'Failed to fetch notification templates: ' . $connect->error];
    }
}

// Get a single notification template
function getNotificationTemplateById($id) {
    if (!hasTelegramBotPermission('api.telegram.view')) {
        return ['success' => false, 'message' => 'You do not have permission to view notification templates'];
    }
    
    global $connect;
    
    $sql = "SELECT * FROM telegram_notification_templates WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return ['success' => true, 'data' => $result->fetch_assoc()];
    } else {
        return ['success' => false, 'message' => 'Notification template not found'];
    }
}

// Update a notification template
function updateNotificationTemplate($id, $templateName, $templateKey, $messageTemplate, $isActive = 1) {
    if (!hasTelegramBotPermission('api.telegram.manage')) {
        return ['success' => false, 'message' => 'You do not have permission to update notification templates'];
    }
    
    global $connect;
    
    // Check if template key already exists for another template
    $sql = "SELECT id FROM telegram_notification_templates WHERE template_key = ? AND id != ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("si", $templateKey, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return ['success' => false, 'message' => 'Template key already exists for another template'];
    }
    
    $sql = "UPDATE telegram_notification_templates 
            SET template_name = ?, template_key = ?, message_template = ?, is_active = ? 
            WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("sssii", $templateName, $templateKey, $messageTemplate, $isActive, $id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Notification template updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to update notification template: ' . $connect->error];
    }
}

// Delete a notification template
function deleteNotificationTemplate($id) {
    if (!hasTelegramBotPermission('api.telegram.manage')) {
        return ['success' => false, 'message' => 'You do not have permission to delete notification templates'];
    }
    
    global $connect;
    
    $sql = "DELETE FROM telegram_notification_templates WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Notification template deleted successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to delete notification template: ' . $connect->error];
    }
}

// Get notification logs
function getNotificationLogs($limit = 100, $offset = 0) {
    if (!hasTelegramBotPermission('api.telegram.monitor')) {
        return ['success' => false, 'message' => 'You do not have permission to view notification logs'];
    }
    
    global $connect;
    
    $sql = "SELECT l.*, t.template_name, t.template_key 
            FROM telegram_notification_logs l
            LEFT JOIN telegram_notification_templates t ON l.template_id = t.id
            ORDER BY l.created_at DESC
            LIMIT ? OFFSET ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM telegram_notification_logs";
        $countResult = $connect->query($countSql);
        $totalCount = $countResult->fetch_assoc()['total'];
        
        return [
            'success' => true, 
            'data' => $data,
            'pagination' => [
                'total' => $totalCount,
                'offset' => $offset,
                'limit' => $limit
            ]
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to fetch notification logs: ' . $connect->error];
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'createBot':
            $response = createBotSettings($_POST['botName'], $_POST['botToken'], $_POST['chatId'], $_POST['webhookUrl'] ?? null);
            break;
            
        case 'updateBot':
            $response = updateBotSetting(
                $_POST['id'], 
                $_POST['botName'], 
                $_POST['botToken'], 
                $_POST['chatId'], 
                $_POST['webhookUrl'] ?? null, 
                $_POST['isActive'] ?? 1
            );
            break;
            
        case 'deleteBot':
            $response = deleteBotSetting($_POST['id']);
            break;
            
        case 'testBot':
            $response = testBotConnection($_POST['botToken'], $_POST['chatId']);
            break;
            
        case 'createTemplate':
            $response = createNotificationTemplate($_POST['templateName'], $_POST['templateKey'], $_POST['messageTemplate']);
            break;
            
        case 'updateTemplate':
            $response = updateNotificationTemplate(
                $_POST['id'],
                $_POST['templateName'],
                $_POST['templateKey'],
                $_POST['messageTemplate'],
                $_POST['isActive'] ?? 1
            );
            break;
            
        case 'deleteTemplate':
            $response = deleteNotificationTemplate($_POST['id']);
            break;
    }
    
    echo json_encode($response);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'getBots':
            $response = getAllBotSettings();
            break;
            
        case 'getBot':
            $response = getBotSetting($_GET['id']);
            break;
            
        case 'getTemplates':
            $response = getAllNotificationTemplates();
            break;
            
        case 'getTemplate':
            $response = getNotificationTemplateById($_GET['id']);
            break;
            
        case 'getLogs':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $response = getNotificationLogs($limit, $offset);
            break;
    }
    
    echo json_encode($response);
    exit;
} 