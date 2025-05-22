<?php
require_once 'core.php';

// Check if user has admin privileges
if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

try {
    // Begin transaction
    $connect->begin_transaction();

    // Reset failed notifications to pending
    $sql = "UPDATE notification_queue 
            SET status = 'pending',
                attempts = 0,
                last_attempt = NULL,
                error_message = NULL
            WHERE status = 'failed'";
    
    $connect->query($sql);
    $affectedRows = $connect->affected_rows;

    // Log the retry action
    $sql = "INSERT INTO audit_log (user_id, activity_type, description) 
            VALUES (?, 'notification_retry', ?)";
    
    $description = "Retried {$affectedRows} failed notifications";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $_SESSION['userId'], $description);
    $stmt->execute();

    // Commit transaction
    $connect->commit();

    echo json_encode([
        'success' => true,
        'message' => "{$affectedRows} notifications queued for retry",
        'retried' => $affectedRows
    ]);

} catch (Exception $e) {
    // Rollback on error
    $connect->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 