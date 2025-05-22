<?php 
require_once 'core.php';

// Build the SQL query with filters
$sql = "SELECT d.*, a.name 
        FROM digitalswap d 
        LEFT JOIN accounts a ON d.account_id = a.id 
        WHERE 1=1";

// Apply filters if provided
if(isset($_GET['typeFilter']) && !empty($_GET['typeFilter'])) {
    $typeFilter = $connect->real_escape_string($_GET['typeFilter']);
    $sql .= " AND d.type = '$typeFilter'";
}

if(isset($_GET['platformFilter']) && !empty($_GET['platformFilter'])) {
    $platformFilter = $connect->real_escape_string($_GET['platformFilter']);
    $sql .= " AND d.platform = '$platformFilter'";
}

if(isset($_GET['dateFilter']) && !empty($_GET['dateFilter'])) {
    $dateFilter = $connect->real_escape_string($_GET['dateFilter']);
    $sql .= " AND DATE(d.transaction_date) = '$dateFilter'";
}

$sql .= " ORDER BY d.transaction_date DESC";

$result = $connect->query($sql);
$output = array('data' => array());

if($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $output['data'][] = array(
            'id' => $row['id'],
            'name' => $row['name'],
            'type' => ucfirst($row['type']),
            'platform' => $row['platform'],
            'amount' => $row['amount'],
            'transaction_date' => date('Y-m-d H:i', strtotime($row['transaction_date'])),
            'comment' => $row['comment']
        );
    }
}

$connect->close();
echo json_encode($output); 