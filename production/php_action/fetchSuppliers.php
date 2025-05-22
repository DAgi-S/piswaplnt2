<?php
require_once 'core.php';

$response = array(
    'success' => false,
    'messages' => '',
    'data' => array()
);

$sql = "SELECT id, company_name FROM suppliers WHERE active = 1 ORDER BY company_name ASC";
$result = $connect->query($sql);

if($result) {
    $response['data'] = array();
    while($row = $result->fetch_assoc()) {
        $response['data'][] = array(
            'id' => $row['id'],
            'company_name' => $row['company_name']
        );
    }
    $response['success'] = true;
} else {
    $response['messages'] = "Error fetching suppliers: " . $connect->error;
}

$connect->close();

header('Content-Type: application/json');
echo json_encode($response); 