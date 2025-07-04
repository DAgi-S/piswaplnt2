<?php
require_once 'db_connect.php';
require_once 'core.php';
header('Content-Type: application/json');

// Total backups and size
$row = $connect->query("SELECT COUNT(*) as total, SUM(size_in_bytes) as total_size FROM system_backup_logs")->fetch_assoc();
$totalBackups = (int)$row['total'];
$totalSize = (int)$row['total_size'];

// Human-readable size
function human_filesize($bytes, $decimals = 2) {
    $size = ['B','KB','MB','GB','TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return $bytes ? sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $size[$factor] : '0 B';
}

// Success rate
$row = $connect->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful FROM system_backup_logs")->fetch_assoc();
$successRate = ($row['total'] > 0) ? round(($row['successful'] / $row['total']) * 100, 2) : 0;

// Backups over time (last 14 days)
$labels = [];
$data = [];
$days = 14;
for ($i = $days - 1; $i >= 0; $i--) {
    $labels[] = date('Y-m-d', strtotime("-$i days"));
    $data[] = 0;
}
$res = $connect->query("SELECT DATE(backup_date) as day, COUNT(*) as count FROM system_backup_logs WHERE backup_date >= DATE_SUB(CURDATE(), INTERVAL $days DAY) GROUP BY day");
$map = [];
while ($row = $res->fetch_assoc()) {
    $map[$row['day']] = (int)$row['count'];
}
foreach ($labels as $idx => $day) {
    if (isset($map[$day])) $data[$idx] = $map[$day];
}

// Type distribution
$typeLabels = [];
$typeData = [];
$res = $connect->query("SELECT backup_type, COUNT(*) as count FROM system_backup_logs GROUP BY backup_type");
while ($row = $res->fetch_assoc()) {
    $typeLabels[] = $row['backup_type'] ?: 'Unknown';
    $typeData[] = (int)$row['count'];
}

echo json_encode([
    'total_backups' => $totalBackups,
    'total_size' => $totalSize,
    'total_size_human' => human_filesize($totalSize),
    'success_rate' => $successRate,
    'backups_over_time' => [
        'labels' => $labels,
        'data' => $data
    ],
    'type_distribution' => [
        'labels' => $typeLabels,
        'data' => $typeData
    ]
]); 