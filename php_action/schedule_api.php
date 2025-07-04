<?php
require_once '../core.php';
header('Content-Type: application/json');

global $connect;
$action = $_GET['action'] ?? '';

function fetch_schedule($row) {
    return [
        'id' => $row['id'],
        'schedule_name' => $row['schedule_name'],
        'backup_type' => $row['backup_type'],
        'frequency' => $row['frequency'],
        'day_of_week' => $row['day_of_week'],
        'time_of_day' => $row['time_of_day'],
        'enabled' => $row['enabled'],
        'last_run' => $row['last_run'],
        'next_run' => $row['next_run'],
        'notify_email' => $row['notify_email']
    ];
}

if ($action === 'list') {
    $result = $connect->query('SELECT * FROM system_backup_schedules ORDER BY id DESC');
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = fetch_schedule($row);
    }
    echo json_encode($schedules);
    exit;
}

if ($action === 'get' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $connect->prepare('SELECT * FROM system_backup_schedules WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    echo json_encode(fetch_schedule($row));
    exit;
}

if ($action === 'save') {
    $id = intval($_POST['id'] ?? 0);
    $schedule_name = $_POST['schedule_name'] ?? '';
    $backup_type = $_POST['backup_type'] ?? '';
    $frequency = $_POST['frequency'] ?? '';
    $day_of_week = $_POST['day_of_week'] ?? null;
    $time_of_day = $_POST['time_of_day'] ?? '';
    $notify_email = $_POST['notify_email'] ?? null;
    $enabled = isset($_POST['enabled']) ? intval($_POST['enabled']) : 1;

    if ($id > 0) {
        $stmt = $connect->prepare('UPDATE system_backup_schedules SET schedule_name=?, backup_type=?, frequency=?, day_of_week=?, time_of_day=?, notify_email=?, enabled=? WHERE id=?');
        $stmt->bind_param('ssssssii', $schedule_name, $backup_type, $frequency, $day_of_week, $time_of_day, $notify_email, $enabled, $id);
        $stmt->execute();
    } else {
        $stmt = $connect->prepare('INSERT INTO system_backup_schedules (schedule_name, backup_type, frequency, day_of_week, time_of_day, notify_email, enabled) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssssi', $schedule_name, $backup_type, $frequency, $day_of_week, $time_of_day, $notify_email, $enabled);
        $stmt->execute();
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $connect->prepare('DELETE FROM system_backup_schedules WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Invalid action']); 