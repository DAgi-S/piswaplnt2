<?php
class BackupRetention {
    private $db;
    private $policy;
    private $backupDir;

    public function __construct($db, $backupDir = '../backups/') {
        $this->db = $db;
        $this->backupDir = $backupDir;
        $this->policy = $this->loadPolicy();
    }

    private function loadPolicy() {
        $result = $this->db->query("SELECT * FROM system_backup_retention_policy WHERE enabled = 1 ORDER BY id DESC LIMIT 1");
        return $result ? $result->fetch_assoc() : ['keep_last_n' => 7, 'keep_days' => 30];
    }

    public function enforce() {
        // Get all backup files from DB
        $result = $this->db->query("SELECT id, file_path, backup_date FROM system_backup_logs ORDER BY backup_date DESC");
        $backups = [];
        while ($row = $result->fetch_assoc()) {
            $backups[] = $row;
        }
        // Keep only the most recent N
        $toDelete = array_slice($backups, $this->policy['keep_last_n']);
        $now = time();
        foreach ($toDelete as $backup) {
            $file = $this->backupDir . $backup['file_path'];
            $ageDays = (strtotime($backup['backup_date']) > 0) ? (($now - strtotime($backup['backup_date'])) / 86400) : 0;
            if ($ageDays > $this->policy['keep_days']) {
                if (file_exists($file)) unlink($file);
                $this->db->query("DELETE FROM system_backup_logs WHERE id = " . intval($backup['id']));
            }
        }
    }
} 