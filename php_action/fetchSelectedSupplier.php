<?php
require_once 'core.php';

$supplierId = $_POST['supplierId'];

$sql = "SELECT * FROM suppliers WHERE id = $supplierId";
$result = $connect->query($sql);

if($result->num_rows > 0) {
    $row = $result->fetch_array();
} // if num_rows

$connect->close();

echo json_encode($row); 