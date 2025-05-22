<?php
require_once 'core.php';

$sql = "SELECT id, letter_code FROM letter_templates ORDER BY letter_code ASC";
$result = $connect->query($sql);

$output = array();

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $output[] = $row;
    }
}

echo json_encode($output);
$connect->close(); 