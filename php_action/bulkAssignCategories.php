<?php
require_once 'core.php';
require_once 'db_connect.php';

$valid['success'] = array('success' => false, 'messages' => array());

// Get category IDs
$sql = "SELECT category_id, category_name FROM digital_categories";
$result = $connect->query($sql);
$categories = array();
while($row = $result->fetch_assoc()) {
    $categories[$row['category_name']] = $row['category_id'];
}

// Get uncategorized transactions
$sql = "SELECT ds.* FROM digitalswap ds 
        LEFT JOIN digital_transaction_categories dtc ON ds.id = dtc.transaction_id 
        WHERE dtc.id IS NULL";
$result = $connect->query($sql);

$assigned = 0;
if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $categoryId = null;
        $platform = strtolower($row['platform']);
        
        // Assign category based on platform
        if(strpos($platform, 'facebook') !== false) {
            $categoryId = $categories['Facebook payment'];
        } else if(strpos($platform, 'bonga') !== false) {
            $categoryId = $categories['Bonga payment'];
        } else if(strpos($platform, 'usd') !== false || strpos($platform, 'dollar') !== false) {
            $categoryId = $categories['USD payment'];
        } else {
            $categoryId = $categories['Other'];
        }

        if($categoryId) {
            $sql = "INSERT INTO digital_transaction_categories (transaction_id, category_id) VALUES (?, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("ii", $row['id'], $categoryId);
            if($stmt->execute()) {
                $assigned++;
            }
        }
    }
}

if($assigned > 0) {
    $valid['success'] = true;
    $valid['messages'] = "$assigned transactions were automatically categorized";
} else {
    $valid['success'] = false;
    $valid['messages'] = "No transactions were categorized";
}

$connect->close();
echo json_encode($valid); 