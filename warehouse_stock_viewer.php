<?php
// Include database connection
require_once 'production/php_action/db_connect.php';

// Set header for proper display
header('Content-Type: text/html; charset=utf-8');

// Process filter form if submitted
$warehouse_filter = '';
$item_type_filter = '';
$item_id_filter = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['warehouse_id'])) {
        $warehouse_filter = "AND ws.warehouse_id = " . intval($_POST['warehouse_id']);
    }
    
    if (!empty($_POST['item_type'])) {
        $item_type_filter = "AND ws.item_type = '" . $connect->real_escape_string($_POST['item_type']) . "'";
    }
    
    if (!empty($_POST['item_id'])) {
        $item_id_filter = "AND ws.item_id = " . intval($_POST['item_id']);
    }
}

// Get warehouses for the filter dropdown
$warehouses = [];
$sql_warehouses = "SELECT id, name FROM warehouses ORDER BY name";
$result_warehouses = $connect->query($sql_warehouses);
if ($result_warehouses) {
    while ($row = $result_warehouses->fetch_assoc()) {
        $warehouses[$row['id']] = $row['name'];
    }
}

// Main page content
echo "<!DOCTYPE html>
<html>
<head>
    <title>Warehouse Stock Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; text-align: left; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .filter-form { background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .filter-form label { display: inline-block; margin-right: 15px; }
        .filter-form select, .filter-form input { padding: 5px; border-radius: 3px; border: 1px solid #ccc; }
        .filter-form button { padding: 5px 10px; background-color: #4CAF50; color: white; border: none; 
                              border-radius: 3px; cursor: pointer; }
        .filter-form button:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <h1>Warehouse Stock Viewer</h1>
    
    <div class='filter-form'>
        <form method='post' action=''>
            <label>
                Warehouse:
                <select name='warehouse_id'>
                    <option value=''>All Warehouses</option>";
                    
                    foreach ($warehouses as $id => $name) {
                        $selected = (isset($_POST['warehouse_id']) && $_POST['warehouse_id'] == $id) ? 'selected' : '';
                        echo "<option value='$id' $selected>$name</option>";
                    }
                    
echo "          </select>
            </label>
            
            <label>
                Item Type:
                <select name='item_type'>
                    <option value=''>All Types</option>
                    <option value='raw_material' " . (isset($_POST['item_type']) && $_POST['item_type'] == 'raw_material' ? 'selected' : '') . ">Raw Material</option>
                    <option value='finished_good' " . (isset($_POST['item_type']) && $_POST['item_type'] == 'finished_good' ? 'selected' : '') . ">Finished Good</option>
                </select>
            </label>
            
            <label>
                Item ID:
                <input type='number' name='item_id' value='" . (isset($_POST['item_id']) ? htmlspecialchars($_POST['item_id']) : '') . "'>
            </label>
            
            <button type='submit'>Filter</button>
            <button type='button' onclick='window.location.href=\"warehouse_stock_viewer.php\"'>Reset</button>
        </form>
    </div>";

// Query to get warehouse stock with additional information
$sql = "SELECT 
            ws.id,
            w.name AS warehouse_name,
            ws.item_type,
            ws.item_id,
            CASE 
                WHEN ws.item_type = 'raw_material' THEN rm.name
                WHEN ws.item_type = 'finished_good' THEN pp.name
                ELSE 'Unknown'
            END AS item_name,
            ws.quantity,
            ws.created_at,
            ws.updated_at
        FROM 
            warehouse_stock ws
        LEFT JOIN 
            warehouses w ON ws.warehouse_id = w.id
        LEFT JOIN 
            raw_materials rm ON ws.item_id = rm.id AND ws.item_type = 'raw_material'
        LEFT JOIN 
            production_products pp ON ws.item_id = pp.id AND ws.item_type = 'finished_good'
        WHERE 
            1=1 
            $warehouse_filter
            $item_type_filter
            $item_id_filter
        ORDER BY 
            w.name, ws.item_type, ws.item_id";

$result = $connect->query($sql);

if (!$result) {
    echo "<p>Error executing query: " . $connect->error . "</p>";
} else {
    echo "<table>
            <tr>
                <th>ID</th>
                <th>Warehouse</th>
                <th>Item Type</th>
                <th>Item ID</th>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Created At</th>
                <th>Updated At</th>
            </tr>";
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['warehouse_name']}</td>
                    <td>{$row['item_type']}</td>
                    <td>{$row['item_id']}</td>
                    <td>{$row['item_name']}</td>
                    <td align='right'>{$row['quantity']}</td>
                    <td>{$row['created_at']}</td>
                    <td>{$row['updated_at']}</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='8' align='center'>No warehouse stock records found</td></tr>";
    }
    
    echo "</table>";
}

// Close connection
$connect->close();

echo "</body></html>";

// Don't forget to update the changelog
$today = date('Y-m-d');
$changelog_entry = "
$today
- warehouse_stock_viewer.php
- check_stock_status.php
- check_sales_stock_deduction.php
";
?> 