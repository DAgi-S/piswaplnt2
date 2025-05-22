# Production Orders Documentation

## Overview
The production orders system provides a comprehensive interface for managing manufacturing and production processes. It allows authorized users to create, monitor, and manage production orders, track material consumption, and monitor production progress. The module includes comprehensive logging of all activities for audit and tracking purposes.

## File Structure

```
├── production/
│   ├── production_orders.php        # Main production orders interface
│   ├── production_detail.php        # Detailed view of a production order
│   ├── js/
│   │   ├── production_orders.js     # Frontend JavaScript for orders list
│   │   ├── production_detail.js     # Frontend JavaScript for order details
│   │   └── modules/
│   │       ├── production-order.js  # Production order module
│   │       └── status-handler.js    # Status management module
│   └── php_action/
│       ├── createProductionOrder.php # Handle order creation
│       ├── updateProductionOrder.php # Handle order updates
│       ├── deleteProductionOrder.php # Handle order deletion
│       └── classes/
│           └── ProductionManager.php # Core production management class
│       ├── includes/
│       │   ├── LogManager.php            # Logging functionality
│       │   └── ProductionMiddleware.php   # Access control
│       ├── logActivity.php               # Activity logging endpoint
│       └── custom/js/
│           └── production_orders.js          # Client-side functionality
```

## Database Structure

### Production Orders Table (`production_orders`)
```sql
CREATE TABLE production_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    product_id INT NOT NULL,
    target_quantity DECIMAL(10,2) NOT NULL,
    completed_quantity DECIMAL(10,2) DEFAULT 0.00,
    start_date DATE NOT NULL,
    expected_completion_date DATE NOT NULL,
    actual_completion_date DATE DEFAULT NULL,
    status ENUM('draft','confirmed','inprogress','completed','cancelled') DEFAULT 'draft',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    warehouse_id INT,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (product_id) REFERENCES production_products(id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);
```

### Production Products Table (`production_products`)
```sql
CREATE TABLE production_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    unit VARCHAR(20),
    cost_per_unit DECIMAL(10,2),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES production_categories(id)
);
```

### Production Order Materials Table (`production_order_materials`)
```sql
CREATE TABLE production_order_materials (
    production_order_id INT,
    material_id INT,
    required_quantity DECIMAL(10,2) NOT NULL,
    reserved_quantity DECIMAL(10,2) DEFAULT 0.00,
    consumed_quantity DECIMAL(10,2) DEFAULT 0.00,
    reservation_status ENUM('pending', 'partial', 'complete') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (production_order_id, material_id),
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(id)
);
```

### Production Progress Table (`production_progress`)
```sql
CREATE TABLE production_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    production_order_id INT,
    quantity DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);
```

### Production Quality Checks Table (`production_quality_checks`)
```sql
CREATE TABLE production_quality_checks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    production_order_id INT,
    check_date DATETIME NOT NULL,
    inspector_id INT,
    status ENUM('passed', 'failed', 'pending') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id),
    FOREIGN KEY (inspector_id) REFERENCES users(user_id)
);
```

### Production Waste Logs Table (`production_waste_logs`)
```sql
CREATE TABLE production_waste_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    production_order_id INT,
    material_id INT,
    quantity DECIMAL(10,2) NOT NULL,
    reason TEXT,
    recorded_by INT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id),
    FOREIGN KEY (material_id) REFERENCES raw_materials(id),
    FOREIGN KEY (recorded_by) REFERENCES users(user_id)
);
```

### Product Bill of Materials Table (`product_bom`)
```sql
CREATE TABLE product_bom (
    product_id INT,
    material_id INT,
    quantity_required DECIMAL(10,2) NOT NULL,
    wastage_percent DECIMAL(5,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id, material_id),
    FOREIGN KEY (product_id) REFERENCES production_products(id),
    FOREIGN KEY (material_id) REFERENCES raw_materials(id)
);
```

### Raw Materials Table (`raw_materials`)
```sql
CREATE TABLE raw_materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE,
    category_id INT,
    unit VARCHAR(20),
    cost_per_unit DECIMAL(10,2),
    minimum_stock DECIMAL(10,2),
    current_stock DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES raw_material_categories(id)
);
```

### Production Order Expenses Table (`production_order_expenses`)
```sql
CREATE TABLE production_order_expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    production_order_id INT,
    expense_category_id INT,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id),
    FOREIGN KEY (expense_category_id) REFERENCES production_expense_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);
```

