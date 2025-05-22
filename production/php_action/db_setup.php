<?php
require_once 'core.php';
require_once 'db_connect.php';

// Function to execute SQL file
function executeSQLFile($connect, $sqlFile) {
    try {
        // Read the SQL file
        $sql = file_get_contents($sqlFile);
        
        if ($sql === false) {
            throw new Exception("Error reading SQL file: " . $sqlFile);
        }

        // Split SQL by semicolon
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        // Begin transaction
        $connect->begin_transaction();
        
        // Execute each query
        foreach ($queries as $query) {
            if (!empty($query)) {
                if (!$connect->query($query)) {
                    throw new Exception("Error executing query: " . $connect->error);
                }
            }
        }
        
        // Commit transaction
        $connect->commit();
        
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        error_log("Database Setup Error: " . $e->getMessage());
        return false;
    }
}

// Execute the SQL file
$sqlFile = __DIR__ . '/../sql/create_warehouse_tables.sql';
if (executeSQLFile($connect, $sqlFile)) {
    echo json_encode(array(
        'success' => true,
        'message' => 'Database tables created successfully'
    ));
} else {
    echo json_encode(array(
        'success' => false,
        'message' => 'Error creating database tables'
    ));
}

// Close connection
$connect->close(); 