<?php

class OptimizedSchemaManager {
    private static $instance = null;
    private $schema = null;
    private $schemaFile;

    private function __construct() {
        $this->schemaFile = __DIR__ . '/db_schema_min.json';
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
            throw new Exception("Schema file not found. Please run generate_optimized_schema.php first.");
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

    private function parseColumnDefinition($columnDef) {
        preg_match('/^([^:*>]+)([*>:])([^?=]*)?(\?)?(?:=(.*))?$/', $columnDef, $matches);
        
        if (empty($matches)) {
            return [
                'name' => $columnDef,
                'type' => 's',  // default to string type if parsing fails
                'primary' => false,
                'foreign_key' => null,
                'nullable' => false,
                'default' => null
            ];
        }

        $result = [
            'name' => $matches[1],
            'type' => 's',  // default to string type
            'primary' => false,
            'foreign_key' => null,
            'nullable' => isset($matches[4]),
            'default' => isset($matches[5]) ? $matches[5] : null
        ];

        switch ($matches[2]) {
            case '*':
                $result['primary'] = true;
                $result['type'] = $matches[3] ?: 'i';  // default to integer for primary keys
                break;
            case '>':
                $result['foreign_key'] = $matches[3];
                $result['type'] = 'i';  // foreign keys are typically integers
                break;
            case ':':
                $result['type'] = $matches[3] ?: 's';  // default to string type
                break;
        }

        // Extract numeric precision for decimal types
        if (strpos($result['type'], ',') !== false) {
            list($baseType, $precision) = explode(',', $result['type']);
            $result['type'] = $baseType;
            $result['precision'] = $precision;
        }

        return $result;
    }

    public function getColumnDefinition($columnDef) {
        return $this->parseColumnDefinition($columnDef);
    }

    public function getTableColumns($tableName) {
        if (!$this->tableExists($tableName)) {
            throw new Exception("Table '$tableName' does not exist in schema.");
        }

        $columns = [];
        foreach ($this->schema[$tableName]['c'] as $columnDef) {
            $parsed = $this->parseColumnDefinition($columnDef);
            if ($parsed) {
                $columns[] = $parsed;
            }
        }
        return $columns;
    }

    public function columnExists($tableName, $columnName) {
        if (!$this->tableExists($tableName)) {
            return false;
        }
        
        foreach ($this->schema[$tableName]['c'] as $columnDef) {
            if (strpos($columnDef, $columnName) === 0) {
                return true;
            }
        }
        return false;
    }

    public function getColumnDetails($tableName, $columnName) {
        if (!$this->tableExists($tableName)) {
            throw new Exception("Table '$tableName' does not exist in schema.");
        }
        
        foreach ($this->schema[$tableName]['c'] as $columnDef) {
            if (strpos($columnDef, $columnName) === 0) {
                $parsed = $this->parseColumnDefinition($columnDef);
                if ($parsed) {
                    return $parsed;
                }
            }
        }
        
        throw new Exception("Column '$columnName' does not exist in table '$tableName'.");
    }

    public function getPrimaryKey($tableName) {
        if (!$this->tableExists($tableName)) {
            throw new Exception("Table '$tableName' does not exist in schema.");
        }
        
        foreach ($this->schema[$tableName]['c'] as $columnDef) {
            if (strpos($columnDef, '*') !== false) {
                return substr($columnDef, 0, strpos($columnDef, '*'));
            }
        }
        return null;
    }

    public function getForeignKeys($tableName) {
        if (!$this->tableExists($tableName)) {
            throw new Exception("Table '$tableName' does not exist in schema.");
        }

        $foreignKeys = [];
        foreach ($this->schema[$tableName]['c'] as $columnDef) {
            if (strpos($columnDef, '>') !== false) {
                list($columnName, $reference) = explode('>', $columnDef);
                $foreignKeys[$columnName] = $reference;
            }
        }
        return $foreignKeys;
    }

    public function getReferencingTables($tableName) {
        if (!$this->tableExists($tableName)) {
            throw new Exception("Table '$tableName' does not exist in schema.");
        }

        $referencingTables = [];
        foreach ($this->schema as $otherTable => $tableData) {
            foreach ($tableData['c'] as $columnDef) {
                if (strpos($columnDef, '>' . $tableName . '.') !== false) {
                    list($columnName, $reference) = explode('>', $columnDef);
                    $referencingTables[] = [
                        'table' => $otherTable,
                        'column' => $columnName,
                        'references_field' => substr($reference, strlen($tableName) + 1)
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