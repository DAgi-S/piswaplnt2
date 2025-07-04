<?php
class BackupEncryption {
    private $db;
    private $settings;

    public function __construct($db) {
        $this->db = $db;
        $this->settings = $this->loadSettings();
    }

    private function loadSettings() {
        $result = $this->db->query("SELECT * FROM system_backup_encryption_settings WHERE enabled = 1 ORDER BY id DESC LIMIT 1");
        return $result ? $result->fetch_assoc() : null;
    }

    public function isEnabled() {
        return $this->settings && $this->settings['enabled'];
    }

    public function encryptFile($inputFile, $outputFile = null) {
        if (!$this->isEnabled()) return false;
        $key = $this->settings['encryption_key'];
        $algo = $this->settings['algorithm'] ?: 'AES-256-CBC';
        $ivlen = openssl_cipher_iv_length($algo);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $data = file_get_contents($inputFile);
        $encrypted = openssl_encrypt($data, $algo, $key, 0, $iv);
        $output = base64_encode($iv . $encrypted);
        $outFile = $outputFile ?: $inputFile;
        file_put_contents($outFile, $output);
        return true;
    }

    public function decryptFile($inputFile, $outputFile = null) {
        if (!$this->isEnabled()) return false;
        $key = $this->settings['encryption_key'];
        $algo = $this->settings['algorithm'] ?: 'AES-256-CBC';
        $data = base64_decode(file_get_contents($inputFile));
        $ivlen = openssl_cipher_iv_length($algo);
        $iv = substr($data, 0, $ivlen);
        $encrypted = substr($data, $ivlen);
        $decrypted = openssl_decrypt($encrypted, $algo, $key, 0, $iv);
        $outFile = $outputFile ?: $inputFile;
        file_put_contents($outFile, $decrypted);
        return true;
    }
} 