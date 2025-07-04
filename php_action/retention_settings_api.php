<?php
require_once '../core.php';
global $connect;
header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

if ($action === 'get') {
    $result = $connect->query('SELECT * FROM system_backup_retention_policy ORDER BY id DESC LIMIT 1');
    $row = $result ? $result->fetch_assoc() : null;
    echo json_encode($row);
    exit;
}

if ($action === 'save') {
    $keep_last_n = intval($_POST['keep_last_n'] ?? 7);
    $keep_days = intval($_POST['keep_days'] ?? 30);
    $enabled = intval($_POST['enabled'] ?? 1);
    // Upsert (replace all)
    $connect->query('DELETE FROM system_backup_retention_policy');
    $stmt = $connect->prepare('INSERT INTO system_backup_retention_policy (keep_last_n, keep_days, enabled) VALUES (?, ?, ?)');
    $stmt->bind_param('iii', $keep_last_n, $keep_days, $enabled);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Invalid action']); 