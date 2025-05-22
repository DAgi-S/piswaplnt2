<?php
require_once 'core.php';

$sql = "SELECT setting_key, setting_value FROM company_settings";
$result = $connect->query($sql);

$companyInfo = array();
if($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $companyInfo[$row['setting_key']] = $row['setting_value'];
    }
}

// Close database connection
$connect->close();

header('Content-Type: application/json');
echo json_encode($companyInfo); 