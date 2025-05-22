<?php
require_once 'core.php';
require_once 'classes/Database.php';

function setupSystemConfig() {
    try {
        $database = new Database();
        $conn = $database->connect();
        
        // Read and execute the SQL file
        $sqlFile = __DIR__ . '/../sql/system_config_tables.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', 
                explode(';', $sql)
            ),
            function($statement) {
                return !empty($statement);
            }
        );
        
        // Execute each statement
        foreach ($statements as $statement) {
            try {
                $conn->exec($statement);
            } catch (PDOException $e) {
                // Log the error but continue with other statements
                error_log("Error executing SQL statement: " . $e->getMessage());
                error_log("Statement: " . $statement);
            }
        }
        
        // Import existing settings if available
        importExistingSettings($conn);
        
        echo json_encode([
            'success' => true,
            'message' => 'System configuration tables created successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Error in setupSystemConfig: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error setting up system configuration: ' . $e->getMessage()
        ]);
    }
}

function importExistingSettings($conn) {
    try {
        // Check if old settings table exists
        $stmt = $conn->query("SHOW TABLES LIKE 'settings'");
        if ($stmt->rowCount() > 0) {
            // Get existing settings
            $stmt = $conn->query("SELECT * FROM settings");
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($settings as $setting) {
                // Determine category based on setting key
                $category = 'maintenance';
                if (strpos($setting['setting_key'], 'mail_') === 0) {
                    $category = 'email';
                } elseif (strpos($setting['setting_key'], 'db_') === 0) {
                    $category = 'database';
                } elseif (strpos($setting['setting_key'], 'backup_') === 0) {
                    $category = 'backup';
                }
                
                // Update the new settings table
                $sql = "UPDATE system_config_settings 
                       SET setting_value = :value 
                       WHERE setting_key = :key 
                       AND category_id = (
                           SELECT category_id 
                           FROM system_config_categories 
                           WHERE category_name = :category
                       )";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':value' => $setting['value'],
                    ':key' => $setting['setting_key'],
                    ':category' => $category
                ]);
            }
        }
    } catch (Exception $e) {
        error_log("Error importing existing settings: " . $e->getMessage());
    }
}

// Execute the setup
if (isset($_SESSION['userId']) && isset($_SESSION['role_id'])) {
    $stmt = $conn->prepare("SELECT has_permission(?, 'manage_system_config') AS has_perm");
    $stmt->execute([$_SESSION['role_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['has_perm']) {
        setupSystemConfig();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Insufficient permissions.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Please log in.'
    ]);
} 