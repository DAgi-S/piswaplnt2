<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Clear any previous output
while (ob_get_level()) {
    ob_end_clean();
}

// Set header for JSON response
header('Content-Type: application/json');

try {
    // Optimize the SQL query with proper JOINs and indexing
    $sql = "SELECT 
                ds.id,
                ds.transaction_date,
                COALESCE(tt.name, ds.type) as type,
                ds.name,
                COALESCE(a.account_platform, ds.platform) as platform,
                ds.amount,
                ds.image,
                ds.comment
            FROM digitalswap ds
            LEFT JOIN transaction_types tt ON ds.type_id = tt.id
            LEFT JOIN accounts a ON ds.account_id = a.id
            ORDER BY ds.transaction_date DESC, ds.id DESC";

    $result = $connect->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $connect->error);
    }

    $data = array();
    while ($row = $result->fetch_assoc()) {
        // Format amount with 2 decimal places
        $row['amount'] = number_format((float)$row['amount'], 2, '.', '');
        
        // Format date
        $row['transaction_date'] = date('Y-m-d', strtotime($row['transaction_date']));
        
        // Handle image display
        if (!empty($row['image'])) {
            $row['image_url'] = $row['image'];
            $row['image'] = '<a href="javascript:void(0)" class="view-image" data-image="' . htmlspecialchars($row['image']) . '">View Image</a>';
        } else {
            $row['image_url'] = '';
            $row['image'] = 'No Image';
        }
        
        $data[] = $row;
    }

    $output = array(
        "draw" => 1,
        "recordsTotal" => count($data),
        "recordsFiltered" => count($data),
        "data" => $data
    );

} catch (Exception $e) {
    $output = array(
        "draw" => 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => array(),
        "error" => $e->getMessage()
    );
    
    // Log the error
    error_log("Digital Swap Fetch Error: " . $e->getMessage());
}

// Close the database connection
$connect->close();

// Send JSON response
echo json_encode($output, JSON_UNESCAPED_UNICODE);