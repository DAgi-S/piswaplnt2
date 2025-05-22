<?php

class SchemaManager {
    private static $instance = null;
    private $schema = null;
    private $schemaFile;

    private function __construct() {
        $this->schemaFile = __DIR__ . '/db_schema.json';
        $this->loadSchema();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadSchema() {
        if (!file_exists($this->schemaFile)) {
            throw new Exception("Schema file not found. Please run generate_schema.php first.");
        }
        
        $content = file_get_contents($this->schemaFile);
        $this->schema = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error parsing schema file: " . json_last_error_msg());
        }
    }

    public function refreshSchema() {
        $this->loadSchema();
    }

    public function tableExists($tableName) {
        return isset($this->schema[$tableName]);
    }

    public function getTableColumns($tableName) {
        if (!$this->tableExists($tableName)) {
            throw new Exception("Table '$tableName' does not exist in schema.");
        }
        return $this->schema[$tableName]['columns'];
    }

    public function columnExists($tableName, $columnName) {
        if (!$this->tableExists($tableName)) {
            return false;
        }
        
        foreach ($this->schema[$tableName]['columns'] as $column) {
            if ($column['name'] === $columnName) {
                return true;
            }
        }
        return false;
    }

    public function getColumnDetails($tableName, $columnName) {
        if (!$this->tableExists($tableName)) {
            throw new Exception("Table '$tableName' does not exist in schema.");
        }
        
        foreach ($this->schema[$tableName]['columns'] as $column) {
            if ($column['name'] === $columnName) {
                return $column;
            }
        }
        
        throw new Exception("Column '$columnName' does not exist in table '$tableName'.");
    }

    public function getPrimaryKey($tableName) {
        if (!$this->tableExists($tableName)) {
            throw new Exception("Table '$tableName' does not exist in schema.");
        }
        return $this->schema[$tableName]['primary_key'];
    }

    public function getForeignKeys($tableName) {
        if (!$this->tableExists($tableName)) {
            throw new Exception("Table '$tableName' does not exist in schema.");
        }
        return $this->schema[$tableName]['foreign_keys'];
    }

    public function getReferencingTables($tableName) {
        if (!$this->tableExists($tableName)) {
            throw new Exception("Table '$tableName' does not exist in schema.");
        }

        $referencingTables = [];
        foreach ($this->schema as $otherTable => $tableData) {
            foreach ($tableData['foreign_keys'] as $column => $reference) {
                if ($reference['references_table'] === $tableName) {
                    $referencingTables[] = [
                        'table' => $otherTable,
                        'column' => $column,
                        'references_field' => $reference['references_field']
                    ];
                }
            }
        }
        return $referencingTables;
    }

    public function getAllTables() {
        return array_keys($this->schema);
    }

    public function getTableSchema($tableName) {
        if (!$this->tableExists($tableName)) {
            throw new Exception("Table '$tableName' does not exist in schema.");
        }
        return $this->schema[$tableName];
    }
} 