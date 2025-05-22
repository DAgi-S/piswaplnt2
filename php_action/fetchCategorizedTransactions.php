<?php
require_once 'core.php';
require_once 'db_connect.php';

$output = array('data' => array());

$categoryFilter = isset($_GET['categoryId']) ? $_GET['categoryId'] : '';

$sql = "SELECT ds.*, COALESCE(c.category_name, 'Uncategorized') as category_name, dtc.category_id,
        tt.name as type_name, a.account_owner, a.account_platform
        FROM digitalswap ds 
        LEFT JOIN digital_transaction_categories dtc ON ds.id = dtc.transaction_id 
        LEFT JOIN digital_categories c ON dtc.category_id = c.category_id
        LEFT JOIN transaction_types tt ON ds.type_id = tt.id
        LEFT JOIN accounts a ON ds.account_id = a.id";

if($categoryFilter) {
    $sql .= " WHERE dtc.category_id = " . $connect->real_escape_string($categoryFilter);
}

$sql .= " ORDER BY ds.transaction_date DESC";

$result = $connect->query($sql);

if($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $transactionId = $row['id'];
        
        $button = '
            <button class="btn btn-default assign-category" 
                    data-id="'.$transactionId.'" 
                    data-category="'.$row['category_id'].'">
                <i class="glyphicon glyphicon-tags"></i> Assign Category
            </button>';

        // Format amount with currency symbol
        $amount = $row['amount'];
        
        $output['data'][] = array(
            $transactionId,
            $row['transaction_date'],
            $row['type_name'],
            $row['name'],
            $row['platform'],
            $amount,
            $row['category_name'],
            $button
        );
    }
}

$connect->close();
echo json_encode($output); 