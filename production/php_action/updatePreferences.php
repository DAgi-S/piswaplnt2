<?php
require_once 'core.php';
require_once 'db_connect.php';

if ($_POST) {
    $response = array();
    
    $userId = $_SESSION['userId'];
    $language = $_POST['language'];
    $timezone = $_POST['timezone'];
    $notifyUpdates = isset($_POST['notify_updates']) ? 1 : 0;
    $notifyAlerts = isset($_POST['notify_alerts']) ? 1 : 0;
    $notifyReports = isset($_POST['notify_reports']) ? 1 : 0;

    // Validate language
    $allowedLanguages = ['en', 'es', 'fr'];
    if (!in_array($language, $allowedLanguages)) {
        $response['success'] = false;
        $response['message'] = 'Invalid language selection';
        echo json_encode($response);
        exit();
    }

    // Validate timezone
    if (!in_array($timezone, DateTimeZone::listIdentifiers())) {
        $response['success'] = false;
        $response['message'] = 'Invalid timezone selection';
        echo json_encode($response);
        exit();
    }

    // Start transaction
    $connect->begin_transaction();

    try {
        // Update user preferences
        $sql = "UPDATE users SET 
                language = ?, 
                timezone = ?, 
                notify_updates = ?,
                notify_alerts = ?,
                notify_reports = ?
                WHERE user_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("ssiiii", 
            $language, 
            $timezone, 
            $notifyUpdates,
            $notifyAlerts,
            $notifyReports,
            $userId
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error updating preferences: ' . $connect->error);
        }

        // Log the action
        $logSql = "INSERT INTO audit_log (user_id, activity_type, description, ip_address) VALUES (?, 'update_preferences', ?, ?)";
        $logStmt = $connect->prepare($logSql);
        $description = "Updated user preferences";
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $logStmt->bind_param("iss", $userId, $description, $ipAddress);
        
        if (!$logStmt->execute()) {
            throw new Exception('Error logging action: ' . $connect->error);
        }

        // Update session variables
        $_SESSION['language'] = $language;
        $_SESSION['timezone'] = $timezone;

        // Commit transaction
        $connect->commit();

        $response['success'] = true;
        $response['message'] = 'Preferences updated successfully';

    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    $stmt->close();
    $connect->close();

    echo json_encode($response);
} 