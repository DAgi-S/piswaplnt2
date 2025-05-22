<?php
require_once 'core.php';

$sql = "SELECT id, name, rate, type, status FROM tax_rates WHERE deleted = 0 OR deleted IS NULL";
$result = $connect->query($sql);

$output = array('data' => array());

if($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $output['data'][] = array(
            'id' => $row[0],
            'name' => $row[1],
            'rate' => $row[2],
            'type' => $row[3],
            'status' => $row[4]
        );
    }
}

// Close database connection
$connect->close();

header('Content-Type: application/json');
echo json_encode($output); 