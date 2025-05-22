<?php
require_once 'core.php';
require_once 'classes/NotificationManager.php';
require_once 'classes/AdminMailer.php';
require_once 'config/mail_config.php';

class NotificationQueueProcessor {
    private $db;
    private $notificationManager;
    private $adminMailer;
    private $maxAttempts = 3;
    private $batchSize = 50;
    private $sleepTime = 10; // seconds between batches
    private $lastDailySummary = null;

    public function __construct() {
        global $connect;
        $this->db = $connect;
        $this->notificationManager = new NotificationManager();
        $this->adminMailer = new AdminMailer();
        $this->lastDailySummary = time();
    }

    public function processQueue() {
        while (true) {
            try {
                // Process pending notifications
                $pendingNotifications = $this->getPendingNotifications();
                
                if (!empty($pendingNotifications)) {
                    foreach ($pendingNotifications as $notification) {
                        $this->processNotification($notification);
                    }

                    // Check for failures and notify admins if needed
                    $this->checkFailuresAndNotify();
                }

                // Send daily summary if it's time
                $this->sendDailySummaryIfDue();

                // Clean up old completed notifications
                $this->cleanupOldNotifications();

                // Sleep before next batch
                sleep($this->sleepTime);

            } catch (Exception $e) {
                error_log("Error processing notification queue: " . $e->getMessage());
                sleep($this->sleepTime);
            }
        }
    }

    private function getPendingNotifications() {
        $sql = "SELECT nq.*, n.type, n.title, n.message, n.priority, u.email, u.phone, 
                       CONCAT(u.username, ' (', u.email, ')') as user_name
                FROM notification_queue nq
                JOIN notifications n ON n.notification_id = nq.notification_id
                JOIN users u ON u.user_id = nq.user_id
                WHERE nq.status = 'pending' 
                AND nq.attempts < ?
                AND (nq.last_attempt IS NULL OR nq.last_attempt < DATE_SUB(NOW(), INTERVAL 5 MINUTE))
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $this->maxAttempts, $this->batchSize);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function processNotification($queueItem) {
        // Update status to processing
        $this->updateQueueStatus($queueItem['queue_id'], 'processing');

        try {
            // Attempt to deliver the notification
            $success = $this->notificationManager->deliverNotification(
                $queueItem['notification_id'],
                $queueItem['user_id'],
                $queueItem['channel']
            );

            if ($success) {
                $this->updateQueueStatus($queueItem['queue_id'], 'completed');
            } else {
                throw new Exception("Delivery failed");
            }

        } catch (Exception $e) {
            $attempts = $queueItem['attempts'] + 1;
            $status = $attempts >= $this->maxAttempts ? 'failed' : 'pending';
            
            $this->updateQueueStatus(
                $queueItem['queue_id'], 
                $status,
                $attempts,
                $e->getMessage()
            );
        }
    }

    private function checkFailuresAndNotify() {
        if (!NOTIFY_ADMIN_ON_FAILURES) {
            return;
        }

        $sql = "SELECT nq.*, n.type, CONCAT(u.username, ' (', u.email, ')') as user_name
                FROM notification_queue nq
                JOIN notifications n ON n.notification_id = nq.notification_id
                JOIN users u ON u.user_id = nq.user_id
                WHERE nq.status = 'failed'
                AND nq.attempts >= ?
                AND (
                    nq.last_attempt > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    OR nq.notification_sent IS NULL
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", FAILURE_NOTIFICATION_THRESHOLD);
        $stmt->execute();
        $failedItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (!empty($failedItems)) {
            // Send notification to admin
            $this->adminMailer->sendFailureNotification($failedItems);

            // Mark notifications as sent to admin
            $ids = array_column($failedItems, 'queue_id');
            $idList = implode(',', $ids);
            $this->db->query("UPDATE notification_queue 
                             SET notification_sent = NOW() 
                             WHERE queue_id IN ($idList)");
        }
    }

    private function sendDailySummaryIfDue() {
        // Check if 24 hours have passed since last summary
        if (time() - $this->lastDailySummary >= 86400) {
            // Get queue statistics
            $stats = $this->getQueueStats();
            
            // Send daily summary
            $this->adminMailer->sendDailySummary($stats);
            
            // Update last summary time
            $this->lastDailySummary = time();
        }
    }

    private function getQueueStats() {
        $stats = [
            'total' => 0,
            'completed' => 0,
            'failed' => 0,
            'avg_attempts' => 0,
            'unique_users' => 0,
            'active_channels' => 0
        ];

        // Get basic statistics
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    AVG(attempts) as avg_attempts,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT channel) as active_channels
                FROM notification_queue
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";

        $result = $this->db->query($sql);
        if ($row = $result->fetch_assoc()) {
            $stats = array_merge($stats, $row);
        }

        return $stats;
    }

    private function updateQueueStatus($queueId, $status, $attempts = null, $errorMessage = null) {
        $sql = "UPDATE notification_queue 
                SET status = ?,
                    attempts = COALESCE(?, attempts + 1),
                    last_attempt = NOW(),
                    error_message = ?
                WHERE queue_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sisi", $status, $attempts, $errorMessage, $queueId);
        $stmt->execute();
    }

    private function cleanupOldNotifications() {
        // Remove completed notifications older than 7 days
        $sql = "DELETE FROM notification_queue 
                WHERE status = 'completed' 
                AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $this->db->query($sql);
    }
}

// Run the processor
$processor = new NotificationQueueProcessor();
$processor->processQueue(); 