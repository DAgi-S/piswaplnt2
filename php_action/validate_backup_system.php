<?php
require_once 'core.php';
require_once 'classes/ConfigurationManager.php';

// Check permissions
if (!isset($_SESSION['userId']) || !isset($_SESSION['role_id'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

class BackupSystemValidator {
    private $config;
    private $errors = [];
    private $testResults = [];
    private $backupPath;

    public function __construct() {
        $this->config = ConfigurationManager::getInstance();
        $this->backupPath = $this->config->get('backup_path');
    }

    public function validate() {
        // Check backup directory permissions
        $this->checkDirectoryPermissions();

        // If directory checks pass, test backup creation
        if (empty($this->errors)) {
            $this->testBackupCreation();
        }

        return [
            'success' => empty($this->errors),
            'errors' => $this->errors,
            'test_results' => $this->testResults
        ];
    }

    private function checkDirectoryPermissions() {
        try {
            // Check if backup directory exists
            if (!file_exists($this->backupPath)) {
                if (!mkdir($this->backupPath, 0750, true)) {
                    throw new Exception('Failed to create backup directory');
                }
                $this->testResults[] = 'Backup directory created successfully';
            }

            // Check directory permissions
            if (!is_writable($this->backupPath)) {
                throw new Exception('Backup directory is not writable');
            }
            $this->testResults[] = 'Backup directory is writable';

            // Check subdirectories
            $subdirs = ['database', 'encryption_keys', 'config'];
            foreach ($subdirs as $dir) {
                $path = $this->backupPath . '/' . $dir;
                if (!file_exists($path)) {
                    if (!mkdir($path, 0750, true)) {
                        throw new Exception("Failed to create {$dir} directory");
                    }
                }
                if (!is_writable($path)) {
                    throw new Exception("{$dir} directory is not writable");
                }
                $this->testResults[] = "{$dir} directory is writable";
            }

        } catch (Exception $e) {
            $this->errors[] = 'Directory check failed: ' . $e->getMessage();
        }
    }

    private function testBackupCreation() {
        try {
            // Test database backup
            $this->testDatabaseBackup();

            // Test encryption key backup
            $this->testEncryptionKeyBackup();

            // Test configuration backup
            $this->testConfigBackup();

        } catch (Exception $e) {
            $this->errors[] = 'Backup test failed: ' . $e->getMessage();
        }
    }

    private function testDatabaseBackup() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->backupPath . '/database/test_backup_' . $timestamp . '.sql';

            // Create test database backup
            $command = sprintf(
                'mysqldump -h %s -P %s -u %s -p%s %s > %s',
                escapeshellarg($this->config->get('db_host')),
                escapeshellarg($this->config->get('db_port')),
                escapeshellarg($this->config->get('db_user')),
                escapeshellarg($this->config->get('db_password')),
                escapeshellarg($this->config->get('db_name')),
                escapeshellarg($backupFile)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new Exception('Database backup failed');
            }

            // Verify backup file
            if (!file_exists($backupFile) || filesize($backupFile) === 0) {
                throw new Exception('Backup file is empty or missing');
            }

            $this->testResults[] = 'Database backup created successfully';

            // Clean up test backup
            unlink($backupFile);

        } catch (Exception $e) {
            throw new Exception('Database backup test failed: ' . $e->getMessage());
        }
    }

    private function testEncryptionKeyBackup() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->backupPath . '/encryption_keys/test_keys_' . $timestamp . '.enc';
            $recoveryFile = $this->backupPath . '/encryption_keys/test_recovery_' . $timestamp . '.txt';

            // Create test encryption key backup
            $testData = [
                'test_key' => bin2hex(random_bytes(32)),
                'timestamp' => $timestamp
            ];

            $encrypted = openssl_encrypt(
                json_encode($testData),
                'AES-256-CBC',
                bin2hex(random_bytes(32)),
                OPENSSL_RAW_DATA,
                random_bytes(16)
            );

            if (file_put_contents($backupFile, $encrypted) === false) {
                throw new Exception('Failed to write encryption key backup');
            }

            // Create test recovery info
            $recoveryInfo = "Test Recovery Information\nTimestamp: " . $timestamp;
            if (file_put_contents($recoveryFile, $recoveryInfo) === false) {
                throw new Exception('Failed to write recovery information');
            }

            $this->testResults[] = 'Encryption key backup created successfully';

            // Clean up test files
            unlink($backupFile);
            unlink($recoveryFile);

        } catch (Exception $e) {
            throw new Exception('Encryption key backup test failed: ' . $e->getMessage());
        }
    }

    private function testConfigBackup() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->backupPath . '/config/test_config_' . $timestamp . '.json';

            // Create test configuration backup
            $configData = [
                'test_setting' => 'test_value',
                'timestamp' => $timestamp
            ];

            if (file_put_contents($backupFile, json_encode($configData)) === false) {
                throw new Exception('Failed to write configuration backup');
            }

            $this->testResults[] = 'Configuration backup created successfully';

            // Clean up test backup
            unlink($backupFile);

        } catch (Exception $e) {
            throw new Exception('Configuration backup test failed: ' . $e->getMessage());
        }
    }
}

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $validator = new BackupSystemValidator();
    $result = $validator->validate();
    
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 