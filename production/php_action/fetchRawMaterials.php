<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file access
error_log("Accessing fetchRawMaterials.php");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set proper headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Include database connection
$dbPath = dirname(__FILE__) . '/db_connect.php';
error_log("Looking for database connection file at: " . $dbPath);

if (!file_exists($dbPath)) {
    error_log("Database connection file not found!");
    http_response_code(500);
    die(json_encode([
        'data' => [],
        'error' => true,
        'message' => 'Database configuration file not found'
    ]));
}

require_once $dbPath;

// Verify database connection
if (!isset($connect)) {
    error_log("Database connection variable not set!");
    http_response_code(500);
    die(json_encode([
        'data' => [],
        'error' => true,
        'message' => 'Database connection not established'
    ]));
}

if ($connect->connect_error) {
    error_log("Database connection error: " . $connect->connect_error);
    http_response_code(500);
    die(json_encode([
        'data' => [],
        'error' => true,
        'message' => 'Database connection failed: ' . $connect->connect_error
    ]));
}

try {
    // Set character encoding
    if (!$connect->set_charset("utf8mb4")) {
        throw new Exception("Error setting character encoding: " . $connect->error);
    }

    // Test database connection with a simple query
    $test_query = "SELECT 1";
    if (!$connect->query($test_query)) {
        throw new Exception("Database connection test failed: " . $connect->error);
    }

    // Prepare the SQL query
    $sql = "SELECT 
                rm.id,
                rm.material_code,
                rm.name,
                rm.description,
                rm.unit,
                COALESCE(rm.current_stock, 0) as current_stock,
                COALESCE(rm.min_stock_level, 0) as min_stock_level,
                COALESCE(rm.cost_per_unit, 0) as cost_per_unit,
                rm.status,
                rm.created_at,
                rmc.name as category_name,
                rmc.id as category_id
            FROM raw_materials rm
            LEFT JOIN raw_material_categories rmc ON rm.category_id = rmc.id
            WHERE rm.status != 'deleted'
            ORDER BY rm.material_code ASC";

    // Log the query
    error_log("Executing query: " . $sql);

    // Execute the query
    $result = $connect->query($sql);
    
    if (!$result) {
        throw new Exception("Query failed: " . $connect->error);
    }

    error_log("Query executed successfully. Found " . $result->num_rows . " rows");

    $data = [];
    
    // Fetch all rows
    while ($row = $result->fetch_assoc()) {
        try {
            $data[] = [
                'id' => intval($row['id']),
                'material_code' => $row['material_code'],
                'name' => $row['name'],
                'category_name' => $row['category_name'] ?? '',
                'category_id' => $row['category_id'] ? intval($row['category_id']) : null,
                'unit' => strtoupper($row['unit']),
                'current_stock' => floatval($row['current_stock']),
                'min_stock_level' => floatval($row['min_stock_level']),
                'cost_per_unit' => floatval($row['cost_per_unit']),
                'description' => $row['description'] ?? '',
                'status' => $row['status'],
                'created_at' => $row['created_at']
            ];
        } catch (Exception $rowError) {
            error_log("Error processing row: " . $rowError->getMessage());
            continue;
        }
    }

    error_log("Successfully processed " . count($data) . " rows");

    // Return success response
    $response = [
        'data' => $data,
        'error' => false,
        'recordsTotal' => count($data),
        'recordsFiltered' => count($data)
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

} catch (Exception $e) {
    error_log("Error in fetchRawMaterials.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'data' => [],
        'error' => true,
        'message' => 'An error occurred while fetching data: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'sql_error' => $connect->error ?? null
        ]
    ]);
} finally {
    // Clean up
    if (isset($result) && $result instanceof mysqli_result) {
        $result->free();
    }
    if (isset($connect)) {
        $connect->close();
    }
} 