<?php
require_once 'Database.php';

class ConfigurationValidator {
    private static $instance = null;
    private $db;

    private function __construct() {
        $this->db = new Database();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Validate email configuration
     * @param array $config Email configuration array
     * @return array Validation results
     */
    public function validateEmailConfig($config) {
        $results = [
            'valid' => true,
            'messages' => [],
            'tests' => []
        ];

        // Validate SMTP server
        if (empty($config['mail_server'])) {
            $results['valid'] = false;
            $results['messages'][] = 'SMTP server is required';
        } else {
            // Test SMTP connection
            $results['tests'][] = $this->testSmtpConnection(
                $config['mail_server'],
                $config['mail_port'] ?? 587
            );
        }

        // Validate email format
        if (!empty($config['mail_username']) && 
            !filter_var($config['mail_username'], FILTER_VALIDATE_EMAIL)) {
            $results['valid'] = false;
            $results['messages'][] = 'Invalid email format for username';
        }

        // Validate port
        if (!empty($config['mail_port'])) {
            if (!is_numeric($config['mail_port']) || 
                $config['mail_port'] < 1 || 
                $config['mail_port'] > 65535) {
                $results['valid'] = false;
                $results['messages'][] = 'Invalid port number (must be between 1 and 65535)';
            }
        }

        return $results;
    }

    /**
     * Test SMTP connection
     * @param string $host SMTP host
     * @param int $port SMTP port
     * @return array Test results
     */
    private function testSmtpConnection($host, $port) {
        $result = [
            'test' => 'SMTP Connection',
            'success' => false,
            'message' => ''
        ];

        try {
            $errno = 0;
            $errstr = '';
            $timeout = 5;

            if ($socket = @fsockopen($host, $port, $errno, $errstr, $timeout)) {
                fclose($socket);
                $result['success'] = true;
                $result['message'] = "Successfully connected to SMTP server";
            } else {
                $result['message'] = "Failed to connect to SMTP server: $errstr ($errno)";
            }
        } catch (Exception $e) {
            $result['message'] = "Error testing SMTP connection: " . $e->getMessage();
        }

        return $result;
    }

    /**
     * Validate database configuration
     * @param array $config Database configuration array
     * @return array Validation results
     */
    public function validateDatabaseConfig($config) {
        $results = [
            'valid' => true,
            'messages' => [],
            'tests' => []
        ];

        // Required fields
        $required = ['db_host', 'db_name', 'db_user', 'db_password'];
        foreach ($required as $field) {
            if (empty($config[$field])) {
                $results['valid'] = false;
                $results['messages'][] = ucfirst(str_replace('db_', '', $field)) . ' is required';
            }
        }

        // Test connection if all required fields are present
        if ($results['valid']) {
            $results['tests'][] = $this->testDatabaseConnection($config);
        }

        return $results;
    }

    /**
     * Test database connection
     * @param array $config Database configuration
     * @return array Test results
     */
    private function testDatabaseConnection($config) {
        $result = [
            'test' => 'Database Connection',
            'success' => false,
            'message' => ''
        ];

        try {
            $testConn = new PDO(
                "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
                $config['db_user'],
                $config['db_password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5
                ]
            );
            $result['success'] = true;
            $result['message'] = "Successfully connected to database";
        } catch (PDOException $e) {
            $result['message'] = "Database connection failed: " . $e->getMessage();
        }

        return $result;
    }

    /**
     * Validate backup configuration
     * @param array $config Backup configuration array
     * @return array Validation results
     */
    public function validateBackupConfig($config) {
        $results = [
            'valid' => true,
            'messages' => [],
            'tests' => []
        ];

        // Validate backup path
        if (empty($config['backup_path'])) {
            $results['valid'] = false;
            $results['messages'][] = 'Backup path is required';
        } else {
            $results['tests'][] = $this->testBackupPath($config['backup_path']);
        }

        // Validate backup frequency
        if (!empty($config['backup_frequency'])) {
            if (!is_numeric($config['backup_frequency']) || $config['backup_frequency'] < 1) {
                $results['valid'] = false;
                $results['messages'][] = 'Backup frequency must be a positive number';
            }
        }

        // Validate retention count
        if (!empty($config['backup_retention'])) {
            if (!is_numeric($config['backup_retention']) || $config['backup_retention'] < 1) {
                $results['valid'] = false;
                $results['messages'][] = 'Backup retention count must be a positive number';
            }
        }

        return $results;
    }

    /**
     * Test backup path
     * @param string $path Backup path
     * @return array Test results
     */
    private function testBackupPath($path) {
        $result = [
            'test' => 'Backup Path',
            'success' => false,
            'message' => ''
        ];

        try {
            // Normalize path
            $path = rtrim(str_replace('\\', '/', $path), '/');
            
            // Create directory if it doesn't exist
            if (!file_exists($path)) {
                if (!@mkdir($path, 0755, true)) {
                    $result['message'] = "Failed to create backup directory: Permission denied";
                    return $result;
                }
            }

            // Test write permissions
            $testFile = "$path/test_" . uniqid() . ".tmp";
            if (!@file_put_contents($testFile, 'test')) {
                $result['message'] = "Backup directory is not writable";
                return $result;
            }
            @unlink($testFile);

            // Check available space
            $freeSpace = disk_free_space($path);
            if ($freeSpace === false) {
                $result['message'] = "Could not determine available disk space";
                return $result;
            }

            $minSpace = 100 * 1024 * 1024; // 100MB minimum
            if ($freeSpace < $minSpace) {
                $result['message'] = "Warning: Less than 100MB free space available";
                return $result;
            }

            $result['success'] = true;
            $result['message'] = sprintf(
                "Backup path is valid and writable. %.2f GB free space available",
                $freeSpace / 1024 / 1024 / 1024
            );
        } catch (Exception $e) {
            $result['message'] = "Error testing backup path: " . $e->getMessage();
        }

        return $result;
    }

    /**
     * Validate IP address
     * @param string $ip IP address to validate
     * @return bool Validation result
     */
    public function validateIP($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate URL
     * @param string $url URL to validate
     * @return bool Validation result
     */
    public function validateURL($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate path safety
     * @param string $path Path to validate
     * @return bool Validation result
     */
    public function validatePath($path) {
        // Remove any directory traversal attempts
        $path = str_replace(['../', '..\\'], '', $path);
        
        // Check for absolute path
        if (preg_match('/^[a-zA-Z]:\\\\/', $path) || strpos($path, '/') === 0) {
            return false;
        }

        // Check for invalid characters
        return !preg_match('/[<>:"|?*]/', $path);
    }

    /**
     * Validate JSON structure
     * @param string $json JSON string to validate
     * @param array $requiredFields Required fields in the JSON
     * @return bool Validation result
     */
    public function validateJSON($json, $requiredFields = []) {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        if (!empty($requiredFields)) {
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate date/time format
     * @param string $datetime Date/time string to validate
     * @param string $format Expected format
     * @return bool Validation result
     */
    public function validateDateTime($datetime, $format = 'Y-m-d H:i:s') {
        $d = DateTime::createFromFormat($format, $datetime);
        return $d && $d->format($format) === $datetime;
    }
} 