### Production Shift Schedules Table (`production_shift_schedules`)
```sql
CREATE TABLE production_shift_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    production_order_id INT,
    workstation_id INT,
    shift_start DATETIME NOT NULL,
    shift_end DATETIME NOT NULL,
    operator_id INT,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id),
    FOREIGN KEY (workstation_id) REFERENCES workstations(id),
    FOREIGN KEY (operator_id) REFERENCES users(user_id)
);
```

### Activity Log Table
```sql
CREATE TABLE `activity_log` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `action` varchar(255) NOT NULL,
    `module` varchar(100) NOT NULL,
    `reference_id` bigint(20) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_module` (`module`),
    KEY `idx_reference` (`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Audit Log Table
```sql
CREATE TABLE `audit_log` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `activity_type` varchar(100) NOT NULL,
    `description` text,
    `old_value` text,
    `new_value` text,
    `reference_id` bigint(20) NOT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_activity_type` (`activity_type`),
    KEY `idx_reference` (`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Frontend Components

### Production Orders Interface (production_orders.php)
- Main interface for managing production orders
- Features:
  - Production orders listing with filtering and search
  - Add/Edit production order modal
  - Material requirements calculation
  - Progress tracking
  - Status management
  - Permissions-based UI adaptation

### Production Order Details Interface (production_detail.php)
- Detailed view of a specific production order
- Features:
  - Order information display
  - Material consumption tracking
  - Production progress updates
  - Status updates
  - Notes and documentation

### JavaScript Modules
```javascript
// Production Order Module (production-order.js)
- initDataTable()                // Initialize orders DataTable
- initAddOrder()                // Initialize add order functionality
- calculateMaterials()          // Calculate required materials
- updateProgress()             // Update production progress
- handleStatusChange()         // Handle order status changes
- validateMaterials()          // Validate material availability

// Status Handler Module (status-handler.js)
- updateOrderStatus()          // Update order status
- validateStatusTransition()   // Validate status changes
- updateUIElements()          // Update UI based on status
```

## Backend API Endpoints

### Production Order Management APIs
1. Create Production Order
   ```
   POST: php_action/createProductionOrder.php
   Payload: {
       product_id: number,
       target_quantity: number,
       start_date: string,
       expected_completion_date: string,
       notes: string
   }
   Response: {
       success: boolean,
       messages: string,
       order_id: number,
       order_number: string
   }
   ```

2. Update Production Order
   ```
   POST: php_action/updateProductionOrder.php
   Payload: {
       order_id: number,
       target_quantity: number,
       start_date: string,
       expected_completion_date: string,
       notes: string
   }
   Response: {
       success: boolean,
       messages: string
   }
   ```

3. Delete Production Order
   ```
   POST: php_action/deleteProductionOrder.php
   Payload: {
       order_id: number
   }
   Response: {
       success: boolean,
       messages: string
   }
   ```

4. Update Production Progress
   ```
   POST: php_action/updateProductionProgress.php
   Payload: {
       order_id: number,
       quantity: number,
       notes: string
   }
   Response: {
       success: boolean,
       messages: string,
       completed_quantity: number,
       completion_percentage: number
   }
   ```

### Fetch Production Orders API
```
GET/POST: php_action/fetchProductionOrders.php
Response Format: {
    draw: number,              // DataTables draw counter
    recordsTotal: number,      // Total records in database
    recordsFiltered: number,   // Records after filtering
    data: Array<{
        id: number,
        order_number: string,
        product_name: string,
        status: string,
        target_quantity: string,    // Formatted as "0.00"
        completed_quantity: string,  // Formatted as "0.00"
        start_date: string,         // Format: "YYYY-MM-DD"
        expected_completion_date: string,
        created_by: string
    }>
}
```

### Data Fetching Improvements
1. Null Value Handling
   ```sql
   SELECT 
       COALESCE(pp.name, 'N/A') as product_name,
       COALESCE(po.status, 'draft') as status,
       COALESCE(po.target_quantity, 0) as target_quantity,
       COALESCE(po.completed_quantity, 0) as completed_quantity
   ```

2. Number Formatting
   ```php
   // Format numbers with null check and consistent decimals
   $row['target_quantity'] = number_format((float)$row['target_quantity'], 2, '.', '');
   $row['completed_quantity'] = number_format((float)$row['completed_quantity'], 2, '.', '');
   ```

3. Date Formatting
   ```sql
   DATE_FORMAT(COALESCE(po.start_date, CURRENT_DATE), '%Y-%m-%d') as start_date
   ```

4. Character Encoding
   ```php
   // Ensure proper UTF-8 encoding
   array_walk_recursive($row, function(&$item) {
       if (is_string($item)) {
           $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
       }
   });
   ```

### Error Prevention
1. Error Display Control
   ```php
   error_reporting(E_ALL & ~E_NOTICE);
   ini_set('display_errors', 0);
   ```

2. Default Values
   - All numeric fields default to 0
   - All date fields default to current date
   - Text fields have meaningful defaults (N/A, System, etc.)

3. JSON Output Control
   ```php
   json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
   ```

4. Clean Error Responses
   ```php
   echo json_encode(array(
       'error' => true,
       'message' => "Error loading production orders"
   ));
   ```

## Production Order States

### Status Flow
1. Draft
   - Initial state for new orders
   - Can modify all order details
   - No material reservations

2. Planned
   - Order details finalized
   - Materials calculated and reserved
   - Ready to start production

3. In Progress
   - Production has started
   - Materials being consumed
   - Progress being recorded

4. Completed
   - Production target reached
   - All materials consumed
   - Final quality checks done

5. Cancelled
   - Order terminated
   - Materials released
   - Reason documented

### Status Transitions
```
Draft → Planned → In Progress → Completed
     ↘                ↘
       → Cancelled     → Cancelled
```

## Material Management

### Material Requirements
- Calculated based on Bill of Materials (BOM)
- Includes wastage calculations
- Validates stock availability
- Handles material reservations

### Material Consumption
- Tracks actual usage vs planned
- Records wastage
- Updates inventory levels
- Generates alerts for shortages

## Production Progress Tracking

### Progress Updates
- Record quantity produced
- Track completion percentage
- Document quality issues
- Monitor efficiency

### Progress Calculations
```php
$completionPercentage = ($completedQuantity / $targetQuantity) * 100;
$remainingQuantity = $targetQuantity - $completedQuantity;
```

## Security Measures

### Access Control
- Role-based access control (RBAC)
- Permission checks for all operations
- Audit trail for changes
- Data validation and sanitization

### Required Permissions
- `view_production_orders`
- `create_production_order`
- `edit_production_order`
- `delete_production_order`
- `manage_production_schedule`
- `manage_quality_control`

## Error Handling

### Common Issues
1. Material Shortages
   - Check stock levels
   - Validate reservations
   - Alert purchasing department

2. Progress Updates
   - Validate quantities
   - Check completion logic
   - Verify material consumption

3. Status Changes
   - Validate transitions
   - Check prerequisites
   - Update related records

## Best Practices

### Order Management
1. Use meaningful order numbers
2. Document changes thoroughly
3. Regular status updates
4. Monitor material usage
5. Track production efficiency

### Material Planning
1. Verify stock availability
2. Consider lead times
3. Account for wastage
4. Maintain safety stock
5. Regular inventory updates

## Implementation Examples

### Adding Progress Update
```javascript
function updateProgress(orderId, quantity, notes) {
    $.ajax({
        url: 'php_action/updateProductionProgress.php',
        method: 'POST',
        data: {
            orderId: orderId,
            quantity: quantity,
            notes: notes
        },
        success: function(response) {
            // Handle response
            updateProgressDisplay(response.completed_quantity);
            updateStatusIfNeeded(response.completion_percentage);
        }
    });
}
```

### Status Update
```javascript
function updateOrderStatus(orderId, newStatus, notes) {
    if (validateStatusTransition(currentStatus, newStatus)) {
        $.ajax({
            url: 'php_action/updateOrderStatus.php',
            method: 'POST',
            data: {
                orderId: orderId,
                status: newStatus,
                notes: notes
            },
            success: function(response) {
                // Handle response
                updateUIElements(newStatus);
                refreshMaterialStatus();
            }
        });
    }
}
```

## Maintenance

### Regular Tasks
1. Clean up completed orders
2. Archive old records
3. Update material costs
4. Review production efficiency
5. Optimize stock levels

### Performance Optimization
1. Index management
2. Query optimization
3. Cache implementation
4. Regular backups
5. Data archiving

## Integration Points

### Inventory System
- Stock level checks
- Material reservations
- Consumption updates
- Reorder triggers

### Quality Control
- Inspection points
- Quality metrics
- Defect tracking
- Compliance checks

### Cost Management
- Material costs
- Labor tracking
- Overhead allocation
- Efficiency metrics

## Troubleshooting Guide

### Common Problems
1. Order Creation Issues
   - Validate product existence
   - Check material availability
   - Verify user permissions

2. Progress Update Failures
   - Check quantity validation
   - Verify material consumption
   - Review status constraints

3. Material Management Issues
   - Validate stock levels
   - Check reservation logic
   - Review consumption records

### Resolution Steps
1. Check error logs
2. Verify data integrity
3. Validate user permissions
4. Review business rules
5. Test status transitions 

## Logging Integration

### Server-Side Logging Methods

The LogManager class provides several methods for logging production order activities:

1. **Production Order Creation**
```php
$logManager->logProductionOrderCreation($orderId, [
    'order_number' => $orderNumber,
    'product_id' => $productId,
    'quantity' => $quantity,
    // ... other order details
]);
```

2. **Production Order Update**
```php
$logManager->logProductionOrderUpdate($orderId, $oldData, $newData);
```

3. **Status Change**
```php
$logManager->logProductionOrderStatusChange($orderId, $oldStatus, $newStatus, $notes);
```

4. **Order Completion**
```php
$logManager->logProductionOrderCompletion($orderId, [
    'completion_date' => $completionDate,
    'final_quantity' => $finalQuantity,
    // ... completion details
]);
```

5. **Material Allocation**
```php
$logManager->logProductionMaterialAllocation($orderId, [
    'material_id' => $materialId,
    'quantity' => $quantity,
    // ... allocation details
]);
```

6. **Progress Update**
```php
$logManager->logProductionProgressUpdate($orderId, [
    'completed_quantity' => $completedQty,
    'remaining_quantity' => $remainingQty,
    // ... progress details
]);
```

### Client-Side Logging

JavaScript helper function for logging client-side activities:

```javascript
// Simple activity logging
logProductionActivity('Viewed order details', orderId);

// Logging with additional data
logProductionActivity('Updated quantity', orderId, {
    old_quantity: oldQty,
    new_quantity: newQty
});
```

### Automatic Logging Events

The system automatically logs the following events:

1. Page Access
```php
$logManager->logActivity(
    "Accessed production orders page",
    "production_orders",
    0
);
```

2. Order Creation
```php
$logManager->logProductionOrderCreation($orderId, $orderData);
```

3. Status Changes
```php
$logManager->logProductionOrderStatusChange($orderId, 'pending', 'in_progress');
```

4. Material Allocations
```php
$logManager->logProductionMaterialAllocation($orderId, $materialData);
```

## Viewing Logs

### Activity Log Query
```sql
SELECT al.*, u.username 
FROM activity_log al
JOIN users u ON al.user_id = u.id
WHERE al.module = 'production_orders'
ORDER BY al.created_at DESC;
```

### Audit Trail Query
```sql
SELECT al.*, u.username 
FROM audit_log al
JOIN users u ON al.user_id = u.id
WHERE al.activity_type LIKE 'production_%'
ORDER BY al.created_at DESC;
```

## Best Practices

1. **Error Handling**
```php
try {
    $logManager->logProductionOrderUpdate($orderId, $oldData, $newData);
} catch (Exception $e) {
    error_log("Logging error: " . $e->getMessage());
}
```

2. **Transaction Management**
```php
try {
    $connect->beginTransaction();
    
    // Perform order update
    // ...
    
    // Log the update
    $logManager->logProductionOrderUpdate($orderId, $oldData, $newData);
    
    $connect->commit();
} catch (Exception $e) {
    $connect->rollback();
    throw $e;
}
```

3. **Data Sanitization**
```php
$sanitizedData = array_diff_key($orderData, array_flip(['sensitive_field']));
$logManager->logAudit(..., $sanitizedData);
```

## Security Considerations

1. All logging actions require authenticated user sessions
2. Sensitive data is filtered before logging
3. IP addresses are recorded for audit logs
4. Logs are write-only and cannot be modified
5. Regular log rotation and archiving is recommended

## Maintenance

1. **Log Rotation**
- Implement log rotation for older records
- Archive logs older than specified period
- Maintain separate archives for audit trails

2. **Performance**
- Index frequently queried columns
- Regular table optimization
- Consider partitioning for large datasets

3. **Monitoring**
- Monitor log table sizes
- Set up alerts for unusual activity
- Regular backup of log data

## Integration Examples

1. **Creating a Production Order**
```php
try {
    // Create order
    $orderId = createProductionOrder($orderData);
    
    // Log creation
    $logManager->logProductionOrderCreation($orderId, $orderData);
    
    // Log material allocation
    $logManager->logProductionMaterialAllocation($orderId, $materialsData);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    throw $e;
}
```

2. **Updating Order Status**
```php
try {
    // Update status
    updateOrderStatus($orderId, $newStatus);
    
    // Log status change
    $logManager->logProductionOrderStatusChange(
        $orderId,
        $oldStatus,
        $newStatus,
        $notes
    );
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    throw $e;
}
``` 