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

// Check if archive ID is provided
if (!isset($_GET['archive_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Archive ID is required'
    ]);
    exit();
}

try {
    $archiveId = (int)$_GET['archive_id'];
    
    $sql = "SELECT 
                na.*,
                u.username,
                u.email
            FROM notification_archives na
            LEFT JOIN users u ON na.user_id = u.user_id
            WHERE na.archive_id = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $archiveId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Add additional analytics for this notification
        $row['delivery_time'] = null;
        if ($row['delivered_at'] && $row['created_at']) {
            $created = new DateTime($row['created_at']);
            $delivered = new DateTime($row['delivered_at']);
            $diff = $created->diff($delivered);
            $row['delivery_time'] = $diff->format('%H:%I:%S');
        }

        // Get channel configuration at the time (if stored in metadata)
        if ($row['metadata']) {
            $metadata = json_decode($row['metadata'], true);
            // Remove sensitive information from metadata
            if (isset($metadata['config'])) {
                unset($metadata['config']['password']);
                unset($metadata['config']['auth_token']);
                unset($metadata['config']['api_key']);
            }
            $row['metadata'] = json_encode($metadata);
        }

        echo json_encode([
            'success' => true,
            'data' => $row
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Archive not found'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 