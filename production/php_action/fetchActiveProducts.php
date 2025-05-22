<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

$response = array();

try {
    $searchTerm = isset($_POST['searchTerm']) ? mysqli_real_escape_string($connect, $_POST['searchTerm']) : '';
    
    $sql = "SELECT product_id as id, name as text FROM products WHERE status = 1";
    
    if (!empty($searchTerm)) {
        $sql .= " AND name LIKE '%{$searchTerm}%'";
    }
    
    $sql .= " ORDER BY name ASC LIMIT 10";
    
    $result = $connect->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
    }

} catch (Exception $e) {
    $response = array('error' => $e->getMessage());
}

echo json_encode($response); 