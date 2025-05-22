<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set header to JSON
header('Content-Type: application/json');

try {
    // Changed query to remove deleted column filter
    $sql = "SELECT id, company_name, contact_person, email, phone, address, active FROM suppliers";
    $query = $connect->query($sql);

    if (!$query) {
        throw new Exception($connect->error);
    }

    $data = array();
    while ($row = $query->fetch_assoc()) {
        $actionButtons = '<div class="btn-group">';
        $actionButtons .= '<button class="btn btn-default" onclick="editSupplier('.$row['id'].')" data-toggle="modal" data-target="#editSupplierModal"><i class="glyphicon glyphicon-edit"></i></button>';
        $actionButtons .= '<button class="btn btn-danger" onclick="removeSupplier('.$row['id'].')" data-toggle="modal" data-target="#removeSupplierModal"><i class="glyphicon glyphicon-trash"></i></button>';
        $actionButtons .= '</div>';

        $status = '<span class="badge '.($row['active'] == 1 ? 'badge-active' : 'badge-inactive').'">'.($row['active'] == 1 ? 'Active' : 'Inactive').'</span>';

        $data[] = array(
            'id' => $row['id'],
            'company_name' => $row['company_name'],
            'contact_person' => $row['contact_person'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'address' => $row['address'],
            'status' => $row['active'],
            'action' => $actionButtons
        );
    }

    $response = array(
        "draw" => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        "recordsTotal" => $query->num_rows,
        "recordsFiltered" => $query->num_rows,
        "data" => $data
    );

    echo json_encode($response);

} catch (Exception $e) {
    $response = array(
        "draw" => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => array(),
        "error" => $e->getMessage()
    );
    echo json_encode($response);
}

// Close the connection
$connect->close(); 