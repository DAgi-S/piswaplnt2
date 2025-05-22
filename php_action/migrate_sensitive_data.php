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

class SensitiveDataMigration {
    private $encryption;
    private $config;
    private $db;
    private $log = [];

    public function __construct() {
        $this->encryption = ConfigurationEncryption::getInstance();
        $this->config = ConfigurationManager::getInstance();
        $this->db = new PDO(
            "mysql:host=" . $this->config->get('db_host') . 
            ";dbname=" . $this->config->get('db_name'),
            $this->config->get('db_user'),
            $this->config->get('db_password')
        );
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function migrate() {
        try {
            // Start transaction
            $this->db->beginTransaction();

            // Migrate configuration settings
            $this->migrateConfigurationSettings();

            // Migrate user credentials
            $this->migrateUserCredentials();

            // Migrate API keys and tokens
            $this->migrateApiCredentials();

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Migration completed successfully',
                'log' => $this->log
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
                'log' => $this->log
            ];
        }
    }

    private function migrateConfigurationSettings() {
        $this->log[] = 'Starting configuration settings migration...';
        
        $sensitiveKeys = [
            'mail_password',
            'db_password',
            'api_key',
            'secret_key',
            'encryption_key'
        ];

        $stmt = $this->db->query("SELECT * FROM system_config_settings WHERE config_key IN ('" . 
            implode("','", $sensitiveKeys) . "')");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!$this->encryption->isEncrypted($row['config_value'])) {
                $encryptedValue = $this->encryption->encrypt($row['config_value']);
                
                $updateStmt = $this->db->prepare(
                    "UPDATE system_config_settings SET config_value = ? WHERE config_id = ?"
                );
                $updateStmt->execute([$encryptedValue, $row['config_id']]);
                
                $this->log[] = "Encrypted configuration: {$row['config_key']}";
            }
        }
    }

    private function migrateUserCredentials() {
        $this->log[] = 'Starting user credentials migration...';
        
        $stmt = $this->db->query("SELECT user_id, password FROM users WHERE password NOT LIKE 'enc:%'");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!$this->encryption->isEncrypted($row['password'])) {
                $encryptedPassword = $this->encryption->encrypt($row['password']);
                
                $updateStmt = $this->db->prepare(
                    "UPDATE users SET password = ? WHERE user_id = ?"
                );
                $updateStmt->execute([$encryptedPassword, $row['user_id']]);
                
                $this->log[] = "Encrypted password for user ID: {$row['user_id']}";
            }
        }
    }

    private function migrateApiCredentials() {
        $this->log[] = 'Starting API credentials migration...';
        
        // Check if api_credentials table exists
        $tables = $this->db->query("SHOW TABLES LIKE 'api_credentials'")->fetchAll();
        
        if (!empty($tables)) {
            $stmt = $this->db->query("SELECT * FROM api_credentials WHERE token NOT LIKE 'enc:%'");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!$this->encryption->isEncrypted($row['token'])) {
                    $encryptedToken = $this->encryption->encrypt($row['token']);
                    
                    $updateStmt = $this->db->prepare(
                        "UPDATE api_credentials SET token = ? WHERE id = ?"
                    );
                    $updateStmt->execute([$encryptedToken, $row['id']]);
                    
                    $this->log[] = "Encrypted API token ID: {$row['id']}";
                }
            }
        }
    }
}

// Execute migration
$migration = new SensitiveDataMigration();
$result = $migration->migrate();

// Output results
header('Content-Type: application/json');
echo json_encode($result); 