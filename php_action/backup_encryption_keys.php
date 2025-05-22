<?php
require_once 'core.php';
require_once 'classes/ConfigurationEncryption.php';
require_once 'classes/ConfigurationManager.php';

// Check permissions
if (!isset($_SESSION['userId']) || !isset($_SESSION['role_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Access denied. Please log in.'
    ]));
}

class EncryptionKeyBackup {
    private $encryption;
    private $config;
    private $backupPath;
    private $log = [];

    public function __construct() {
        $this->encryption = ConfigurationEncryption::getInstance();
        $this->config = ConfigurationManager::getInstance();
        $this->backupPath = $this->config->get('backup_path') . '/encryption_keys';
    }

    public function backup() {
        try {
            // Create backup directory if it doesn't exist
            if (!file_exists($this->backupPath)) {
                mkdir($this->backupPath, 0700, true);
            }

            // Generate backup filename with timestamp
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->backupPath . '/keys_backup_' . $timestamp . '.enc';

            // Get current encryption keys
            $keys = [
                'primary_key' => $this->encryption->getPrimaryKey(),
                'secondary_keys' => $this->encryption->getSecondaryKeys(),
                'timestamp' => time(),
                'version' => '1.0'
            ];

            // Encrypt keys with a backup password
            $backupPassword = $this->generateBackupPassword();
            $encryptedKeys = openssl_encrypt(
                json_encode($keys),
                'AES-256-CBC',
                $backupPassword,
                0,
                substr(hash('sha256', $backupPassword), 0, 16)
            );

            // Save encrypted keys to file
            if (file_put_contents($backupFile, $encryptedKeys) === false) {
                throw new Exception('Failed to write backup file');
            }

            // Generate recovery info
            $recoveryInfo = [
                'backup_file' => basename($backupFile),
                'backup_password' => $backupPassword,
                'timestamp' => $timestamp,
                'checksum' => hash_file('sha256', $backupFile)
            ];

            // Save recovery info to secure location
            $recoveryFile = $this->backupPath . '/recovery_info_' . $timestamp . '.txt';
            $recoveryContent = "ENCRYPTION KEYS BACKUP RECOVERY INFORMATION\n" .
                             "=======================================\n" .
                             "Backup File: {$recoveryInfo['backup_file']}\n" .
                             "Backup Password: {$recoveryInfo['backup_password']}\n" .
                             "Timestamp: {$recoveryInfo['timestamp']}\n" .
                             "Checksum: {$recoveryInfo['checksum']}\n" .
                             "\nStore this information securely!";

            if (file_put_contents($recoveryFile, $recoveryContent) === false) {
                throw new Exception('Failed to write recovery information');
            }

            // Clean old backups
            $this->cleanOldBackups();

            return [
                'success' => true,
                'message' => 'Encryption keys backed up successfully',
                'recovery_file' => basename($recoveryFile)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ];
        }
    }

    public function restore($backupFile, $backupPassword) {
        try {
            $fullPath = $this->backupPath . '/' . basename($backupFile);
            
            if (!file_exists($fullPath)) {
                throw new Exception('Backup file not found');
            }

            // Read and decrypt backup
            $encryptedContent = file_get_contents($fullPath);
            $decryptedContent = openssl_decrypt(
                $encryptedContent,
                'AES-256-CBC',
                $backupPassword,
                0,
                substr(hash('sha256', $backupPassword), 0, 16)
            );

            if ($decryptedContent === false) {
                throw new Exception('Invalid backup password');
            }

            $keys = json_decode($decryptedContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid backup format');
            }

            // Verify backup version
            if (!isset($keys['version']) || version_compare($keys['version'], '1.0', '<')) {
                throw new Exception('Unsupported backup version');
            }

            // Restore keys
            $this->encryption->setPrimaryKey($keys['primary_key']);
            $this->encryption->setSecondaryKeys($keys['secondary_keys']);

            return [
                'success' => true,
                'message' => 'Encryption keys restored successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ];
        }
    }

    private function generateBackupPassword() {
        // Generate a strong random password
        $length = 32;
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }

    private function cleanOldBackups() {
        // Keep only last 5 backups
        $files = glob($this->backupPath . '/keys_backup_*.enc');
        $recoveryFiles = glob($this->backupPath . '/recovery_info_*.txt');
        
        if (count($files) > 5) {
            // Sort files by creation time
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            // Remove old files
            for ($i = 5; $i < count($files); $i++) {
                unlink($files[$i]);
                // Remove corresponding recovery file
                $timestamp = substr(basename($files[$i]), 12, 19);
                $recoveryFile = $this->backupPath . '/recovery_info_' . $timestamp . '.txt';
                if (file_exists($recoveryFile)) {
                    unlink($recoveryFile);
                }
            }
        }
    }
}

// Handle request
$action = $_POST['action'] ?? '';
$backup = new EncryptionKeyBackup();
$result = [];

switch ($action) {
    case 'backup':
        $result = $backup->backup();
        break;
        
    case 'restore':
        $backupFile = $_POST['backup_file'] ?? '';
        $backupPassword = $_POST['backup_password'] ?? '';
        if (empty($backupFile) || empty($backupPassword)) {
            $result = [
                'success' => false,
                'message' => 'Backup file and password are required'
            ];
        } else {
            $result = $backup->restore($backupFile, $backupPassword);
        }
        break;
        
    default:
        $result = [
            'success' => false,
            'message' => 'Invalid action'
        ];
}

// Output result
header('Content-Type: application/json');
echo json_encode($result); 