<?php
require_once 'core.php';

header('Content-Type: application/json');

$output = array();

try {
    // Main query for data
    $sql = "SELECT id, company_name, tin_number, phone, email, address, status 
            FROM clients 
            WHERE status = 1";
    
    $result = $connect->query($sql);

    $data = array();
    if($result) {
        while($row = $result->fetch_assoc()) {
            $data[] = array(
                $row['company_name'],
                $row['tin_number'] ? $row['tin_number'] : '',
                $row['phone'] ? $row['phone'] : '',
                $row['email'] ? $row['email'] : '',
                $row['address'] ? $row['address'] : '',
                $row['status'],
                '',  // Action buttons column (will be rendered by DataTables)
                $row['id']  // Hidden ID column
            );
        }
    }

    $output = array(
        "draw" => isset($_GET['draw']) ? intval($_GET['draw']) : 0,
        "recordsTotal" => count($data),
        "recordsFiltered" => count($data),
        "data" => $data
    );

    $connect->close();
    
} catch(Exception $e) {
    error_log("Error in fetchClientsMain.php: " . $e->getMessage());
    $output = array(
        "draw" => isset($_GET['draw']) ? intval($_GET['draw']) : 0,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => array(),
        "error" => "An error occurred while fetching clients"
    );
}

echo json_encode($output); 