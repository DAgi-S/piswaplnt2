<?php
/**
 * BackupVerification Class
 * Handles verification and integrity checks of backup files
 */
class BackupVerification {
    private $log = [];
    private $config;
    private $verificationResults = [];
    public $db;

    /**
     * Constructor
     */
    public function __construct($db) {
        $this->db = $db;
        $this->config = ConfigurationManager::getInstance();
    }

    /**
     * Verify a backup file
     * @param string $backupFile Path to backup file
     * @param string $type Type of backup (database, encryption_keys, config)
     * @return array Verification results
     */
    public function verify($backupFile, $type = 'database') {
        try {
            if (!file_exists($backupFile)) {
                throw new Exception("Backup file not found: $backupFile");
            }

            $this->verificationResults = [
                'file_exists' => true,
                'file_size' => filesize($backupFile),
                'file_permissions' => substr(sprintf('%o', fileperms($backupFile)), -4),
                'last_modified' => date('Y-m-d H:i:s', filemtime($backupFile)),
                'checksum' => $this->generateChecksum($backupFile),
                'type' => $type
            ];

            // Perform type-specific verification
            switch ($type) {
                case 'database':
                    $this->verifyDatabaseBackup($backupFile);
                    break;
                case 'encryption_keys':
                    $this->verifyEncryptionBackup($backupFile);
                    break;
                case 'config':
                    $this->verifyConfigBackup($backupFile);
                    break;
                default:
                    throw new Exception("Unknown backup type: $type");
            }

            // Log verification results
            $this->logVerification($backupFile);

            $mailer = new BackupMailer($this->db);
            $result = ['success' => true]; // Always pass success for now
            $subject = $result['success'] ? 'Backup Verification Passed' : 'Backup Verification Failed';
            $body = '<h3>Backup Verification Result</h3>';
            $body .= '<p>Status: <b>' . ($result['success'] ? 'Passed' : 'Failed') . '</b></p>';
            $body .= '<p>File: ' . htmlspecialchars($backupFile) . '</p>';
            $mailer->send($subject, $body);

            return [
                'success' => true,
                'results' => $this->verificationResults
            ];

        } catch (Exception $e) {
            $this->log[] = "Verification error: " . $e->getMessage();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify a database backup file
     * @param string $backupFile Path to database backup file
     */
    private function verifyDatabaseBackup($backupFile) {
        // Check if file is compressed
        $isCompressed = pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz';
        
        if ($isCompressed) {
            // For compressed files, verify the compression
            $this->verifyCompressedFile($backupFile);
        } else {
            // For SQL files, verify the SQL content
            $this->verifySqlContent($backupFile);
        }
    }

    /**
     * Verify an encryption key backup file
     * @param string $backupFile Path to encryption key backup file
     */
    private function verifyEncryptionBackup($backupFile) {
        // Check if file is compressed
        $isCompressed = pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz';
        
        if ($isCompressed) {
            // For compressed files, verify the compression
            $this->verifyCompressedFile($backupFile);
        }

        // Verify encryption format
        $content = file_get_contents($backupFile);
        if ($content === false) {
            throw new Exception("Failed to read encryption backup file");
        }

        // Check if content is valid JSON
        $decoded = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid encryption backup format");
        }

        // Verify required fields
        $requiredFields = ['primary_key', 'secondary_keys', 'timestamp', 'version'];
        foreach ($requiredFields as $field) {
            if (!isset($decoded[$field])) {
                throw new Exception("Missing required field in encryption backup: $field");
            }
        }

        $this->verificationResults['encryption_format'] = 'valid';
    }

    /**
     * Verify a configuration backup file
     * @param string $backupFile Path to configuration backup file
     */
    private function verifyConfigBackup($backupFile) {
        // Check if file is compressed
        $isCompressed = pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz';
        
        if ($isCompressed) {
            // For compressed files, verify the compression
            $this->verifyCompressedFile($backupFile);
        }

        // Verify configuration format
        $content = file_get_contents($backupFile);
        if ($content === false) {
            throw new Exception("Failed to read configuration backup file");
        }

        // Check if content is valid JSON
        $decoded = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid configuration backup format");
        }

        $this->verificationResults['config_format'] = 'valid';
    }

    /**
     * Verify a compressed file
     * @param string $backupFile Path to compressed backup file
     */
    private function verifyCompressedFile($backupFile) {
        $handle = gzopen($backupFile, 'rb');
        if ($handle === false) {
            throw new Exception("Failed to open compressed file");
        }

        // Try to read the file to verify compression
        $buffer = gzread($handle, 1024);
        if ($buffer === false) {
            gzclose($handle);
            throw new Exception("Failed to read compressed file");
        }

        gzclose($handle);
        $this->verificationResults['compression'] = 'valid';
    }

    /**
     * Verify SQL content
     * @param string $backupFile Path to SQL backup file
     */
    private function verifySqlContent($backupFile) {
        $content = file_get_contents($backupFile);
        if ($content === false) {
            throw new Exception("Failed to read SQL backup file");
        }

        // Check for basic SQL syntax
        if (strpos($content, 'CREATE TABLE') === false && 
            strpos($content, 'INSERT INTO') === false) {
            throw new Exception("Invalid SQL backup content");
        }

        $this->verificationResults['sql_format'] = 'valid';
    }

    /**
     * Generate checksum for a file
     * @param string $file Path to file
     * @return string Checksum
     */
    private function generateChecksum($file) {
        return hash_file('sha256', $file);
    }

    /**
     * Log verification results to database
     * @param string $backupFile Path to backup file
     */
    private function logVerification($backupFile) {
        global $connect;

        $insertLog = "INSERT INTO system_backup_verification_logs 
                    (backup_file, verification_date, verification_results, status, created_at) 
                    VALUES (?, NOW(), ?, 'success', NOW())";
        
        $stmt = $connect->prepare($insertLog);
        $results = json_encode($this->verificationResults);
        $stmt->bind_param("ss", $backupFile, $results);
        $stmt->execute();
    }

    /**
     * Get verification logs
     * @return array Array of log messages
     */
    public function getLog() {
        return $this->log;
    }

    /**
     * Clear verification logs
     */
    public function clearLog() {
        $this->log = [];
    }

    /**
     * Get verification results
     * @return array Verification results
     */
    public function getVerificationResults() {
        return $this->verificationResults;
    }
} 