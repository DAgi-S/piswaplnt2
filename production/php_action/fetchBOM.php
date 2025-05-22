<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

require_once 'core.php';
require_once 'db_connect.php';

// Set proper headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // Fetch BOM items with product and material details
    $sql = "SELECT 
                b.id,
                p.name as product_name,
                r.name as raw_material_name,
                b.quantity_required,
                r.unit,
                b.wastage_percent,
                b.status,
                r.current_stock
            FROM product_bom b
            JOIN production_products p ON b.product_id = p.id
            JOIN raw_materials r ON b.material_id = r.id
            WHERE b.status != 'deleted'
            ORDER BY p.name ASC, r.name ASC";

    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Error fetching BOM data: " . $connect->error);
    }

    $data = array();
    while ($row = $result->fetch_assoc()) {
        $actionButtons = '
            <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Action <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="#" onclick="editBOM('.$row['id'].')"><i class="fa fa-edit"></i> Edit</a></li>
                    <li><a href="#" onclick="manageStatus('.$row['id'].', \''.$row['status'].'\')">
                        <i class="fa fa-refresh"></i> '.($row['status'] == 'active' ? 'Deactivate' : 'Activate').'
                    </a></li>
                </ul>
            </div>';

        $status = '<span class="label label-'.($row['status'] == 'active' ? 'success' : 'warning').'">'
                  .ucfirst($row['status']).'</span>';

        $data[] = array(
            $row['product_name'],
            $row['raw_material_name'],
            number_format($row['quantity_required'], 2) . ' ' . $row['unit'],
            $row['unit'],
            number_format($row['wastage_percent'], 2) . '%',
            $status,
            $actionButtons
        );
    }

    echo json_encode(array(
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        "recordsTotal" => $result->num_rows,
        "recordsFiltered" => $result->num_rows,
        "data" => $data
    ));

} catch (Exception $e) {
    error_log("Error in fetchBOM.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
} 