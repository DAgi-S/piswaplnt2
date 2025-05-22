<?php
require_once 'core.php';
require_once 'classes/NotificationManager.php';

// Ensure user is logged in
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-transform');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering
header('Access-Control-Allow-Origin: *'); // Allow cross-origin requests
header('Access-Control-Allow-Credentials: true'); // Allow credentials

// Prevent PHP from buffering
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', 1);
}
ini_set('zlib.output_compression', 0);
ini_set('output_buffering', 'off');
ini_set('implicit_flush', 1);
ob_implicit_flush(1);
ignore_user_abort(true); // Keep script running even if client disconnects

// Clear any existing output buffers
while (ob_get_level() > 0) {
    ob_end_flush();
}

// Set time limit to 5 minutes
set_time_limit(300);

// Function to send SSE message
function sendSSE($event, $data) {
    if (connection_aborted()) {
        exit();
    }
    
    $encodedData = json_encode($data);
    if ($encodedData === false) {
        $encodedData = json_encode(['error' => 'JSON encoding failed']);
    }
    
    echo "id: " . time() . "\n";
    echo "event: {$event}\n";
    echo "data: {$encodedData}\n\n";
    
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

try {
    $notificationManager = new NotificationManager();
    $lastCheck = time();
    $userId = $_SESSION['userId'];
    $connectionStartTime = time();
    $maxConnectionTime = 300; // 5 minutes max connection time
    $retryInterval = 3000; // Retry after 3 seconds if connection fails
    $keepaliveInterval = 5; // Send keepalive every 5 seconds
    $lastKeepalive = time();

    // Send retry interval
    echo "retry: {$retryInterval}\n\n";
    flush();

    // Initial unread count
    $unreadCount = $notificationManager->getUnreadCount($userId);
    sendSSE('count', ['unread' => $unreadCount]);

    // Keep connection alive and check for new notifications
    while (true) {
        if (connection_aborted()) {
            error_log('Client connection aborted');
            exit();
        }

        // Check if we've exceeded max connection time
        if (time() - $connectionStartTime > $maxConnectionTime) {
            sendSSE('close', ['message' => 'Connection timeout, reconnecting']);
            exit();
        }

        try {
            // Send keepalive if needed
            if (time() - $lastKeepalive >= $keepaliveInterval) {
                echo ": keepalive " . time() . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                $lastKeepalive = time();
            }

            // Check for new notifications
            $newNotifications = $notificationManager->getNewNotifications($userId, $lastCheck);
            
            if (!empty($newNotifications)) {
                foreach ($newNotifications as $notification) {
                    sendSSE('notification', $notification);
                }
                
                // Update unread count
                $unreadCount = $notificationManager->getUnreadCount($userId);
                sendSSE('count', ['unread' => $unreadCount]);
            }
            
            $lastCheck = time();
            
            // Small sleep to prevent excessive CPU usage
            usleep(100000); // 100ms sleep
        } catch (Exception $e) {
            error_log('Notification Check Error: ' . $e->getMessage());
            sendSSE('error', ['message' => 'Error checking notifications']);
            sleep(1); // Short wait before retrying
        }
    }
} catch (Exception $e) {
    error_log('Notification Stream Error: ' . $e->getMessage());
    sendSSE('error', ['message' => 'Error initializing notification stream']);
    http_response_code(500);
    exit();
} 