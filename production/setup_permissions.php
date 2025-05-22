<?php
require_once 'php_action/db_connect.php';

// Function to execute SQL and handle errors
function executeSQLFile($connect, $file) {
    try {
        // Read SQL file
        $sql = file_get_contents($file);
        
        if ($sql === false) {
            throw new Exception("Error reading SQL file: $file");
        }
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map(
                'trim',
                explode(';', $sql)
            )
        );
        
        // Begin transaction
        $connect->begin_transaction();
        
        // Execute each statement
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                if (!$connect->query($statement)) {
                    throw new Exception("Error executing SQL: " . $connect->error);
                }
            }
        }
        
        // Commit transaction
        $connect->commit();
        
        echo "Successfully executed SQL file: $file\n";
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Execute the setup script
$sqlFile = __DIR__ . '/sql/setup_tables.sql';

if (executeSQLFile($connect, $sqlFile)) {
    echo "Successfully set up production permissions and roles.\n";
    
    // Verify the setup
    $tables = ['permissions', 'user_roles', 'role_permissions'];
    
    foreach ($tables as $table) {
        $result = $connect->query("SELECT COUNT(*) as count FROM $table");
        $row = $result->fetch_assoc();
        echo "$table table has {$row['count']} records.\n";
    }
    
    // Show production roles
    $result = $connect->query("
        SELECT r.role_name, COUNT(rp.permission_id) as permission_count
        FROM user_roles r
        LEFT JOIN role_permissions rp ON r.role_id = rp.role_id
        WHERE r.role_name LIKE 'Production%'
        GROUP BY r.role_name
    ");
    
    echo "\nProduction Roles:\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['role_name']}: {$row['permission_count']} permissions\n";
    }
    
} else {
    echo "Failed to set up production permissions and roles.\n";
}

// Close database connection
$connect->close(); 