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

try {
    $config = ConfigurationManager::getInstance();
    $backupPath = $config->get('backup_path') . '/encryption_keys';
    
    // Create backup directory if it doesn't exist
    if (!file_exists($backupPath)) {
        if (!mkdir($backupPath, 0750, true)) {
            throw new Exception('Failed to create backup directory');
        }
    }

    // Generate timestamp for filenames
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupPath . '/keys_' . $timestamp . '.enc';
    $recoveryFile = $backupPath . '/recovery_info_' . $timestamp . '.txt';

    // Generate random encryption key and IV
    $encryptionKey = bin2hex(random_bytes(32)); // 256-bit key
    $iv = random_bytes(16); // 128-bit IV for AES-256-CBC

    // Get encryption keys from configuration
    $keys = [
        'jwt_key' => $config->get('jwt_key'),
        'encryption_key' => $config->get('encryption_key'),
        'api_key' => $config->get('api_key')
    ];

    // Encrypt keys
    $jsonKeys = json_encode($keys);
    $encrypted = openssl_encrypt($jsonKeys, 'AES-256-CBC', hex2bin($encryptionKey), OPENSSL_RAW_DATA, $iv);
    
    if ($encrypted === false) {
        throw new Exception('Encryption failed: ' . openssl_error_string());
    }

    // Save encrypted backup
    if (file_put_contents($backupFile, $encrypted) === false) {
        throw new Exception('Failed to write backup file');
    }

    // Create recovery information
    $recoveryInfo = "Backup Recovery Information\n";
    $recoveryInfo .= "========================\n";
    $recoveryInfo .= "Timestamp: " . $timestamp . "\n";
    $recoveryInfo .= "Encryption Method: AES-256-CBC\n";
    $recoveryInfo .= "Encryption Key (hex): " . $encryptionKey . "\n";
    $recoveryInfo .= "IV (base64): " . base64_encode($iv) . "\n";
    $recoveryInfo .= "Checksum (SHA-256): " . hash('sha256', $encrypted) . "\n";
    
    // Save recovery information
    if (file_put_contents($recoveryFile, $recoveryInfo) === false) {
        unlink($backupFile); // Remove backup file if recovery info fails
        throw new Exception('Failed to write recovery information');
    }

    // Clean up old backups (keep last 5)
    $backups = glob($backupPath . '/keys_*.enc');
    usort($backups, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    if (count($backups) > 5) {
        for ($i = 5; $i < count($backups); $i++) {
            $oldBackup = $backups[$i];
            $oldRecovery = str_replace('keys_', 'recovery_info_', $oldBackup);
            $oldRecovery = str_replace('.enc', '.txt', $oldRecovery);
            
            @unlink($oldBackup);
            @unlink($oldRecovery);
        }
    }

    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Backup created successfully',
        'timestamp' => $timestamp
    ]);

} catch (Exception $e) {
    error_log('Backup creation failed: ' . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create backup: ' . $e->getMessage()
    ]);
} 