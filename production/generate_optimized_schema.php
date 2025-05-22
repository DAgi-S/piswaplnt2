<?php
require_once __DIR__ . '/../config/database.php';

class OptimizedSchemaGenerator {
    private $conn;
    private $schema = [];
    
    // Type mapping to shorter versions
    private $typeMap = [
        'int' => 'i',
        'bigint' => 'bi',
        'varchar' => 's',
        'text' => 't',
        'decimal' => 'd',
        'timestamp' => 'ts',
        'datetime' => 'dt',
        'date' => 'da',
        'enum' => 'e',
        'tinyint' => 'ti',
        'float' => 'f',
        'double' => 'do',
        'char' => 'c',
        'boolean' => 'b'
    ];

    public function __construct() {
        $db = new PiStockDatabase();
        $this->conn = $db->getConnection();
    }

    private function simplifyType($fullType) {
        // Extract base type and size/precision
        preg_match('/^(\w+)(?:\(([\d,]+)\))?/', $fullType, $matches);
        $baseType = strtolower($matches[1]);
        $size = isset($matches[2]) ? $matches[2] : '';

        // Get shortened type
        $shortType = isset($this->typeMap[$baseType]) ? $this->typeMap[$baseType] : $baseType;

        // Add size if it exists and is not a standard size
        if ($size && !in_array("$baseType($size)", ['int(11)', 'varchar(255)', 'tinyint(1)'])) {
            $shortType .= $size;
        }

        return $shortType;
    }

    public function generateSchema() {
        // Get all tables
        $tables_query = "SHOW TABLES";
        $tables_result = $this->conn->query($tables_query);

        while ($table = $tables_result->fetch_array(MYSQLI_NUM)) {
            $table_name = $table[0];
            $this->schema[$table_name] = ['c' => []]; // 'c' for columns

            // Get foreign keys first
            $fk_query = "
                SELECT 
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM
                    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE
                    TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = '$table_name'
                    AND REFERENCED_TABLE_NAME IS NOT NULL";

            $fk_result = $this->conn->query($fk_query);
            $foreignKeys = [];
            while ($fk = $fk_result->fetch_assoc()) {
                $foreignKeys[$fk['COLUMN_NAME']] = $fk['REFERENCED_TABLE_NAME'] . '.' . $fk['REFERENCED_COLUMN_NAME'];
            }

            // Get columns and their details
            $columns_query = "SHOW COLUMNS FROM `$table_name`";
            $columns_result = $this->conn->query($columns_query);

            while ($column = $columns_result->fetch_assoc()) {
                $columnDef = $column['Field'];
                
                // Add type
                $shortType = $this->simplifyType($column['Type']);
                
                // Add primary key marker
                if ($column['Key'] === 'PRI') {
                    $columnDef .= '*';
                }
                // Add foreign key reference
                elseif (isset($foreignKeys[$column['Field']])) {
                    $columnDef .= '>' . $foreignKeys[$column['Field']];
                }
                // Add type for non-key fields
                else {
                    $columnDef .= ':' . $shortType;
                }
                
                // Add nullable marker
                if ($column['Null'] === 'YES') {
                    $columnDef .= '?';
                }
                
                // Add default value if not null and not current_timestamp
                if ($column['Default'] !== null && $column['Default'] !== 'current_timestamp()') {
                    $columnDef .= '=' . $column['Default'];
                }

                $this->schema[$table_name]['c'][] = $columnDef;
            }
        }

        return $this->schema;
    }

    public function saveSchema($filename = 'db_schema_min.json') {
        $schema = $this->generateSchema();
        $json = json_encode($schema, JSON_PRETTY_PRINT);
        file_put_contents($filename, $json);
        return true;
    }
}

// Generate and save the optimized schema
try {
    $generator = new OptimizedSchemaGenerator();
    $generator->saveSchema(__DIR__ . '/db_schema_min.json');
    echo "Optimized schema generated successfully!\n";
} catch (Exception $e) {
    echo "Error generating optimized schema: " . $e->getMessage() . "\n";
} 