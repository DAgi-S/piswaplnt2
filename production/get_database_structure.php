<?php
require_once 'php_action/core.php';

$output = array();
$html = '';

// Get all tables
$sql = "SHOW TABLES";
$result = $connect->query($sql);

while ($row = $result->fetch_array()) {
    $table = $row[0];
    
    // Get table structure
    $structure = array();
    $sql2 = "DESCRIBE $table";
    $result2 = $connect->query($sql2);
    
    while ($row2 = $result2->fetch_assoc()) {
        $structure[] = $row2;
    }
    
    $output[$table] = $structure;
    
    // Create HTML output
    $html .= "<h3>Table: $table</h3>\n";
    $html .= "<table border='1'>\n";
    $html .= "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    
    foreach ($structure as $field) {
        $html .= "<tr>";
        $html .= "<td>{$field['Field']}</td>";
        $html .= "<td>{$field['Type']}</td>";
        $html .= "<td>{$field['Null']}</td>";
        $html .= "<td>{$field['Key']}</td>";
        $html .= "<td>{$field['Default']}</td>";
        $html .= "<td>{$field['Extra']}</td>";
        $html .= "</tr>\n";
    }
    
    $html .= "</table><br>\n";
}

// Save text version
file_put_contents('../currenttables.txt', print_r($output, true));

// Save HTML version
file_put_contents('../databasetables.html', "<!DOCTYPE html>
<html>
<head>
    <title>Database Structure</title>
    <style>
        table { border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px; border: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Database Structure</h1>
    $html
</body>
</html>");

echo "Database structure has been saved to currenttables.txt and databasetables.html in the root directory";
?> 