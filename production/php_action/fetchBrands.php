<?php
require_once 'core.php';

$output = array('data' => array());

// brands table
$sql = "SELECT brand_id as id, name, description, status, created_at, 'brands' as table_name FROM brands WHERE deleted = 0 OR deleted IS NULL";
$result = $connect->query($sql);
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $output['data'][] = array(
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'table_name' => $row['table_name']
        );
    }
}
// production_brands table
$sql = "SELECT id, name, description, status, created_at, 'production_brands' as table_name FROM production_brands";
$result = $connect->query($sql);
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $status = ($row['status'] == 1 || strtolower($row['status']) == 'active') ? 1 : 0;
        $output['data'][] = array(
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'status' => $status,
            'created_at' => $row['created_at'],
            'table_name' => $row['table_name']
        );
    }
}
$connect->close();
header('Content-Type: application/json');
echo json_encode($output); 