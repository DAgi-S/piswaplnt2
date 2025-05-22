<?php
/**
 * Handler for the Telegram notification example
 */
require_once '../php_action/db_connect.php';
require_once '../php_action/core.php';
require_once '../php_action/telegram_notification.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to use this feature']);
    exit;
}

// Set header for JSON response
header('Content-Type: application/json');

// Process the request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    
    if ($type === 'template') {
        // Template notification
        $templateKey = $_POST['templateKey'] ?? '';
        $templateData = $_POST['templateData'] ?? '{}';
        
        if (empty($templateKey)) {
            echo json_encode(['success' => false, 'message' => 'Template key is required']);
            exit;
        }
        
        try {
            $data = json_decode($templateData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data');
            }
            
            $result = sendTemplateNotification($templateKey, $data);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Notification sent successfully using template: ' . $templateKey
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to send notification. Check the logs for details.'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    } elseif ($type === 'direct') {
        // Direct message
        $message = $_POST['message'] ?? '';
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message is required']);
            exit;
        }
        
        $result = sendTelegramNotification($message);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Direct message sent successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to send message. Check the logs for details.'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request type']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
} 