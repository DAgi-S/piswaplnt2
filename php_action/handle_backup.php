<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/backup_errors.log');

// Get the origin
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allowedOrigins = [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:80',
    'http://127.0.0.1:80'
];

// Allow from allowed origins
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Required files
require_once 'db_connect.php';
require_once 'core.php';
require_once 'classes/backup/BackupCompression.php';
require_once 'classes/backup/BackupVerification.php';
require_once 'classes/backup/BackupMailer.php';
require_once 'classes/backup/BackupEncryption.php';

// Basic security check
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Load backup configuration
require_once dirname(__FILE__, 2) . '/config/backup.php';

// Add these variables near the top:
$backupAllDatabases = true; // Set to true to backup all databases
$backupUploads = true; // Set to true to backup uploads directory
$uploadsDir = dirname(__FILE__, 2) . '/uploads'; // Adjust if your uploads dir is elsewhere

function findMysqldump() {
    $possiblePaths = [
        'mysqldump',                          // System PATH
        'C:/xampp/mysql/bin/mysqldump.exe',   // XAMPP Windows
        'C:/xampp/mysql/bin/mysqldump',       // XAMPP Windows alternative
        '/usr/bin/mysqldump',                 // Linux/cPanel
        '/usr/local/bin/mysqldump',           // Alternative Linux path
        '/usr/local/mysql/bin/mysqldump'      // MAMP and others
    ];

    foreach ($possiblePaths as $path) {
        if (strpos($path, '/') === 0) {
            // Unix-like path
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        } else if (strpos($path, 'C:') === 0) {
            // Windows path
            if (file_exists($path)) {
                return $path;
            }
        } else {
            // Try to find in system PATH
            $output = [];
            $returnVar = -1;
            exec('which ' . $path . ' 2>/dev/null', $output, $returnVar);
            if ($returnVar === 0 && !empty($output[0])) {
                return $output[0];
            }
        }
    }
    
    return null; // Return null if mysqldump not found
}

