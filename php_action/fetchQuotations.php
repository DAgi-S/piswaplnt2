<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

header('Content-Type: application/json');

if (!hasPermission('view_quotations')) {
    echo json_encode([
        'data' => []
    ]);
    exit();
}

$sql = "SELECT q.*, c.company_name as client_name, c.email as client_email
        FROM quotations q
        LEFT JOIN clients c ON q.client_id = c.id
        ORDER BY q.created_at DESC";

$result = $connect->query($sql);
$data = array();

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = array(
            'id' => $row['id'],
            'quotation_number' => $row['quotation_number'],
            'client_name' => $row['client_name'],
            'client_email' => $row['client_email'],
            'created_at' => date('d M Y', strtotime($row['created_at'])),
            'sub_total' => $row['sub_total'],
            'vat_amount' => $row['vat_amount'],
            'grand_total' => $row['grand_total'],
            'status' => $row['status']
        );
    }
}

echo json_encode(['data' => $data]); 