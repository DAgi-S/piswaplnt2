<?php
require_once 'core.php';
require_once 'includes/Database.php';

// Get database connection
$db = Database::getInstance()->getConnection();

// Read and execute the SQL file
$sqlFile = __DIR__ . '/../sql/create_production_analytics_tables.sql';
$sql = file_get_contents($sqlFile);

if ($sql === false) {
    die("Error reading SQL file");
}

// Split SQL into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = true;
$errors = [];

// Execute each statement
foreach ($statements as $statement) {
    if (!empty($statement)) {
        try {
            if (!$db->query($statement)) {
                $success = false;
                $errors[] = "Error executing statement: " . $db->error;
            }
        } catch (Exception $e) {
            $success = false;
            $errors[] = "Exception: " . $e->getMessage();
        }
    }
}

// Return result as JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'errors' => $errors
]); 