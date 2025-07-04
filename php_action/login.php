<?php 
require_once 'db_connect.php';
require_once 'telegram_notification_helper.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set header to return JSON
header('Content-Type: application/json');

$response = array();

try {
    if(!$_POST) {
        throw new Exception('Invalid request method');
    }

    if(!isset($_POST['username']) || !isset($_POST['password'])) {
        throw new Exception('Username and password are required');
    }

    $username = mysqli_real_escape_string($connect, $_POST['username']);
    $password = $_POST['password'];
    
    // Validate database connection
    if($connect->connect_error) {
        throw new Exception('Database connection failed: ' . $connect->connect_error);
    }
    
    $sql = "SELECT u.*, ur.role_name, ur.role_id 
            FROM users u
            LEFT JOIN user_roles ur ON u.role_id = ur.role_id
            WHERE u.username = ? AND u.status = 1";
            
    $stmt = $connect->prepare($sql);
    if(!$stmt) {
        throw new Exception('Query preparation failed: ' . $connect->error);
    }
    
    $stmt->bind_param("s", $username);
    if(!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // Debug password verification
        error_log("Attempting to verify password for user: " . $username);
        
        if(password_verify($password, $row['password'])) {
            // Set session variables
            $_SESSION['userId'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role_name'];
            $_SESSION['userRole'] = $row['role_name'];
            $_SESSION['roleId'] = $row['role_id'];
            
            // Update session info in database
            $session_id = session_id();
            $user_id = $row['user_id'];
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            
            $session_sql = "REPLACE INTO sessions (session_id, user_id, ip_address, user_agent, last_activity) 
                           VALUES (?, ?, ?, ?, NOW())";
            $session_stmt = $connect->prepare($session_sql);
            $session_stmt->bind_param("siss", $session_id, $user_id, $ip_address, $user_agent);
            $session_stmt->execute();
            
            // Send Telegram notification about new login using our new system
            sendTelegramNotification('user_login', [
                'username' => $row['username'],
                'role' => $row['role_name'],
                'ip_address' => $ip_address,
                'device' => $user_agent,
                'login_time' => date('Y-m-d H:i:s')
            ]);
            
            // Log successful login
            logUserActivity($row['user_id'], 'login', 'Successful login');
            
            $response['success'] = true;
            $response['messages'] = "Login Successful";
            $response['data'] = array(
                'userId' => $row['user_id'],
                'username' => $row['username'],
                'role' => $row['role_name']
            );

            // --- Low Stock Notification Logic ---
            $lowStockSql = "SELECT id, material_code, name, current_stock, unit FROM raw_materials WHERE current_stock <= 500 AND status = 'active'";
            $lowStockResult = $connect->query($lowStockSql);
            $lowStockItems = [];
            if ($lowStockResult && $lowStockResult->num_rows > 0) {
                require_once __DIR__ . '/../production/notifications/notification_model.php';
                $notificationModel = new NotificationModel($connect);
                while ($item = $lowStockResult->fetch_assoc()) {
                    $title = 'Low Stock Alert: ' . $item['material_code'];
                    $message = 'Raw material ' . $item['name'] . ' (Code: ' . $item['material_code'] . ') is low: ' . $item['current_stock'] . ' ' . $item['unit'] . ' left.';
                    $link = 'raw_materials.php';
                    $priority = 'high';
                    $icon = 'fa-exclamation-triangle';
                    $type = 'low_stock';
                    // Check if a notification for this item already exists today (unique by material_code)
                    $checkSql = "SELECT notification_id FROM notifications WHERE type = 'low_stock' AND title = ? AND DATE(created_at) = CURDATE()";
                    $checkStmt = $connect->prepare($checkSql);
                    $checkStmt->bind_param("s", $title);
                    $checkStmt->execute();
                    $checkStmt->store_result();
                    if ($checkStmt->num_rows == 0) {
                        $stmt = $connect->prepare("INSERT INTO notifications (user_id, type, title, message, priority, icon, link) VALUES (NULL, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssss", $type, $title, $message, $priority, $icon, $link);
                        $stmt->execute();
                        $stmt->close();
                    }
                    $checkStmt->close();
                    $lowStockItems[] = $item;
                }
            }
            $response['low_stock'] = $lowStockItems;
            // --- End Low Stock Notification Logic ---
        } else {
            // Log failed login attempt
            if(isset($row['user_id'])) {
                logUserActivity($row['user_id'], 'login_failed', 'Invalid password');
                
                // Count failed attempts
                $failedAttempts = countFailedLoginAttempts($username);
                
                // Send Telegram notification about failed login using our new system
                sendTelegramNotification('login_failed', [
                    'username' => $username,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'device' => $_SERVER['HTTP_USER_AGENT'],
                    'attempt_time' => date('Y-m-d H:i:s'),
                    'attempt_count' => $failedAttempts
                ]);
            }
            error_log("Password verification failed for user: " . $username);
            $response['success'] = false;
            $response['messages'] = "Incorrect username/password combination";
        }
    } else {
        error_log("No user found with username: " . $username);
        $response['success'] = false;
        $response['messages'] = "Incorrect username/password combination";
    }
    
} catch(Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $response['success'] = false;
    $response['messages'] = "An error occurred: " . $e->getMessage();
}

// Always return a JSON response
echo json_encode($response);
exit();

// Helper function to log user activity
function logUserActivity($userId, $action, $description) {
    global $connect;
    try {
        $stmt = $connect->prepare("INSERT INTO user_activity_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        if(!$stmt) {
            throw new Exception("Failed to prepare log statement");
        }
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $stmt->bind_param("isss", $userId, $action, $description, $ipAddress);
        $stmt->execute();
    } catch(Exception $e) {
        error_log("Failed to log user activity: " . $e->getMessage());
    }
}

// Count failed login attempts for a username
function countFailedLoginAttempts($username) {
    global $connect;
    try {
        // Count attempts in the last 24 hours
        $sql = "SELECT COUNT(*) as attempt_count FROM user_activity_log 
                WHERE action = 'login_failed' 
                AND description LIKE ? 
                AND created_at > (NOW() - INTERVAL 24 HOUR)";
        $stmt = $connect->prepare($sql);
        $likeParam = '%' . $username . '%';
        $stmt->bind_param("s", $likeParam);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            return $row['attempt_count'] + 1; // +1 for current attempt
        }
    } catch(Exception $e) {
        error_log("Failed to count login attempts: " . $e->getMessage());
    }
    return 1; // Default to 1 if we can't count
} 