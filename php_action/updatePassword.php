<?php
require_once 'db_connect.php';
require_once 'core.php';

// Prevent any redirects
define('REQUIRE_LOGIN', false);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("\n\n=== Password Reset Process Started ===");
error_log("POST Data: " . print_r($_POST, true));

header('Content-Type: application/json');
$response = array('success' => false, 'messages' => '');

try {
    // Debug database connection
    if (!$connect) {
        error_log("Database connection failed");
        throw new Exception("Database connection failed");
    }
    error_log("Database connection successful");

    // Validate input
    if (empty($_POST['token']) || empty($_POST['password'])) {
        error_log("Missing fields - Token: " . (isset($_POST['token']) ? 'yes' : 'no') . 
                 ", Password: " . (isset($_POST['password']) ? 'yes' : 'no'));
        throw new Exception("Missing required fields");
    }

    $password = trim($_POST['password']);
    $token = trim($_POST['token']);
    
    error_log("Processing reset for token: " . $token);

    // Check if token exists first
    $checkTokenSql = "SELECT COUNT(*) as count FROM password_resets WHERE token = ?";
    $checkTokenStmt = $connect->prepare($checkTokenSql);
    $checkTokenStmt->bind_param("s", $token);
    $checkTokenStmt->execute();
    $tokenCount = $checkTokenStmt->get_result()->fetch_assoc()['count'];
    error_log("Token exists in database: " . ($tokenCount > 0 ? 'yes' : 'no'));

    // Begin transaction
    $connect->begin_transaction();
    error_log("Transaction started");

    try {
        // Check token validity
        $checkToken = $connect->prepare("
            SELECT pr.user_id, pr.expires_at, pr.used
            FROM password_resets pr
            WHERE pr.token = ? 
            AND pr.expires_at > NOW()
            AND pr.used = 0
        ");
        
        if (!$checkToken) {
            error_log("Prepare statement failed: " . $connect->error);
            throw new Exception("Database error: " . $connect->error);
        }
        
        $checkToken->bind_param("s", $token);
        $checkToken->execute();
        $result = $checkToken->get_result();
        
        error_log("Token query executed. Found rows: " . $result->num_rows);

        if ($result->num_rows !== 1) {
            $row = $result->fetch_assoc();
            error_log("Token validation failed. Details: " . print_r($row, true));
            throw new Exception("Invalid or expired reset token");
        }

        $userData = $result->fetch_assoc();
        $userId = $userData['user_id'];
        
        error_log("Valid token found for user ID: " . $userId);

        // Update password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updatePass = $connect->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $updatePass->bind_param("si", $hashedPassword, $userId);

        if (!$updatePass->execute()) {
            error_log("Failed to update password: " . $updatePass->error);
            throw new Exception("Failed to update password: " . $updatePass->error);
        }
        error_log("Password updated successfully");

        // Mark token as used
        $markUsed = $connect->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $markUsed->bind_param("s", $token);
        
        if (!$markUsed->execute()) {
            error_log("Failed to mark token as used: " . $markUsed->error);
            throw new Exception("Failed to mark token as used: " . $markUsed->error);
        }
        error_log("Token marked as used");

        $connect->commit();
        error_log("Transaction committed");
        
        $response['success'] = true;
        $response['messages'] = "Password updated successfully. Redirecting to login...";

    } catch (Exception $e) {
        error_log("Inner try-catch error: " . $e->getMessage());
        $connect->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Outer try-catch error: " . $e->getMessage());
    $response['messages'] = $e->getMessage();
}

error_log("Final response: " . print_r($response, true));
echo json_encode($response);
exit();