<?php
require_once 'db_connect.php';
require_once 'core.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="backup_logs_export.csv"');

$output = fopen('php://output', 'w');

// CSV header
fputcsv($output, ['Date', 'Type', 'File', 'Size (bytes)', 'Compression', 'Status', 'Verification']);

// Filters
$where = [];
$params = [];
$types = '';
if (!empty($_GET['start_date'])) {
    $where[] = 'bl.backup_date >= ?';
    $params[] = $_GET['start_date'] . ' 00:00:00';
    $types .= 's';
}
if (!empty($_GET['end_date'])) {
    $where[] = 'bl.backup_date <= ?';
    $params[] = $_GET['end_date'] . ' 23:59:59';
    $types .= 's';
}
if (!empty($_GET['type'])) {
    $where[] = 'bl.backup_type = ?';
    $params[] = $_GET['type'];
    $types .= 's';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$query = "SELECT bl.backup_date, bl.backup_type, bl.file_path, bl.size_in_bytes, bl.compression_ratio, bl.status, bv.status as verification_status
          FROM system_backup_logs bl
          LEFT JOIN system_backup_verification_logs bv ON bl.file_path = bv.backup_file
          $whereSql
          ORDER BY bl.backup_date DESC LIMIT 100";

$stmt = $connect->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['backup_date'],
        $row['backup_type'],
        $row['file_path'],
        $row['size_in_bytes'],
        $row['compression_ratio'],
        $row['status'],
        $row['verification_status'] ?? 'Not Verified'
    ]);
}

fclose($output);
exit;