<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

try {
    // Fix the path to db_connect.php
    $dbPath = dirname(dirname(__FILE__)) . '/includes/db_connect.php';
    if (!file_exists($dbPath)) {
        throw new Exception("Database connection file not found at: " . $dbPath);
    }
    require_once $dbPath;

    // Clear any previous output
    ob_clean();

    // Set proper content type and prevent caching
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Initialize response array
    $response = array(
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    );

    // Verify database connection
    if (!isset($connect) || !$connect) {
        throw new Exception("Database connection failed or not properly initialized");
    }

    // Test database connection
    try {
        $connect->query("SELECT 1");
    } catch (PDOException $e) {
        throw new Exception("Database connection test failed: " . $e->getMessage());
    }

    // Base query for total records
    $countSql = "SELECT COUNT(*) as total FROM inventory_movement_log";
    $stmt = $connect->query($countSql);
    if (!$stmt) {
        throw new Exception("Error executing count query: " . print_r($connect->errorInfo(), true));
    }
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $response['recordsTotal'] = $totalRecords;

    // Main query
    $sql = "SELECT 
                iml.id,
                iml.created_at,
                iml.item_type,
                iml.item_id,
                iml.source_type,
                iml.source_id,
                iml.destination_type,
                iml.destination_id,
                iml.quantity,
                iml.movement_type,
                iml.reference_type,
                iml.reference_id,
                iml.status,
                COALESCE(rm.name, pp.name, 'Unknown') as item_name
            FROM inventory_movement_log iml
            LEFT JOIN raw_materials rm ON iml.item_type = 'raw_material' AND iml.item_id = rm.id
            LEFT JOIN production_products pp ON iml.item_type IN ('finished_good', 'product') AND iml.item_id = pp.id
            WHERE 1=1";

    $params = array();

    // Apply filters
    if (!empty($_POST['startDate'])) {
        $sql .= " AND DATE(iml.created_at) >= ?";
        $params[] = $_POST['startDate'];
    }
    
    if (!empty($_POST['endDate'])) {
        $sql .= " AND DATE(iml.created_at) <= ?";
        $params[] = $_POST['endDate'];
    }
    
    if (!empty($_POST['movementType'])) {
        $sql .= " AND iml.movement_type = ?";
        $params[] = $_POST['movementType'];
    }
    
    if (!empty($_POST['status'])) {
        $sql .= " AND iml.status = ?";
        $params[] = $_POST['status'];
    }

    // Add search condition
    if (!empty($_POST['search']['value'])) {
        $searchValue = $_POST['search']['value'];
        $sql .= " AND (iml.item_type LIKE ? OR rm.name LIKE ? OR pp.name LIKE ?)";
        $params[] = "%$searchValue%";
        $params[] = "%$searchValue%";
        $params[] = "%$searchValue%";
    }

    // Get filtered count
    $countStmt = $connect->prepare("SELECT COUNT(*) as total FROM ($sql) as filtered");
    if (!$countStmt) {
        throw new Exception("Error preparing filtered count query: " . print_r($connect->errorInfo(), true));
    }
    $countStmt->execute($params);
    $filteredCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $response['recordsFiltered'] = $filteredCount;

    // Add sorting
    if (!empty($_POST['order'])) {
        $columns = array('created_at', 'item_type', 'item_name', 'source_type', 'destination_type', 'quantity', 'movement_type', 'reference_type', 'status');
        $orderColumn = $_POST['order'][0]['column'];
        $orderDir = $_POST['order'][0]['dir'];
        
        if (isset($columns[$orderColumn])) {
            $sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
        }
    } else {
        $sql .= " ORDER BY iml.created_at DESC";
    }

    // Add pagination
    if (isset($_POST['start']) && isset($_POST['length'])) {
        $sql .= " LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
    }

    // Execute final query
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing main query: " . print_r($connect->errorInfo(), true));
    }
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process results
    $data = array();
    foreach ($results as $row) {
        // Get source and destination names
        $sourceName = getLocationName($connect, $row['source_type'], $row['source_id']);
        $destinationName = getLocationName($connect, $row['destination_type'], $row['destination_id']);

        $data[] = array(
            'id' => $row['id'],
            'created_at' => $row['created_at'],
            'item_type' => $row['item_type'],
            'item_name' => $row['item_name'],
            'source_type' => $row['source_type'],
            'source_name' => $sourceName,
            'destination_type' => $row['destination_type'],
            'destination_name' => $destinationName,
            'quantity' => $row['quantity'],
            'movement_type' => $row['movement_type'],
            'reference_type' => $row['reference_type'],
            'reference_id' => $row['reference_id'],
            'status' => $row['status']
        );
    }

    $response['data'] = $data;

} catch (Exception $e) {
    // Log the error with full details
    error_log("Error in " . __FILE__ . ": " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    error_log("POST data: " . print_r($_POST, true));

    // Clear any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Send error response
    http_response_code(500);
    echo json_encode(array(
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array(),
        'error' => "Database connection error: " . $e->getMessage()
    ));
    exit;
}

// Helper function to get location names
function getLocationName($connect, $type, $id) {
    if (empty($type) || empty($id)) {
        return 'N/A';
    }

    try {
        $sql = "";
        switch ($type) {
            case 'warehouse':
                $sql = "SELECT name FROM warehouses WHERE id = ?";
                break;
            case 'supplier':
                $sql = "SELECT company_name as name FROM suppliers WHERE id = ?";
                break;
            case 'workstation':
                $sql = "SELECT name FROM workstations WHERE id = ?";
                break;
            case 'production':
                $sql = "SELECT CONCAT('Production #', order_number) as name FROM production_orders WHERE id = ?";
                break;
            default:
                return ucfirst($type);
        }

        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing location query: " . print_r($connect->errorInfo(), true));
        }
        $stmt->execute(array($id));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['name'] : 'Unknown';
    } catch (Exception $e) {
        error_log("Error getting location name: " . $e->getMessage());
        return 'Error';
    }
}

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Send the response
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit; 