<?php
require_once 'core.php';

class NotificationArchiveManager {
    private $db;
    private $itemsPerPage = 50;

    public function __construct() {
        global $connect;
        $this->db = $connect;
    }

    /**
     * Get archived notifications with pagination and filtering
     */
    public function getArchivedNotifications($page = 1, $filters = []) {
        $offset = ($page - 1) * $this->itemsPerPage;
        $whereConditions = [];
        $params = [];
        $types = '';

        // Build where conditions based on filters
        if (!empty($filters['user_id'])) {
            $whereConditions[] = 'user_id = ?';
            $params[] = $filters['user_id'];
            $types .= 'i';
        }
        if (!empty($filters['type'])) {
            $whereConditions[] = 'type = ?';
            $params[] = $filters['type'];
            $types .= 's';
        }
        if (!empty($filters['status'])) {
            $whereConditions[] = 'status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'created_at >= ?';
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'created_at <= ?';
            $params[] = $filters['date_to'];
            $types .= 's';
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM notification_archives $whereClause";
        $stmt = $this->db->prepare($countSql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $totalCount = $stmt->get_result()->fetch_assoc()['total'];

        // Get archived notifications
        $sql = "SELECT 
                    na.*,
                    u.username,
                    u.email
                FROM notification_archives na
                LEFT JOIN users u ON na.user_id = u.user_id
                $whereClause
                ORDER BY archived_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $params[] = $this->itemsPerPage;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'total' => $totalCount,
            'page' => $page,
            'total_pages' => ceil($totalCount / $this->itemsPerPage),
            'items' => $results
        ];
    }

    /**
     * Get statistics about archived notifications
     */
    public function getArchiveStatistics($dateRange = '30d') {
        $dateCondition = $this->getDateCondition($dateRange);
        
        $sql = "SELECT 
                    COUNT(*) as total_notifications,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT type) as notification_types
                FROM notification_archives
                WHERE $dateCondition";

        $result = $this->db->query($sql);
        return $result->fetch_assoc();
    }

    /**
     * Export archived notifications to CSV
     */
    public function exportToCSV($filters = []) {
        $whereConditions = [];
        $params = [];
        $types = '';

        // Build where conditions similar to getArchivedNotifications
        // ... [similar filter building logic as above] ...

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "SELECT 
                    na.*,
                    u.username,
                    u.email
                FROM notification_archives na
                LEFT JOIN users u ON na.user_id = u.user_id
                $whereClause
                ORDER BY archived_at DESC";

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $results = $stmt->get_result();

        $filename = 'notification_archives_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Archive ID', 'Type', 'Title', 'Message', 'Priority',
            'Channel', 'Status', 'Created At', 'Delivered At',
            'Archived At', 'Username', 'Email'
        ]);

        while ($row = $results->fetch_assoc()) {
            fputcsv($output, [
                $row['archive_id'],
                $row['type'],
                $row['title'],
                $row['message'],
                $row['priority'],
                $row['channel'],
                $row['status'],
                $row['created_at'],
                $row['delivered_at'],
                $row['archived_at'],
                $row['username'],
                $row['email']
            ]);
        }

        fclose($output);
        exit();
    }

    /**
     * Delete archived notifications older than specified days
     */
    public function deleteOldArchives($days = 365) {
        $sql = "DELETE FROM notification_archives 
                WHERE archived_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $days);
        return $stmt->execute();
    }

    /**
     * Get date condition for statistics queries
     */
    private function getDateCondition($range) {
        switch ($range) {
            case '7d':
                return 'archived_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
            case '30d':
                return 'archived_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
            case '90d':
                return 'archived_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)';
            case '1y':
                return 'archived_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
            default:
                return 'archived_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
        }
    }
} 