# Database Schema Management System

This system provides tools for managing and accessing database schema information in a consistent and efficient way.

## Components

1. `generate_schema.php` - Script to generate the detailed schema JSON file
2. `generate_optimized_schema.php` - Script to generate the optimized schema JSON file
3. `SchemaManager.php` - Class to interact with the detailed schema
4. `OptimizedSchemaManager.php` - Class to interact with the optimized schema
5. `db_schema.json` - The detailed schema file
6. `db_schema_min.json` - The optimized schema file

## Optimized Schema Format

The optimized schema uses a compact notation to reduce file size while maintaining all essential information:

### Column Definition Format
```
fieldname[marker][type][nullable?][=default]
```

Where:
- `fieldname`: The name of the column
- `marker`: One of:
  - `*` for primary key (includes type)
  - `>` for foreign key (includes reference)
  - `:` for regular column (includes type)
- `type`: Shortened type notation:
  - `i` = int
  - `bi` = bigint
  - `s` = varchar
  - `t` = text
  - `d` = decimal
  - `ts` = timestamp
  - `dt` = datetime
  - `da` = date
  - `e` = enum
  - `ti` = tinyint
  - `f` = float
  - `do` = double
  - `c` = char
  - `b` = boolean
- `?` (optional): Indicates nullable field
- `=value` (optional): Default value

### Examples
```json
{
  "users": {
    "c": [
      "id*i",                    // Primary key, integer
      "username:s",              // VARCHAR
      "email:s?",               // Nullable VARCHAR
      "role_id>roles.id",       // Foreign key to roles.id
      "status:ti=1",            // TINYINT with default value 1
      "created_at:ts"           // TIMESTAMP
    ]
  }
}
```

## Getting Started

1. Generate the optimized schema:
```bash
php production/generate_optimized_schema.php
```

2. Include the OptimizedSchemaManager in your code:
```php
require_once 'production/OptimizedSchemaManager.php';

$schemaManager = OptimizedSchemaManager::getInstance();
```

## Usage Examples

### Check if a table exists
```php
if ($schemaManager->tableExists('sales_orders')) {
    // Table exists
}
```

### Get table columns
```php
$columns = $schemaManager->getTableColumns('sales_orders');
// Returns array with parsed column information
```

### Check if a column exists
```php
if ($schemaManager->columnExists('sales_orders', 'order_number')) {
    // Column exists
}
```

### Get column details
```php
$columnDetails = $schemaManager->getColumnDetails('sales_orders', 'order_number');
// Returns array with name, type, nullable, primary, foreign_key, and default
```

### Get primary key
```php
$primaryKey = $schemaManager->getPrimaryKey('sales_orders');
```

### Get foreign keys
```php
$foreignKeys = $schemaManager->getForeignKeys('sales_orders');
```

### Get tables that reference a specific table
```php
$referencingTables = $schemaManager->getReferencingTables('sales_orders');
```

### Get all tables
```php
$allTables = $schemaManager->getAllTables();
```

### Get complete table schema
```php
$tableSchema = $schemaManager->getTableSchema('sales_orders');
```

### Refresh schema (if database structure has changed)
```php
$schemaManager->refreshSchema();
```

## Best Practices

1. Use the optimized schema for better performance:
```php
$schemaManager = OptimizedSchemaManager::getInstance();
```

2. Handle exceptions when calling methods:
```php
try {
    $columns = $schemaManager->getTableColumns('table_name');
} catch (Exception $e) {
    // Handle the error
}
```

3. Refresh the schema when database structure changes:
```php
// After making database structure changes
php production/generate_optimized_schema.php
```

4. Use the schema manager to validate table and column names before executing queries:
```php
if ($schemaManager->columnExists('table_name', 'column_name')) {
    // Safe to use the column in your query
}
```

## Troubleshooting

1. If you get "Schema file not found" error:
   - Run `php production/generate_optimized_schema.php` to create the schema file

2. If schema is out of date:
   - Run `php production/generate_optimized_schema.php` to regenerate the schema
   - Call `$schemaManager->refreshSchema()` in your code

3. If you get database connection errors:
   - Check your database configuration in `config/database.php`
   - Ensure the database server is running
   - Verify your connection credentials 