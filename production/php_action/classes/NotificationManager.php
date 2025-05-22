<?php
require_once 'core.php';

class NotificationManager {
    private $db;
    private $channels = [];

    public function __construct() {
        global $connect;
        $this->db = $connect;
        $this->initializeChannels();
    }

    private function initializeChannels() {
        $sql = "SELECT * FROM notification_channels WHERE is_active = 1";
        $result = $this->db->query($sql);
        
        while($row = $result->fetch_assoc()) {
            $this->channels[$row['type']] = json_decode($row['config'], true);
        }
    }

    // Get user preferences with channel information
    public function getUserPreferences($userId) {
        $sql = "SELECT unp.notification_type, nc.type as channel_type, unp.is_enabled 
                FROM user_notification_preferences unp
                LEFT JOIN notification_channels nc ON nc.channel_id = unp.channel_id
                WHERE unp.user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $preferences = [
            'channels' => [],
            'types' => []
        ];

        while ($row = $result->fetch_assoc()) {
            if ($row['channel_type']) {
                $preferences['channels'][$row['channel_type']] = (bool)$row['is_enabled'];
            } else {
                $preferences['types'][$row['notification_type']] = (bool)$row['is_enabled'];
            }
        }
        
        return $preferences;
    }

    // Get notification frequencies
    public function getNotificationFrequencies($userId) {
        $sql = "SELECT notification_type, 
                JSON_UNQUOTE(JSON_EXTRACT(config, '$.frequency')) as frequency
                FROM user_notification_preferences
                WHERE user_id = ? AND notification_type = 'frequency'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $frequencies = [];
        while ($row = $result->fetch_assoc()) {
            $frequencies[$row['notification_type']] = $row['frequency'];
        }
        
        return $frequencies;
    }

    // Get channel settings
    public function getChannelSettings($userId) {
        $sql = "SELECT nc.type, nc.config, COALESCE(unp.is_enabled, 1) as is_enabled
                FROM notification_channels nc
                LEFT JOIN user_notification_preferences unp 
                    ON unp.channel_id = nc.channel_id 
                    AND unp.user_id = ?
                WHERE nc.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['type']] = [
                'config' => json_decode($row['config'], true),
                'enabled' => (bool)$row['is_enabled']
            ];
        }
        
        return $settings;
    }

    public function updatePreference($userId, $type, $channel, $enabled) {
        // Get channel ID if updating channel preference
        $channelId = null;
        if ($type === 'channel') {
            $sql = "SELECT channel_id FROM notification_channels WHERE type = ? AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $channel);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $channelId = $row['channel_id'];
            }
        }

        // Check if preference exists
        $sql = "SELECT preference_id 
                FROM user_notification_preferences 
                WHERE user_id = ? 
                AND notification_type = ?
                AND " . ($channelId ? "channel_id = ?" : "channel_id IS NULL");
        
        $stmt = $this->db->prepare($sql);
        if ($channelId) {
            $stmt->bind_param("isi", $userId, $type, $channelId);
        } else {
            $stmt->bind_param("is", $userId, $type);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Update existing preference
            $sql = "UPDATE user_notification_preferences 
                    SET is_enabled = ?
                    WHERE preference_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $enabled, $row['preference_id']);
        } else {
            // Insert new preference
            $sql = "INSERT INTO user_notification_preferences 
                    (user_id, notification_type, channel_id, is_enabled) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("isii", $userId, $type, $channelId, $enabled);
        }

        return $stmt->execute();
    }

    public function createNotification($title, $message, $userId, $priority = 'normal', $type = 'system') {
        $sql = "INSERT INTO notifications 
                (type, title, message, priority, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssss", 
            $type,
            $title,
            $message,
            $priority
        );
        
        if ($stmt->execute()) {
            $notificationId = $stmt->insert_id;
            
            // Create user notification mapping
            $sql = "INSERT INTO user_notifications 
                    (notification_id, user_id, is_read, delivery_status) 
                    VALUES (?, ?, 0, 'pending')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $notificationId, $userId);
            
            if ($stmt->execute()) {
                return $notificationId;
            }
        }
        
        return false;
    }

    public function getUserNotifications($userId, $filters = []) {
        $sql = "SELECT n.*, un.is_read, un.read_at
                FROM notifications n
                JOIN user_notifications un ON un.notification_id = n.notification_id
                WHERE un.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT 50";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count
                FROM user_notifications
                WHERE user_id = ? AND is_read = 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return (int)$row['count'];
        }
        
        return 0;
    }

    public function getNewNotifications($userId, $lastCheck) {
        $sql = "SELECT n.*, un.is_read
                FROM notifications n
                JOIN user_notifications un ON un.notification_id = n.notification_id
                WHERE un.user_id = ? AND n.created_at > FROM_UNIXTIME(?)
                ORDER BY n.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $userId, $lastCheck);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function markAsRead($notificationId, $userId) {
        $sql = "UPDATE user_notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE notification_id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $notificationId, $userId);
        return $stmt->execute();
    }

    public function updateUserPreferences($userId, $preferences) {
        $success = true;
        
        // Begin transaction
        $this->db->begin_transaction();
        
        try {
            // Update channel preferences
            if (isset($preferences['channels'])) {
                foreach ($preferences['channels'] as $channel => $enabled) {
                    $success = $success && $this->updatePreference($userId, 'channel', $channel, $enabled);
                }
            }
            
            // Update notification type preferences
            if (isset($preferences['types'])) {
                foreach ($preferences['types'] as $type => $enabled) {
                    $success = $success && $this->updatePreference($userId, $type, null, $enabled);
                }
            }
            
            if ($success) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
} 