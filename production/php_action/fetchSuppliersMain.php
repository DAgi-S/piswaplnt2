<?php
require_once 'core.php';

header('Content-Type: application/json');

// Initialize the output array with required DataTables structure
$output = array(
    'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => array(),
    'error' => false
);

try {
    // Get total records count
    $totalRecords = $connect->query("SELECT COUNT(*) as count FROM suppliers WHERE active = 1")->fetch_object()->count;
    $output['recordsTotal'] = intval($totalRecords);
    $output['recordsFiltered'] = intval($totalRecords);

    // Get supplier data with proper error handling
    $sql = "SELECT id, company_name, contact_person, phone, email, address, tin, active 
            FROM suppliers 
            WHERE active = 1 
            ORDER BY company_name ASC";
    
    if(!$result = $connect->query($sql)) {
        throw new Exception($connect->error);
    }

    $data = array();
    while($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $actionButton = '<div class="btn-group">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Action <span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right">
                <li><a href="javascript:void(0);" onclick="viewSupplier('.$row['id'].')"><i class="fa fa-eye"></i> View</a></li>
                <li><a href="javascript:void(0);" onclick="editSupplier('.$row['id'].')"><i class="fa fa-edit"></i> Edit</a></li>
                <li><a href="javascript:void(0);" onclick="removeSupplier('.$row['id'].')"><i class="fa fa-trash"></i> Remove</a></li>
            </ul>
        </div>';

        $status = $row['active'] == 1 ? 
            '<span class="label label-success">Active</span>' : 
            '<span class="label label-danger">Inactive</span>';

        $data[] = array(
            htmlspecialchars($row['company_name']),
            htmlspecialchars($row['contact_person']),
            htmlspecialchars($row['phone']),
            htmlspecialchars($row['email']),
            htmlspecialchars($row['address']),
            htmlspecialchars($row['tin']),
            $status,
            $actionButton
        );
    }
    
    $output['data'] = $data;
    $result->free();

} catch(Exception $e) {
    error_log("Error in fetchSuppliersMain.php: " . $e->getMessage());
    $output['error'] = true;
    $output['message'] = "An error occurred while fetching suppliers: " . $e->getMessage();
}

if($connect) {
    $connect->close();
}

// Ensure clean output
if(ob_get_length()) ob_clean();
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); 