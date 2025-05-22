<?php
require_once 'core.php';

$sql = "SELECT COUNT(*) as count FROM guest_users";
$result = $connect->query($sql);
$row = $result->fetch_assoc();
echo "Number of guests: " . $row['count']; 