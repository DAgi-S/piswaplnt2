<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

try {
    // Get POST data
    $request = json_decode(file_get_contents('php://input'), true);
    if (!$request && $_POST) {
        $request = $_POST;
    }

    // Basic query
    $sql = "SELECT 
                id,
                name,
                description,
                status,
                created_at,
                updated_at
            FROM raw_material_categories
            WHERE 1=1";

    // Total records without filtering
    $totalRecords = $connect->query("SELECT COUNT(*) as total FROM raw_material_categories")->fetch_assoc()['total'];
    $filteredRecords = $totalRecords;

    // Search
    if (!empty($request['search']['value'])) {
        $searchValue = mysqli_real_escape_string($connect, $request['search']['value']);
        $sql .= " AND (name LIKE '%{$searchValue}%' 
                      OR description LIKE '%{$searchValue}%'
                      OR status LIKE '%{$searchValue}%')";
        
        // Update filtered records count
        $filteredRecords = $connect->query("SELECT COUNT(*) as total FROM raw_material_categories WHERE name LIKE '%{$searchValue}%' OR description LIKE '%{$searchValue}%' OR status LIKE '%{$searchValue}%'")->fetch_assoc()['total'];
    }

    // Ordering
    if (isset($request['order'])) {
        $columns = ['name', 'description', 'status'];
        $orderColumn = $request['order'][0]['column'];
        $orderDir = $request['order'][0]['dir'];
        
        if (isset($columns[$orderColumn])) {
            $sql .= " ORDER BY {$columns[$orderColumn]} " . ($orderDir === 'asc' ? 'ASC' : 'DESC');
        }
    } else {
        $sql .= " ORDER BY name ASC";
    }

    // Pagination
    if (isset($request['start']) && isset($request['length'])) {
        $sql .= " LIMIT " . (int)$request['start'] . ", " . (int)$request['length'];
    }

    // Execute query
    $result = $connect->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $connect->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?: '',
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }

    echo json_encode([
        'draw' => isset($request['draw']) ? (int)$request['draw'] : 0,
        'recordsTotal' => (int)$totalRecords,
        'recordsFiltered' => (int)$filteredRecords,
        'data' => $data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'draw' => isset($request['draw']) ? (int)$request['draw'] : 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'error' => $e->getMessage(),
        'data' => []
    ]);
}
?> 