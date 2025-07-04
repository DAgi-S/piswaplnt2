<?php
require_once 'db_connect.php';
require_once 'core.php';
require_once 'classes/backup/BackupVerification.php';

// Check permissions
if (!isset($_SESSION['userId'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

class BackupMonitoring {
    private $connect;
    private $backupPath;

    public function __construct($connect) {
        $this->connect = $connect;
        $this->backupPath = dirname(__FILE__, 2) . '/backups/';
    }

    /**
     * Get backup statistics
     * @return array Backup statistics
     */
    public function getBackupStats() {
        $stats = [
            'total_backups' => 0,
            'total_size' => 0,
            'last_backup' => null,
            'success_rate' => 0,
            'storage_usage' => 0,
            'backup_types' => [
                'database' => 0,
                'encryption_keys' => 0,
                'config' => 0
            ]
        ];

        // Get total backups and size
        $query = "SELECT COUNT(*) as total, SUM(size_in_bytes) as total_size 
                 FROM system_backup_logs";
        $result = $this->connect->query($query);
        if ($row = $result->fetch_assoc()) {
            $stats['total_backups'] = $row['total'];
            $stats['total_size'] = $row['total_size'] ?? 0;
        }

        // Get last backup
        $query = "SELECT backup_date, file_path, size_in_bytes, compression_ratio 
                 FROM system_backup_logs 
                 ORDER BY backup_date DESC LIMIT 1";
        $result = $this->connect->query($query);
        if ($row = $result->fetch_assoc()) {
            $stats['last_backup'] = $row;
        }

        // Calculate success rate
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful
                 FROM system_backup_logs";
        $result = $this->connect->query($query);
        if (($row = $result->fetch_assoc()) && $row['total'] > 0) {
            $stats['success_rate'] = round(($row['successful'] / $row['total']) * 100, 2);
        }

        // Calculate storage usage
        $totalSpace = disk_total_space($this->backupPath);
        $freeSpace = disk_free_space($this->backupPath);
        $usedSpace = $totalSpace - $freeSpace;
        $usagePercent = 0;
        if ($totalSpace > 0) {
            $usagePercent = ($usedSpace / $totalSpace) * 100;
        }
        $stats['storage_usage'] = $usagePercent;

        // Get backup types count
        $query = "SELECT backup_type, COUNT(*) as count 
                 FROM system_backup_logs 
                 GROUP BY backup_type";
        $result = $this->connect->query($query);
        while ($row = $result->fetch_assoc()) {
            if (isset($stats['backup_types'][$row['backup_type']])) {
                $stats['backup_types'][$row['backup_type']] = $row['count'];
            }
        }

        return $stats;
    }

    /**
     * Get recent backup history
     * @param int $limit Number of recent backups to return
     * @return array Recent backup history
     */
    public function getRecentBackups($limit = 10) {
        $query = "SELECT 
                    bl.backup_date,
                    bl.backup_type,
                    bl.file_path,
                    bl.size_in_bytes,
                    bl.compression_ratio,
                    bl.status,
                    bv.verification_results
                 FROM system_backup_logs bl
                 LEFT JOIN system_backup_verification_logs bv 
                    ON bl.file_path = bv.backup_file
                 ORDER BY bl.backup_date DESC
                 LIMIT ?";
        
        $stmt = $this->connect->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $backups = [];
        while ($row = $result->fetch_assoc()) {
            $row['verification_results'] = isset($row['verification_results']) && $row['verification_results'] ? @json_decode($row['verification_results'], true) : null;
            $backups[] = $row;
        }

        return $backups;
    }

    /**
     * Get backup health status
     * @return array Backup health status
     */
    public function getBackupHealth() {
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'last_check' => date('Y-m-d H:i:s')
        ];

        // Check if backups are being created regularly
        $query = "SELECT backup_date 
                 FROM system_backup_logs 
                 ORDER BY backup_date DESC 
                 LIMIT 1";
        $result = $this->connect->query($query);
        if ($row = $result->fetch_assoc()) {
            $lastBackup = strtotime($row['backup_date']);
            $hoursSinceLastBackup = (time() - $lastBackup) / 3600;
            
            if ($hoursSinceLastBackup > 24) {
                $health['status'] = 'warning';
                $health['issues'][] = "No backup created in the last 24 hours";
            }
        }

        // Check for failed backups
        $query = "SELECT COUNT(*) as failed 
                 FROM system_backup_logs 
                 WHERE status = 'failed' 
                 AND backup_date > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $result = $this->connect->query($query);
        if (($row = $result->fetch_assoc()) && $row['failed'] > 0) {
            $health['status'] = 'warning';
            $health['issues'][] = "{$row['failed']} failed backups in the last 24 hours";
        }

        // Check storage space
        $freeSpace = disk_free_space($this->backupPath);
        $totalSpace = disk_total_space($this->backupPath);
        $usedSpace = $totalSpace - $freeSpace;
        $usagePercent = 0;
        if ($totalSpace > 0) {
            $usagePercent = ($usedSpace / $totalSpace) * 100;
        }

        if ($usagePercent > 90) {
            $health['status'] = 'critical';
            $health['issues'][] = "Backup storage is almost full ({$usagePercent}% used)";
        } elseif ($usagePercent > 75) {
            $health['status'] = 'warning';
            $health['issues'][] = "Backup storage is getting full ({$usagePercent}% used)";
        }

        // Check verification status
        $query = "SELECT COUNT(*) as total, 
                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                 FROM system_backup_verification_logs
                 WHERE verification_date > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $result = $this->connect->query($query);
        if (($row = $result->fetch_assoc()) && $row['total'] > 0) {
            $failureRate = ($row['failed'] / $row['total']) * 100;
            if ($failureRate > 0) {
                $health['status'] = 'warning';
                $health['issues'][] = "{$failureRate}% of recent backups failed verification";
            }
        }

        return $health;
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $monitoring = new BackupMonitoring($connect);
        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'stats':
                $data = $monitoring->getBackupStats();
                break;
            case 'recent':
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $data = $monitoring->getRecentBackups($limit);
                break;
            case 'health':
                $data = $monitoring->getBackupHealth();
                break;
            default:
                $data = [
                    'success' => false,
                    'message' => 'Invalid action specified'
                ];
        }
        header('Content-Type: application/json');
        echo json_encode($data);
    } catch (Throwable $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
} 