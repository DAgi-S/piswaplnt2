<?php
require_once __DIR__ . '/../config/database.php';

class SchemaGenerator {
    private $conn;
    private $schema = [];

    public function __construct() {
        $db = new PiStockDatabase();
        $this->conn = $db->getConnection();
    }

    public function generateSchema() {
        // Get all tables
        $tables_query = "SHOW TABLES";
        $tables_result = $this->conn->query($tables_query);

        while ($table = $tables_result->fetch_array(MYSQLI_NUM)) {
            $table_name = $table[0];
            $this->schema[$table_name] = [
                'columns' => [],
                'primary_key' => null,
                'foreign_keys' => []
            ];

            // Get columns and their details
            $columns_query = "SHOW COLUMNS FROM `$table_name`";
            $columns_result = $this->conn->query($columns_query);

            while ($column = $columns_result->fetch_assoc()) {
                $this->schema[$table_name]['columns'][] = [
                    'name' => $column['Field'],
                    'type' => $column['Type'],
                    'nullable' => $column['Null'] === 'YES',
                    'default' => $column['Default']
                ];

                if ($column['Key'] === 'PRI') {
                    $this->schema[$table_name]['primary_key'] = $column['Field'];
                }
            }

            // Get foreign keys
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

            while ($fk = $fk_result->fetch_assoc()) {
                $this->schema[$table_name]['foreign_keys'][$fk['COLUMN_NAME']] = [
                    'references_table' => $fk['REFERENCED_TABLE_NAME'],
                    'references_field' => $fk['REFERENCED_COLUMN_NAME']
                ];
            }
        }

        return $this->schema;
    }

    public function saveSchema($filename = 'db_schema.json') {
        $schema = $this->generateSchema();
        $json = json_encode($schema, JSON_PRETTY_PRINT);
        file_put_contents($filename, $json);
        return true;
    }
}

// Generate and save the schema
try {
    $generator = new SchemaGenerator();
    $generator->saveSchema(__DIR__ . '/db_schema.json');
    echo "Schema generated successfully!\n";
} catch (Exception $e) {
    echo "Error generating schema: " . $e->getMessage() . "\n";
} 