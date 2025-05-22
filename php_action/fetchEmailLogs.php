<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

if (!hasPermission('view_email_logs')) {
    echo json_encode([
        'success' => false,
        'messages' => 'You do not have permission to view email logs'
    ]);
    exit();
}

$sql = "SELECT el.*, q.quotation_number 
        FROM email_logs el
        LEFT JOIN quotations q ON el.quotation_id = q.id
        ORDER BY el.sent_at DESC";

$result = $connect->query($sql);
$data = array();

while ($row = $result->fetch_assoc()) {
    $data[] = array(
        'sent_at' => date('Y-m-d H:i:s', strtotime($row['sent_at'])),
        'quotation_number' => $row['quotation_number'],
        'sent_to' => $row['sent_to'],
        'subject' => $row['subject'],
        'status' => $row['status'],
        'error_message' => $row['error_message'] ?? ''
    );
}

echo json_encode(['data' => $data]); 