<?php
require_once '../core.php';
global $connect;
header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

if ($action === 'get') {
    $result = $connect->query('SELECT * FROM system_backup_encryption_settings ORDER BY id DESC LIMIT 1');
    $row = $result ? $result->fetch_assoc() : null;
    echo json_encode($row);
    exit;
}

if ($action === 'save') {
    $enabled = intval($_POST['enabled'] ?? 0);
    $algorithm = $_POST['algorithm'] ?? 'AES-256-CBC';
    $encryption_key = $_POST['encryption_key'] ?? '';
    // Upsert (replace all)
    $connect->query('DELETE FROM system_backup_encryption_settings');
    $stmt = $connect->prepare('INSERT INTO system_backup_encryption_settings (enabled, algorithm, encryption_key) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $enabled, $algorithm, $encryption_key);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Invalid action']); 