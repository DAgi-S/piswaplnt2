<?php
require_once 'Database.php';
require_once 'ConfigurationEncryption.php';

class ConfigurationManager {
    private $db;
    private $encryption;
    private static $instance = null;
    private $cache = [];

    private function __construct() {
        $this->db = new Database();
        try {
            $this->encryption = ConfigurationEncryption::getInstance();
        } catch (Exception $e) {
            error_log("Failed to initialize encryption: " . $e->getMessage());
            $this->encryption = null;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get a configuration value by its key
     * @param string $key The configuration key
     * @param mixed $default Default value if not found
     * @return mixed The configuration value
     */
    public function get($key, $default = null) {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT setting_value, data_type, default_value 
                FROM system_config_settings 
                WHERE setting_key = ?
            ");
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row) {
                $value = $row['setting_value'] ?? $row['default_value'];
                
                // Decrypt value if necessary
                if ($this->encryption && $this->isSecureKey($key) && $this->encryption->isEncrypted($value)) {
                    $value = $this->encryption->decrypt($value);
                    if ($value === false) {
                        error_log("Failed to decrypt configuration value for key: $key");
                        return $default;
                    }
                }

                $value = $this->castValue($value, $row['data_type']);
                $this->cache[$key] = $value;
                return $value;
            }
        } catch (Exception $e) {
            error_log("Error fetching configuration: " . $e->getMessage());
        }

        return $default;
    }

