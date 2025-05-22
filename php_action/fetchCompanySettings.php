<?php
require_once 'core.php';

$output = array('success' => false, 'settings' => array());

$sql = "SELECT setting_key, setting_value FROM company_settings";
$result = $connect->query($sql);

if($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $output['settings'][$row['setting_key']] = $row['setting_value'];
    }
    $output['success'] = true;
}

$connect->close();
echo json_encode($output); 