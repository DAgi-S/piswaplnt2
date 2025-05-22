<?php
require_once 'core.php';
require_once 'classes/ConfigurationManager.php';
require_once 'classes/Database.php';

// Check permissions
if (!isset($_SESSION['userId']) || !isset($_SESSION['role_id'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

class DatabaseConfigValidator {
    private $config;
    private $errors = [];
    private $testResults = [];
    private $backupPath;

    public function __construct() {
        $this->config = ConfigurationManager::getInstance();
        $this->backupPath = $this->config->get('backup_path') . '/database_config';
    }

    public function validate($data) {
        // Validate host
        if (empty($data['db_host'])) {
            $this->errors[] = 'Database host is required';
        }

        // Validate port
        if (!is_numeric($data['db_port']) || $data['db_port'] < 1 || $data['db_port'] > 65535) {
            $this->errors[] = 'Invalid database port';
        }

        // Validate database name
        if (empty($data['db_name'])) {
            $this->errors[] = 'Database name is required';
        }

        // If no validation errors, test connection
        if (empty($this->errors)) {
            $this->testConnection($data);
        }

        return [
            'success' => empty($this->errors),
            'errors' => $this->errors,
            'test_results' => $this->testResults
        ];
    }

    private function testConnection($data) {
        try {
            // Create backup of current configuration
            $this->backupCurrentConfig();

            // Test new connection
            $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_name']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $pdo = new PDO($dsn, $data['db_user'], $data['db_password'], $options);
            $this->testResults[] = 'Database connection successful';

            // Test basic queries
            $this->testQueries($pdo);

            // If all tests pass, update configuration
            $this->updateConfiguration($data);

        } catch (PDOException $e) {
            $this->errors[] = 'Database Error: ' . $e->getMessage();
            $this->testResults[] = 'Error details: ' . $e->getMessage();
            
            // Restore from backup if connection fails
            $this->restoreFromBackup();
        }
    }

    private function backupCurrentConfig() {
        try {
            if (!file_exists($this->backupPath)) {
                mkdir($this->backupPath, 0750, true);
            }

            $currentConfig = [
                'db_host' => $this->config->get('db_host'),
                'db_port' => $this->config->get('db_port'),
                'db_name' => $this->config->get('db_name'),
                'db_user' => $this->config->get('db_user'),
                'db_password' => $this->config->get('db_password')
            ];

            $backupFile = $this->backupPath . '/config_backup_' . date('Y-m-d_H-i-s') . '.json';
            file_put_contents($backupFile, json_encode($currentConfig));
            $this->testResults[] = 'Current configuration backed up successfully';

        } catch (Exception $e) {
            $this->errors[] = 'Backup failed: ' . $e->getMessage();
        }
    }

    private function testQueries($pdo) {
        try {
            // Test SELECT
            $pdo->query("SELECT 1");
            $this->testResults[] = 'SELECT query successful';

            // Test INSERT
            $pdo->query("CREATE TEMPORARY TABLE test_table (id INT)");
            $pdo->query("INSERT INTO test_table VALUES (1)");
            $this->testResults[] = 'INSERT query successful';

            // Test UPDATE
            $pdo->query("UPDATE test_table SET id = 2 WHERE id = 1");
            $this->testResults[] = 'UPDATE query successful';

            // Test DELETE
            $pdo->query("DELETE FROM test_table");
            $this->testResults[] = 'DELETE query successful';

            // Drop temporary table
            $pdo->query("DROP TEMPORARY TABLE test_table");

        } catch (PDOException $e) {
            $this->errors[] = 'Query test failed: ' . $e->getMessage();
        }
    }

    private function updateConfiguration($data) {
        try {
            $this->config->set('db_host', $data['db_host']);
            $this->config->set('db_port', $data['db_port']);
            $this->config->set('db_name', $data['db_name']);
            $this->config->set('db_user', $data['db_user']);
            $this->config->set('db_password', $data['db_password']);
            
            $this->testResults[] = 'Configuration updated successfully';
        } catch (Exception $e) {
            $this->errors[] = 'Configuration update failed: ' . $e->getMessage();
            $this->restoreFromBackup();
        }
    }

    private function restoreFromBackup() {
        try {
            $backups = glob($this->backupPath . '/config_backup_*.json');
            if (!empty($backups)) {
                $latestBackup = end($backups);
                $config = json_decode(file_get_contents($latestBackup), true);
                
                $this->config->set('db_host', $config['db_host']);
                $this->config->set('db_port', $config['db_port']);
                $this->config->set('db_name', $config['db_name']);
                $this->config->set('db_user', $config['db_user']);
                $this->config->set('db_password', $config['db_password']);
                
                $this->testResults[] = 'Configuration restored from backup';
            }
        } catch (Exception $e) {
            $this->errors[] = 'Restore failed: ' . $e->getMessage();
        }
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new DatabaseConfigValidator();
    $result = $validator->validate($_POST);
    
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 