    /**
     * Set a configuration value
     * @param string $key The configuration key
     * @param mixed $value The value to set
     * @return bool Success status
     */
    public function set($key, $value) {
        try {
            if (!$this->validateValue($key, $value)) {
                throw new Exception("Invalid value for configuration key: $key");
            }

            // Encrypt value if necessary
            if ($this->encryption && $this->isSecureKey($key)) {
                $encrypted = $this->encryption->encrypt($value);
                if ($encrypted === false) {
                    throw new Exception("Failed to encrypt value for key: $key");
                }
                $value = $encrypted;
            }

            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                UPDATE system_config_settings 
                SET setting_value = ?, updated_at = NOW() 
                WHERE setting_key = ?
            ");
            $stmt->bind_param("ss", $value, $key);
            $success = $stmt->execute();

            if ($success) {
                // Store decrypted value in cache
                $this->cache[$key] = $this->isSecureKey($key) ? $value : $value;
                $this->logConfigChange($key, $value);
            }

            return $success;
        } catch (Exception $e) {
            error_log("Error setting configuration: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all settings for a specific category
     * @param string $category The category name
     * @return array Array of settings
     */
    public function getCategorySettings($category) {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT s.* 
                FROM system_config_settings s 
                JOIN system_config_categories c ON s.category_id = c.category_id 
                WHERE c.category_name = ?
                ORDER BY s.display_name
            ");
            $stmt->bind_param("s", $category);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching category settings: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate a configuration value
     * @param string $key The configuration key
     * @param mixed $value The value to validate
     * @return bool Validation result
     */
    private function validateValue($key, $value) {
        try {
            $conn = $this->db->connect();
            $stmt = $conn->prepare("
                SELECT data_type, validation_rules, is_required 
                FROM system_config_settings 
                WHERE setting_key = ?
            ");
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $result = $stmt->get_result();
            $setting = $result->fetch_assoc();

            if (!$setting) {
                return false;
            }

            if ($setting['is_required'] && ($value === null || $value === '')) {
                return false;
            }

            // Skip additional validation for encrypted values
            if ($this->encryption && $this->isSecureKey($key) && $this->encryption->isEncrypted($value)) {
                return true;
            }

            $rules = explode('|', $setting['validation_rules']);
            foreach ($rules as $rule) {
                if (!$this->validateRule($value, $rule, $setting['data_type'])) {
                    return false;
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Error validating configuration: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate a single rule
     * @param mixed $value The value to validate
     * @param string $rule The validation rule
     * @param string $dataType The data type
     * @return bool Validation result
     */
    private function validateRule($value, $rule, $dataType) {
        if (empty($rule)) {
            return true;
        }

        if (strpos($rule, ':') !== false) {
            list($rule, $param) = explode(':', $rule);
        }

        switch ($rule) {
            case 'required':
                return !empty($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'numeric':
                return is_numeric($value);
            case 'boolean':
                return in_array($value, [true, false, 0, 1, '0', '1']);
            case 'min':
                return is_numeric($value) && $value >= $param;
            case 'max':
                return is_numeric($value) && $value <= $param;
            default:
                return true;
        }
    }

    /**
     * Cast a value to its proper type
     * @param mixed $value The value to cast
     * @param string $type The target data type
     * @return mixed The cast value
     */
    private function castValue($value, $type) {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'boolean':
                return (bool) $value;
            case 'json':
                return json_decode($value, true) ?? $value;
            default:
                return $value;
        }
    }

    /**
     * Log configuration changes
     * @param string $key The configuration key
     * @param mixed $value The new value
     */
    private function logConfigChange($key, $value) {
        if ($this->isSecureKey($key)) {
            $value = '********';
        }

        $userId = $_SESSION['userId'] ?? 0;
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = sprintf(
            "Configuration changed - Key: %s, Value: %s, User: %d, Time: %s",
            $key,
            $value,
            $userId,
            $timestamp
        );
        error_log($logMessage);
    }

    /**
     * Check if a key contains sensitive information
     * @param string $key The configuration key
     * @return bool Whether the key is secure
     */
    private function isSecureKey($key) {
        $secureKeys = ['password', 'secret', 'key', 'token'];
        foreach ($secureKeys as $secureKey) {
            if (stripos($key, $secureKey) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Test a configuration value without saving
     * @param string $key The configuration key
     * @param mixed $value The value to test
     * @return array Test results
     */
    public function testConfig($key, $value) {
        $results = [
            'valid' => false,
            'messages' => []
        ];

        try {
            $results['valid'] = $this->validateValue($key, $value);
            
            // Special testing for certain types of configurations
            switch ($key) {
                case 'mail_server':
                    $results['messages'][] = $this->testEmailServer($value);
                    break;
                case 'db_host':
                    $results['messages'][] = $this->testDatabaseConnection($value);
                    break;
                case 'backup_path':
                    $results['messages'][] = $this->testBackupPath($value);
                    break;
            }
        } catch (Exception $e) {
            $results['messages'][] = "Error testing configuration: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Clear the configuration cache
     */
    public function clearCache() {
        $this->cache = [];
    }

    /**
     * Test email server connection
     * @param string $server The email server to test
     * @return string Test result message
     */
    private function testEmailServer($server) {
        $port = $this->get('mail_port', 587);
        $timeout = 5;

        try {
            $connection = @fsockopen($server, $port, $errno, $errstr, $timeout);
            if ($connection) {
                fclose($connection);
                return "Successfully connected to mail server $server on port $port";
            }
            return "Failed to connect to mail server: $errstr ($errno)";
        } catch (Exception $e) {
            return "Error testing mail server: " . $e->getMessage();
        }
    }

    /**
     * Test database connection
     * @param string $host The database host to test
     * @return string Test result message
     */
    private function testDatabaseConnection($host) {
        try {
            $dbName = $this->get('db_name');
            $dbUser = $this->get('db_user');
            $dbPass = $this->get('db_password');

            if (!$dbName || !$dbUser) {
                return "Database name and user must be configured first";
            }

            $testConn = new PDO(
                "mysql:host=$host;dbname=$dbName;charset=utf8mb4",
                $dbUser,
                $dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

            return "Successfully connected to database on $host";
        } catch (PDOException $e) {
            return "Failed to connect to database: " . $e->getMessage();
        }
    }

    /**
     * Test backup path
     * @param string $path The backup path to test
     * @return string Test result message
     */
    private function testBackupPath($path) {
        try {
            // Normalize path
            $path = rtrim(str_replace('\\', '/', $path), '/');
            
            // Create directory if it doesn't exist
            if (!file_exists($path)) {
                if (!@mkdir($path, 0755, true)) {
                    return "Failed to create backup directory: Permission denied";
                }
            }

            // Test write permissions
            $testFile = "$path/test_" . uniqid() . ".tmp";
            if (!@file_put_contents($testFile, 'test')) {
                return "Backup directory is not writable";
            }
            @unlink($testFile);

            // Check available space
            $freeSpace = disk_free_space($path);
            if ($freeSpace === false) {
                return "Could not determine available disk space";
            }

            $minSpace = 100 * 1024 * 1024; // 100MB minimum
            if ($freeSpace < $minSpace) {
                return "Warning: Less than 100MB free space available";
            }

            return "Backup path is valid and writable. " . 
                   sprintf("%.2f GB free space available", $freeSpace / 1024 / 1024 / 1024);
        } catch (Exception $e) {
            return "Error testing backup path: " . $e->getMessage();
        }
    }

    /**
     * Import configuration from file
     * @param string $file Path to configuration file
     * @return array Import results
     */
    public function importFromFile($file) {
        $results = [
            'success' => false,
            'imported' => 0,
            'errors' => []
        ];

        try {
            if (!file_exists($file)) {
                throw new Exception("Configuration file not found");
            }

            $config = json_decode(file_get_contents($file), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid configuration file format");
            }

            foreach ($config as $key => $value) {
                if ($this->set($key, $value)) {
                    $results['imported']++;
                } else {
                    $results['errors'][] = "Failed to import setting: $key";
                }
            }

            $results['success'] = true;
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Export configuration to file
     * @param string $file Path to save configuration
     * @return array Export results
     */
    public function exportToFile($file) {
        $results = [
            'success' => false,
            'exported' => 0,
            'errors' => []
        ];

        try {
            $config = [];
            $conn = $this->db->connect();
            $stmt = $conn->query("SELECT setting_key, setting_value FROM system_config_settings");
            
            while ($row = $stmt->fetch_assoc()) {
                if (!$this->isSecureKey($row['setting_key'])) {
                    $config[$row['setting_key']] = $row['setting_value'];
                    $results['exported']++;
                }
            }

            if (file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT))) {
                $results['success'] = true;
            } else {
                throw new Exception("Failed to write configuration file");
            }
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Re-encrypt all sensitive configuration values
     * @return bool Success status
     */
    public function reEncryptAll() {
        if (!$this->encryption) {
            return false;
        }

        try {
            $conn = $this->db->connect();
            $stmt = $conn->query("
                SELECT setting_id, setting_key, setting_value 
                FROM system_config_settings 
                WHERE setting_value IS NOT NULL
            ");

            $conn->begin_transaction();

            while ($row = $stmt->fetch_assoc()) {
                if ($this->isSecureKey($row['setting_key'])) {
                    // Decrypt if already encrypted
                    $value = $row['setting_value'];
                    if ($this->encryption->isEncrypted($value)) {
                        $value = $this->encryption->decrypt($value);
                        if ($value === false) {
                            continue;
                        }
                    }

                    // Re-encrypt
                    $encrypted = $this->encryption->encrypt($value);
                    if ($encrypted === false) {
                        throw new Exception("Failed to encrypt value for key: " . $row['setting_key']);
                    }

                    $updateStmt = $conn->prepare("
                        UPDATE system_config_settings 
                        SET setting_value = ? 
                        WHERE setting_id = ?
                    ");
                    $updateStmt->bind_param("si", $encrypted, $row['setting_id']);
                    $updateStmt->execute();
                }
            }

            $conn->commit();
            $this->clearCache();
            return true;
        } catch (Exception $e) {
            if ($conn->in_transaction()) {
                $conn->rollback();
            }
            error_log("Error re-encrypting configuration: " . $e->getMessage());
            return false;
        }
    }
} 