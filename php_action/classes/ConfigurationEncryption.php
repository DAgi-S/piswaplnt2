<?php
class ConfigurationEncryption {
    private $encryptionKey;
    private $cipher = 'aes-256-gcm';
    private static $instance = null;
    private $keyFile = '../config/encryption.key';

    private function __construct() {
        $this->initializeEncryption();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize encryption system
     */
    private function initializeEncryption() {
        if (!extension_loaded('openssl')) {
            throw new Exception('OpenSSL extension is required for encryption');
        }

        if (file_exists($this->keyFile)) {
            $this->encryptionKey = file_get_contents($this->keyFile);
        } else {
            $this->generateNewKey();
        }
    }

    /**
     * Generate a new encryption key
     */
    private function generateNewKey() {
        $this->encryptionKey = random_bytes(32);
        $keyDir = dirname($this->keyFile);
        
        if (!file_exists($keyDir)) {
            mkdir($keyDir, 0755, true);
        }
        
        if (file_put_contents($this->keyFile, $this->encryptionKey) === false) {
            throw new Exception('Failed to save encryption key');
        }
        
        chmod($this->keyFile, 0600);
    }

    /**
     * Encrypt a value
     * @param string $value Value to encrypt
     * @return string|false Encrypted value or false on failure
     */
    public function encrypt($value) {
        if (empty($value)) {
            return $value;
        }

        try {
            $ivlen = openssl_cipher_iv_length($this->cipher);
            $iv = openssl_random_pseudo_bytes($ivlen);
            
            // Encrypt the value
            $ciphertext = openssl_encrypt(
                $value,
                $this->cipher,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($ciphertext === false) {
                throw new Exception('Encryption failed');
            }

            // Combine IV, tag, and ciphertext for storage
            $encoded = base64_encode($iv . $tag . $ciphertext);
            return $encoded;
        } catch (Exception $e) {
            error_log('Encryption error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Decrypt a value
     * @param string $encrypted Encrypted value
     * @return string|false Decrypted value or false on failure
     */
    public function decrypt($encrypted) {
        if (empty($encrypted)) {
            return $encrypted;
        }

        try {
            $decoded = base64_decode($encrypted);
            if ($decoded === false) {
                throw new Exception('Invalid base64 encoding');
            }

            $ivlen = openssl_cipher_iv_length($this->cipher);
            $taglen = 16; // GCM tag length is always 16 bytes

            // Extract IV, tag, and ciphertext
            $iv = substr($decoded, 0, $ivlen);
            $tag = substr($decoded, $ivlen, $taglen);
            $ciphertext = substr($decoded, $ivlen + $taglen);

            // Decrypt the value
            $decrypted = openssl_decrypt(
                $ciphertext,
                $this->cipher,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($decrypted === false) {
                throw new Exception('Decryption failed');
            }

            return $decrypted;
        } catch (Exception $e) {
            error_log('Decryption error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Rotate encryption key
     * @return bool Success status
     */
    public function rotateKey() {
        try {
            // Get all encrypted values
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("
                SELECT setting_id, setting_value 
                FROM system_config_settings 
                WHERE setting_value IS NOT NULL
            ");
            
            // Generate new key
            $oldKey = $this->encryptionKey;
            $this->generateNewKey();
            
            // Re-encrypt all values with new key
            $conn->beginTransaction();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Decrypt with old key
                $this->encryptionKey = $oldKey;
                $decrypted = $this->decrypt($row['setting_value']);
                
                if ($decrypted === false) {
                    continue; // Skip if not encrypted or decryption fails
                }
                
                // Encrypt with new key
                $this->encryptionKey = $this->encryptionKey;
                $newEncrypted = $this->encrypt($decrypted);
                
                if ($newEncrypted === false) {
                    throw new Exception('Failed to re-encrypt value');
                }
                
                // Update database
                $updateStmt = $conn->prepare("
                    UPDATE system_config_settings 
                    SET setting_value = ? 
                    WHERE setting_id = ?
                ");
                $updateStmt->execute([$newEncrypted, $row['setting_id']]);
            }
            
            $conn->commit();
            return true;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $this->encryptionKey = $oldKey; // Restore old key on failure
            error_log('Key rotation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a value is encrypted
     * @param string $value Value to check
     * @return bool Whether the value is encrypted
     */
    public function isEncrypted($value) {
        if (empty($value)) {
            return false;
        }

        try {
            $decoded = base64_decode($value, true);
            if ($decoded === false) {
                return false;
            }

            $ivlen = openssl_cipher_iv_length($this->cipher);
            $taglen = 16;
            
            // Check if the decoded value has the correct structure
            return strlen($decoded) > ($ivlen + $taglen);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get encryption status information
     * @return array Status information
     */
    public function getStatus() {
        return [
            'cipher' => $this->cipher,
            'key_exists' => file_exists($this->keyFile),
            'key_permissions' => substr(sprintf('%o', fileperms($this->keyFile)), -4),
            'openssl_version' => OPENSSL_VERSION_TEXT
        ];
    }
} 