<?php
require_once 'core.php';
require_once 'classes/NotificationArchiveManager.php';

// Check if user has admin privileges
if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

try {
    $archiveManager = new NotificationArchiveManager();
    
    // Start transaction
    $connect->begin_transaction();

    // Get count of archives to be deleted
    $sql = "SELECT COUNT(*) as count 
            FROM notification_archives 
            WHERE archived_at < DATE_SUB(NOW(), INTERVAL 365 DAY)";
    
    $result = $connect->query($sql);
    $count = $result->fetch_assoc()['count'];

    // Delete old archives
    $success = $archiveManager->deleteOldArchives();

    if ($success) {
        // Log the cleanup action
        $sql = "INSERT INTO audit_log (user_id, activity_type, description) 
                VALUES (?, 'archive_cleanup', ?)";
        
        $description = "Cleaned up {$count} archived notifications older than 365 days";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("is", $_SESSION['userId'], $description);
        $stmt->execute();

        // Commit transaction
        $connect->commit();

        echo json_encode([
            'success' => true,
            'message' => $description,
            'deleted_count' => $count
        ]);
    } else {
        throw new Exception('Failed to clean up archives');
    }

} catch (Exception $e) {
    // Rollback on error
    $connect->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 