<?php
require_once 'core.php';

// Get search value
$search = $_POST['search']['value'];

// Get order column
$order_column = $_POST['order'][0]['column'];
$order_dir = $_POST['order'][0]['dir'];

// Column names
$columns = array(
    'so.order_number',
    'so.order_date',
    'c.company_name',
    'GROUP_CONCAT(p.name)',
    'so.subtotal',
    'so.vat_amount',
    'so.withholding_amount',
    'so.discount_amount',
    'so.grand_total',
    'so.paid_amount',
    '(so.grand_total - so.paid_amount)',
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
            so.vat_amount as vat,
            so.withholding_amount as withholding,
            so.discount_amount as discount,
            so.grand_total,
            so.paid_amount,
            (so.grand_total - so.paid_amount) as balance,
            so.payment_status,
            u.username as created_by
          FROM sales_orders so
          LEFT JOIN clients c ON so.client_id = c.id
          LEFT JOIN sales_order_items soi ON so.id = soi.order_id
          LEFT JOIN production_products p ON soi.product_id = p.id
          LEFT JOIN users u ON so.created_by = u.id
          WHERE 1=1";

// Search condition
if(!empty($search)) {
    $query .= " AND (so.order_number LIKE '%$search%' 
                OR c.company_name LIKE '%$search%'
                OR p.name LIKE '%$search%'
                OR u.username LIKE '%$search%'
                OR so.payment_status LIKE '%$search%')";
}

// Group by to avoid duplicates
$query .= " GROUP BY so.id";

// Get total records
$totalRecords = $connect->query("SELECT COUNT(DISTINCT so.id) as total FROM sales_orders so")->fetch_object()->total;

// Get filtered records
$stmt = $connect->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$totalFiltered = $result->num_rows;

// Order
if(isset($order_column) && isset($order_dir)) {
    $query .= " ORDER BY " . $columns[$order_column] . " " . $order_dir;
}

// Limit
if(isset($_POST['start']) && isset($_POST['length'])) {
    $query .= " LIMIT " . $_POST['start'] . ", " . $_POST['length'];
}

// Execute final query
$stmt = $connect->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$data = array();
while($row = $result->fetch_assoc()) {
    $row['actions'] = ''; // Actions will be rendered by DataTable
    $data[] = $row;
}

// Response
$response = array(
    "draw" => intval($_POST['draw']),
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data
);

echo json_encode($response);
?> 