function sendBackupNotification($backupDetails) {
    // Get email settings from database
    $query = "SELECT setting_key, setting_value FROM system_config_settings 
              WHERE setting_key IN ('mail_server', 'mail_port', 'mail_username', 'mail_password', 'mail_from') 
              AND category_id = (SELECT category_id FROM system_config_categories WHERE category_name = 'Email')";
    
    global $connect;
    $result = $connect->query($query);
    $emailSettings = [];
    
    while ($row = $result->fetch_assoc()) {
        $emailSettings[$row['setting_key']] = $row['setting_value'];
    }

    require_once 'phpmailer/PHPMailer.php';
    require_once 'phpmailer/SMTP.php';
    require_once 'phpmailer/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $emailSettings['mail_server'];
        $mail->SMTPAuth = true;
        $mail->Username = $emailSettings['mail_username'];
        $mail->Password = $emailSettings['mail_password'];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $emailSettings['mail_port'];

        $mail->setFrom($emailSettings['mail_from']);
        $mail->addAddress($emailSettings['mail_from']); // Send to system admin

        $mail->isHTML(true);
        $mail->Subject = 'Database Backup Completed Successfully';
        $mail->Body = "
            <h2>Database Backup Completed</h2>
            <p>A new database backup has been created successfully.</p>
            <ul>
                <li><strong>Backup Date:</strong> {$backupDetails['backup_date']}</li>
                <li><strong>File Path:</strong> {$backupDetails['file_path']}</li>
                <li><strong>Size:</strong> {$backupDetails['size']} bytes</li>
                <li><strong>Status:</strong> {$backupDetails['status']}</li>
                <li><strong>Compression Ratio:</strong> {$backupDetails['compression_ratio']}%</li>
            </ul>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Failed to send backup notification email: " . $e->getMessage());
        return false;
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['action']) || $_POST['action'] !== 'create_backup') {
        throw new Exception('Invalid action specified');
    }

    // Get backup settings
    $query = "SELECT setting_value FROM system_config_settings 
             WHERE setting_key = 'backup_dir' 
             AND category_id = (SELECT category_id FROM system_config_categories WHERE category_name = 'Backup')";
    $result = $connect->query($query);
    $backupDir = $result->fetch_assoc()['setting_value'] ?? 'backups/';

    // Convert relative path to absolute path
    $backupDir = rtrim(dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . $backupDir, '/\\') . DIRECTORY_SEPARATOR;

    // Ensure backup directory exists and is writable
    if (!file_exists($backupDir)) {
        if (!@mkdir($backupDir, 0755, true)) {
            throw new Exception("Failed to create backup directory: " . $backupDir);
        }
    }

    if (!is_writable($backupDir)) {
        throw new Exception("Backup directory is not writable: " . $backupDir);
    }

    // Get database settings
    $dbHost = 'localhost';  // Using hardcoded values for XAMPP
    $dbName = 'pistocklnt1march';
    $dbUser = 'root';
    $dbPass = '';

    // Find mysqldump executable
    $mysqldump = findMysqldump();
    if ($mysqldump === null) {
        throw new Exception('mysqldump executable not found. Please ensure MySQL is properly installed.');
    }

    error_log("Using mysqldump path: " . $mysqldump);
    error_log("Backup directory: " . $backupDir);

    // Create backup filename
    $timestamp = date('Y-m-d_H-i-s');
    if ($backupAllDatabases) {
        $backupFile = $backupDir . 'database/backup_all_' . $timestamp . '.sql';
        $dbNameArg = '--all-databases';
    } else {
        $backupFile = $backupDir . 'database/backup_' . $timestamp . '.sql';
        $dbNameArg = escapeshellarg($dbName);
    }

    // Build the command
    $command = sprintf(
        '"%s" -h %s -u %s %s > "%s" 2>&1',
        $mysqldump,
        escapeshellarg($dbHost),
        escapeshellarg($dbUser),
        $dbNameArg,
        str_replace('/', '\\', $backupFile)
    );

    // Log the command (without sensitive info)
    $logCommand = $command;
    error_log("Executing backup command: " . $logCommand);

    // Execute backup
    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);

    // Log the output and return value
    error_log("Backup command return value: " . $returnVar);
    error_log("Backup command output: " . implode("\n", $output));

    if ($returnVar !== 0) {
        $errorOutput = implode("\n", $output);
        error_log("Backup command failed with output: " . $errorOutput);
        throw new Exception("Failed to create backup. Command failed with error code: " . $returnVar . ". " . $errorOutput);
    }

    if (!file_exists($backupFile)) {
        throw new Exception("Backup file was not created. Please check directory permissions.");
    }

    $fileSize = filesize($backupFile);
    if ($fileSize === 0) {
        unlink($backupFile);
        throw new Exception("Backup file was created but is empty. Please check mysqldump permissions.");
    }

    error_log("Backup created successfully. File size: " . $fileSize . " bytes");

    // Compress the backup file if compression is enabled
    $compressionResult = ['success' => true, 'compression_ratio' => 0];
    if ($BACKUP_COMPRESSION['enabled']) {
        $compression = new BackupCompression($BACKUP_COMPRESSION['level']);
        $compressionResult = $compression->compress($backupFile);
        
        if ($compressionResult['success']) {
            // Remove original file after successful compression
            unlink($backupFile);
            $backupFile = $compressionResult['destination_file'];
            $fileSize = filesize($backupFile);
        } else {
            error_log("Compression failed: " . $compressionResult['error']);
            // Continue with uncompressed file
        }
    }

    // Verify the backup
    $verification = new BackupVerification($connect);
    $verificationResult = $verification->verify($backupFile, 'database');

    if (!$verificationResult['success']) {
        throw new Exception("Backup verification failed: " . $verificationResult['error']);
    }

    // Log successful backup
    $insertLog = "INSERT INTO system_backup_logs 
                (backup_date, backup_type, file_path, status, size_in_bytes, compression_ratio, created_at) 
                VALUES (NOW(), 'manual', ?, 'success', ?, ?, NOW())";
    $stmt = $connect->prepare($insertLog);
    $compressionRatio = $compressionResult['success'] ? $compressionResult['compression_ratio'] : 0;
    $stmt->bind_param("sid", $backupFile, $fileSize, $compressionRatio);
    $stmt->execute();

    // Email notification
    $mailer = new BackupMailer($connect);
    $subject = $verificationResult['success'] ? 'Backup Successful' : 'Backup Failed';
    $body = '<h3>Backup Notification</h3>';
    $body .= '<p>Status: <b>' . ($verificationResult['success'] ? 'Success' : 'Failure') . '</b></p>';
    $body .= '<p>File: ' . htmlspecialchars($backupFile) . '</p>';
    $body .= '<p>Size: ' . number_format($fileSize/1024, 2) . ' KB</p>';
    $body .= '<p>Compression Ratio: ' . htmlspecialchars($compressionRatio) . '%</p>';
    $mailer->send($subject, $body);

    // After backup is created
    $encryption = new BackupEncryption($connect);
    if ($encryption->isEnabled() && isset($backupFile)) {
        $encryption->encryptFile($backupFile);
    }

    // After database backup, add uploads backup if enabled
    if ($backupUploads && file_exists($uploadsDir)) {
        $uploadsZip = $backupDir . 'uploads/uploads_backup_' . $timestamp . '.zip';
        if (!file_exists($backupDir . 'uploads')) {
            mkdir($backupDir . 'uploads', 0755, true);
        }
        $zipCommand = sprintf('powershell.exe Compress-Archive -Path "%s/*" -DestinationPath "%s"',
            str_replace('/', '\\', $uploadsDir),
            str_replace('/', '\\', $uploadsZip)
        );
        error_log("Executing uploads backup command: $zipCommand");
        shell_exec($zipCommand);
        error_log("Uploads backup created: $uploadsZip");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Backup created and verified successfully',
        'file' => $backupFile,
        'size' => $fileSize,
        'compression_ratio' => $compressionRatio,
        'verification' => $verificationResult['results']
    ]);

} catch (Exception $e) {
    error_log("Backup error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 