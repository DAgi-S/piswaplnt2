<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'core.php';
require_once 'db_connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Get DataTables parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 1;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'ASC';

    // Map DataTables column index to database column names
    $columns = array(
        0 => 'pp.product_code',
        1 => 'pp.name',
        2 => 'pc.name', // category_name
        3 => 'pb.name', // brand_name
        4 => 'pp.current_stock',
        5 => 'pp.production_cost',
        6 => 'pp.selling_price',
        7 => 'pp.status'
    );

    // Base query
    $sql = "SELECT 
                pp.id,
                pp.product_code,
                pp.name,
                pp.unit,
                pp.current_stock,
                pp.min_stock_level,
                pp.production_cost,
                pp.selling_price,
                pp.status,
                pc.name as category_name,
                pb.name as brand_name
            FROM production_products pp
            LEFT JOIN production_categories pc ON pp.category_id = pc.id
            LEFT JOIN production_brands pb ON pp.brand_id = pb.id";

    // Search condition
    $searchCondition = "";
    if (!empty($search)) {
        $searchValue = $connect->real_escape_string($search);
        $searchCondition = " WHERE (pp.product_code LIKE '%$searchValue%' 
                            OR pp.name LIKE '%$searchValue%' 
                            OR pc.name LIKE '%$searchValue%' 
                            OR pb.name LIKE '%$searchValue%')";
    }

    // Count total records
    $totalRecordsResult = $connect->query("SELECT COUNT(*) as total FROM production_products");
    $totalRecordsRow = $totalRecordsResult->fetch_assoc();
    $totalRecords = $totalRecordsRow['total'];

    // Count filtered records
    $filteredRecordsQuery = $sql . $searchCondition;
    $filteredRecordsResult = $connect->query($filteredRecordsQuery);
    $filteredRecords = $filteredRecordsResult ? $filteredRecordsResult->num_rows : 0;

    // Final query with order and limit
    $sql .= $searchCondition;
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
    }
    $sql .= " LIMIT $start, $length";

    // Execute final query
    $result = $connect->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $connect->error);
    }

    // Prepare data array
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $stockStatus = floatval($row['current_stock']) <= floatval($row['min_stock_level']) ? 'danger' : 'success';
        $unit = !empty($row['unit']) ? $row['unit'] : 'pcs';
        
        $data[] = array(
            'product_code' => $row['product_code'],
            'name' => $row['name'],
            'category_name' => $row['category_name'] ?? 'Uncategorized',
            'brand_name' => $row['brand_name'] ?? 'No Brand',
            'current_stock' => '<span class="label label-' . $stockStatus . '">' . number_format($row['current_stock'], 2) . ' ' . $unit . '</span>',
            'production_cost' => number_format($row['production_cost'], 2),
            'selling_price' => number_format($row['selling_price'], 2),
            'status' => '<span class="label label-' . ($row['status'] === 'active' ? 'success' : 'warning') . '">' . strtoupper($row['status']) . '</span>',
            'action' => '<div class="btn-group">
                            <button type="button" class="btn btn-sm btn-default btn-edit" data-id="' . $row['id'] . '">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-' . ($row['status'] === 'active' ? 'warning' : 'success') . '" 
                                    onclick="changeStatus(' . $row['id'] . ', \'' . ($row['status'] === 'active' ? 'inactive' : 'active') . '\')">
                                <i class="fa fa-' . ($row['status'] === 'active' ? 'times' : 'check') . '"></i>
                            </button>
                        </div>'
        );
    }

    // Prepare response
    $response = array(
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $filteredRecords,
        "data" => $data
    );

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in fetchProducts.php: " . $e->getMessage());
    echo json_encode(array(
        "draw" => $draw,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => array(),
        "error" => $e->getMessage()
    ));
} 