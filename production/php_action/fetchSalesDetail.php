<?php
require_once 'core.php';

// Prevent any output before our JSON response
ob_start();

try {
    // Get search value
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

    // Get order column
    $order_column = isset($_POST['order'][0]['column']) ? $_POST['order'][0]['column'] : 1;
    $order_dir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

    // Column names
    $columns = array(
        'so.order_number',
        'so.order_date',
        'c.company_name',
        'GROUP_CONCAT(p.name)',
        'so.subtotal',
        'so.tax_amount',
        'so.withholding_amount',
        'so.discount_amount',
        'so.total_amount',
        'so.paid_amount',
        '(so.total_amount - so.paid_amount)',
        'so.payment_status',
        'u.username'
    );

    // Base query
    $query = "SELECT 
                so.id as sale_id,
                so.order_number as sale_number,
                so.order_date as date,
                c.company_name as client_name,
                GROUP_CONCAT(p.name SEPARATOR ', ') as products,
                so.subtotal,
                so.tax_amount as vat,
                so.withholding_amount as withholding,
                so.discount_amount as discount,
                so.total_amount as grand_total,
                so.paid_amount,
                (so.total_amount - so.paid_amount) as balance,
                so.payment_status,
                u.username as created_by
              FROM sales_orders so
              LEFT JOIN clients c ON so.client_id = c.id
              LEFT JOIN sales_order_items soi ON so.id = soi.sales_order_id
              LEFT JOIN production_products p ON soi.product_id = p.id
              LEFT JOIN users u ON so.created_by = u.user_id
              WHERE 1=1";

    // Search condition
    if(!empty($search)) {
        $query .= " AND (so.order_number LIKE ? 
                    OR c.company_name LIKE ?
                    OR p.name LIKE ?
                    OR u.username LIKE ?
                    OR so.payment_status LIKE ?)";
    }

    // Group by to avoid duplicates
    $query .= " GROUP BY so.id";

    // Get total records
    $totalRecords = $connect->query("SELECT COUNT(DISTINCT so.id) as total FROM sales_orders so")->fetch_object()->total;

    // Get filtered records count
    $countQuery = str_replace('SELECT 
                so.id as sale_id,
                so.order_number as sale_number,
                so.order_date as date,
                c.company_name as client_name,
                GROUP_CONCAT(p.name SEPARATOR \', \') as products,
                so.subtotal,
                so.tax_amount as vat,
                so.withholding_amount as withholding,
                so.discount_amount as discount,
                so.total_amount as grand_total,
                so.paid_amount,
                (so.total_amount - so.paid_amount) as balance,
                so.payment_status,
                u.username as created_by', 'SELECT COUNT(DISTINCT so.id) as total', $query);
    
    $stmt = $connect->prepare($countQuery);
    if(!empty($search)) {
        $searchParam = "%$search%";
        $stmt->bind_param("sssss", $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
    }
    $stmt->execute();
    $totalFiltered = $stmt->get_result()->fetch_object()->total;

    // Order
    if(isset($order_column) && isset($order_dir)) {
        $query .= " ORDER BY " . $columns[$order_column] . " " . $order_dir;
    }

    // Limit
    if(isset($_POST['start']) && isset($_POST['length'])) {
        $query .= " LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
    }

    // Execute final query
    $stmt = $connect->prepare($query);
    if(!empty($search)) {
        $searchParam = "%$search%";
        $stmt->bind_param("sssss", $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $data = array();
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // Clear any previous output
    ob_clean();

    // Set proper headers
    header('Content-Type: application/json');

    // Response
    $response = array(
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        "recordsTotal" => intval($totalRecords),
        "recordsFiltered" => intval($totalFiltered),
        "data" => $data
    );

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(array(
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => array(),
        "error" => $e->getMessage()
    ));
    exit;
}
?> 