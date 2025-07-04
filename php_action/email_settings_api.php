<?php
require_once '../core.php';
global $connect;
header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

if ($action === 'get') {
    $result = $connect->query('SELECT * FROM system_backup_email_settings ORDER BY id DESC LIMIT 1');
    $row = $result ? $result->fetch_assoc() : null;
    echo json_encode($row);
    exit;
}

if ($action === 'save') {
    $smtp_host = $_POST['smtp_host'] ?? '';
    $smtp_port = intval($_POST['smtp_port'] ?? 587);
    $smtp_user = $_POST['smtp_user'] ?? '';
    $smtp_pass = $_POST['smtp_pass'] ?? '';
    $from_email = $_POST['from_email'] ?? '';
    $from_name = $_POST['from_name'] ?? '';
    $to_emails = $_POST['to_emails'] ?? '';
    $use_tls = intval($_POST['use_tls'] ?? 1);
    $enabled = intval($_POST['enabled'] ?? 1);
    // Upsert (replace all)
    $connect->query('DELETE FROM system_backup_email_settings');
    $stmt = $connect->prepare('INSERT INTO system_backup_email_settings (smtp_host, smtp_port, smtp_user, smtp_pass, from_email, from_name, to_emails, use_tls, enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('sisssssii', $smtp_host, $smtp_port, $smtp_user, $smtp_pass, $from_email, $from_name, $to_emails, $use_tls, $enabled);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Invalid action']); 