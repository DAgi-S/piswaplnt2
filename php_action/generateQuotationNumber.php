<?php
require_once 'core.php';
require_once 'db_connect.php';

// Get the current year and month
$year = date('Y');
$month = date('m');

// Get the last quotation number for this year/month
$sql = "SELECT quotation_number FROM quotations 
        WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?
        ORDER BY id DESC LIMIT 1";

$stmt = $connect->prepare($sql);
$stmt->bind_param("ss", $year, $month);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastNumber = intval(substr($row['quotation_number'], -4));
    $newNumber = $lastNumber + 1;
} else {
    $newNumber = 1;
}

// Format: QT-YYYYMM-XXXX
$quotationNumber = sprintf("QT-%s%s-%04d", $year, $month, $newNumber);

echo $quotationNumber; 