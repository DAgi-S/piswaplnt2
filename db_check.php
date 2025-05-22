<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=pistocklntmarch", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($tables) . " tables:\n\n";
    
    foreach ($tables as $table) {
        echo "\nTABLE: $table\n";
        echo str_repeat("=", strlen($table) + 7) . "\n";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            echo "{$col['Field']} - {$col['Type']}";
            if ($col['Key'] === 'PRI') echo " (PRIMARY KEY)";
            if ($col['Key'] === 'MUL') echo " (INDEX)";
            if ($col['Null'] === 'NO') echo " NOT NULL";
            if ($col['Default'] !== null) echo " DEFAULT '{$col['Default']}'";
            echo "\n";
        }
        echo "\n";
    }
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?> 