<?php
require_once 'db_connect.php';

function checkPermissionsSetup() {
    global $connect;
    
    try {
        // Check if permissions table exists
        $tableExists = $connect->query("SHOW TABLES LIKE 'permissions'")->num_rows > 0;
        if (!$tableExists) {
            error_log("Permissions table does not exist");
            return false;
        }

        // Check if role_permissions table exists
        $tableExists = $connect->query("SHOW TABLES LIKE 'role_permissions'")->num_rows > 0;
        if (!$tableExists) {
            error_log("Role_permissions table does not exist");
            return false;
        }

        // Check if has_permission function exists
        $functionExists = $connect->query("SHOW FUNCTION STATUS WHERE Db = DATABASE() AND Name = 'has_permission'")->num_rows > 0;
        if (!$functionExists) {
            error_log("has_permission function does not exist");
            return false;
        }

        // Check if admin role exists and has basic permissions
        $adminPermissions = $connect->query("
            SELECT COUNT(*) as count 
            FROM role_permissions rp 
            JOIN permissions p ON rp.permission_id = p.permission_id 
            WHERE rp.role_id = 1 
            AND p.module = 'system_config'
        ")->fetch_assoc();

        if ($adminPermissions['count'] == 0) {
            error_log("Admin role missing system_config permissions");
            return false;
        }

        return true;
    } catch (Exception $e) {
        error_log("Error checking permissions setup: " . $e->getMessage());
        return false;
    }
}

function setupPermissions() {
    global $connect;
    
    try {
        // Read and execute the SQL file
        $sqlFile = file_get_contents(__DIR__ . '/../sql/system_permissions.sql');
        
        // Split SQL file into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sqlFile)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $connect->query($statement);
                if ($connect->error) {
                    error_log("Error executing SQL: " . $connect->error);
                    return false;
                }
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error setting up permissions: " . $e->getMessage());
        return false;
    }
}

// Check and setup if needed
if (!checkPermissionsSetup()) {
    error_log("Permissions system not properly setup, attempting to setup...");
    if (setupPermissions()) {
        error_log("Permissions system setup successfully");
    } else {
        error_log("Failed to setup permissions system");
    }
}
?> 