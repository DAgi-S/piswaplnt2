<?php
require_once 'core.php';

// Check if user has permission to view settings
if (!hasPermission('settings.units.manage') && !hasPermission('settings_access') && !hasPermission('system.settings.view')) {
    echo json_encode(array(
        'data' => array(),
        'error' => 'Access denied. Permission to view settings required.'
    ));
    exit();
}

try {
    // Prepare and execute query
    $sql = "SELECT id, name, abbreviation, description, status, created_at, updated_at 
            FROM units 
            WHERE deleted = 0 OR deleted IS NULL 
            ORDER BY name ASC";
            
    $result = $connect->query($sql);
    $data = array();

    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $actionButtons = '';
            
            // Only show action buttons if user has manage permission
            if(hasPermission('settings.units.manage')) {
                $actionButtons = '
                    <button class="btn btn-warning btn-sm" onclick="editUnit('.$row['id'].')" title="Edit">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteUnit('.$row['id'].')" title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>';
            }

            $status = $row['status'] == 1 ? 
                '<span class="label label-success">Active</span>' : 
                '<span class="label label-danger">Inactive</span>';

            $data[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'abbreviation' => $row['abbreviation'],
                'description' => $row['description'] ?? '',
                'status' => $row['status'],
                'status_label' => $status,
                'action' => $actionButtons
            );
        }
    }

    $output = array(
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        "recordsTotal" => count($data),
        "recordsFiltered" => count($data),
        "data" => $data
    );

} catch (Exception $e) {
    error_log("Error in fetchUnits.php: " . $e->getMessage());
    $output = array(
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => array(),
        "error" => "An error occurred while fetching units."
    );
}

// Close database connection
$connect->close();

// Set content type header and output response
header('Content-Type: application/json');
echo json_encode($output); 