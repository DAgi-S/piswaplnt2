# ProductionManager Implementation Guide

## Overview

This document provides instructions for integrating the `ProductionManager` class with the existing production management system. The implementation uses PHP classes instead of stored procedures to handle production-related operations, eliminating the need for special database privileges.

## Integration Steps

### 1. File Structure

The implementation consists of the following files:

- `php_action/classes/ProductionManager.php` - Main class for production operations
- `php_action/test_production_manager.php` - Testing script
- `php_action/update_material_costs.php` - Utility to update material costs

### 2. Integration with Existing Code

#### Production Order Creation

Replace direct database operations in `createProductionOrder.php` with the ProductionManager class:

```php
// Include the ProductionManager class
require_once 'classes/ProductionManager.php';

// Create an instance with the database connection
$productionManager = new ProductionManager($connect);

// Collect form data
$orderData = [
    'order_number' => isset($_POST['order_number']) ? $_POST['order_number'] : 'PO-' . date('Ymd') . '-' . rand(1000, 9999),
    'product_id' => $_POST['product_id'],
    'target_quantity' => $_POST['target_quantity'],
    'start_date' => $_POST['start_date'],
    'expected_completion_date' => $_POST['expected_completion_date'],
    'notes' => $_POST['notes'],
    'created_by' => $_SESSION['userId']
];

// Create the production order
$result = $productionManager->createProductionOrder($orderData);

// Handle the result
if ($result['status']) {
    $response = [
        'success' => true,
        'messages' => 'Production order created successfully',
        'order_id' => $result['order_id'],
        'order_number' => $result['order_number']
    ];
} else {
    $response = [
        'success' => false,
        'messages' => $result['message']
    ];
}

echo json_encode($response);
```

#### Production Status Updates

Replace direct database operations in status update files:

```php
// Include the ProductionManager class
require_once 'classes/ProductionManager.php';

// Create an instance with the database connection
$productionManager = new ProductionManager($connect);

// Get data from request
$orderId = $_POST['order_id'];
$newStatus = $_POST['status'];
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

// Update the order status
$result = $productionManager->updateOrderStatus($orderId, $newStatus, $notes);

// Handle the result
if ($result['status']) {
    $response = [
        'success' => true,
        'messages' => $result['message']
    ];
} else {
    $response = [
        'success' => false,
        'messages' => $result['message']
    ];
}

echo json_encode($response);
```

#### Recording Production Progress

For progress tracking:

```php
// Include the ProductionManager class
require_once 'classes/ProductionManager.php';

// Create an instance with the database connection
$productionManager = new ProductionManager($connect);

// Get data from request
$orderId = $_POST['order_id'];
$quantity = $_POST['quantity'];
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

// Record the progress
$result = $productionManager->recordProductionProgress($orderId, $quantity, $notes);

// Handle the result
if ($result['status']) {
    $response = [
        'success' => true,
        'messages' => $result['message'],
        'data' => [
            'completed' => $result['completed'],
            'target' => $result['target'],
            'new_status' => $result['new_status']
        ]
    ];
} else {
    $response = [
        'success' => false,
        'messages' => $result['message']
    ];
}

echo json_encode($response);
```

#### Getting Order Details

For fetching order details:

```php
// Include the ProductionManager class
require_once 'classes/ProductionManager.php';

// Create an instance with the database connection
$productionManager = new ProductionManager($connect);

// Get data from request
$orderId = $_GET['order_id'];

// Get the order details
$result = $productionManager->getProductionOrderDetails($orderId);

// Handle the result
if ($result['status']) {
    $response = [
        'success' => true,
        'order' => $result['order'],
        'materials' => $result['materials'],
        'progress' => $result['progress']
    ];
} else {
    $response = [
        'success' => false,
        'messages' => $result['message']
    ];
}

echo json_encode($response);
```

### 3. Integration with Frontend

The ProductionManager class returns structured data that can be directly used by your existing frontend code. No changes are needed to the HTML/JavaScript if your existing code already handles JSON responses with `success`, `message`, and data properties.

### 4. Database Compatibility

The ProductionManager class:
- Works with your existing database structure
- Supports both PDO and MySQLi connections
- Makes no changes to table structures
- Uses transactions to ensure data integrity

## Key Methods

The ProductionManager class provides the following methods:

1. `createProductionOrder($orderData)` - Creates a new production order
2. `calculateProductionRequirements($orderNumber, $productId, $targetQuantity)` - Calculates material requirements
3. `updateOrderStatus($orderId, $status, $notes)` - Updates order status
4. `recordProductionProgress($orderId, $quantity, $notes)` - Records production progress
5. `getProductionOrderDetails($orderId)` - Gets full order details

## Testing

Before full integration:

1. Use `test_production_manager.php` to test core functionality
2. Use `update_material_costs.php` to ensure cost calculations work correctly
3. Test with small quantities before using in production

## Deployment Steps

1. **Backup Existing Files**:
   ```
   cp php_action/createProductionOrder.php php_action/createProductionOrder.php.bak
   cp php_action/updateOrderStatus.php php_action/updateOrderStatus.php.bak
   ```

2. **Create Class Directory** (if needed):
   ```
   mkdir -p php_action/classes
   ```

3. **Copy New Files**:
   ```
   cp ProductionManager.php php_action/classes/
   ```

4. **Update Existing Controller Files** one by one to use the ProductionManager class

5. **Test Each Functionality** after updating:
   - Create a test production order
   - Update its status
   - Record progress
   - Complete the order

## Advantages Over Stored Procedures

1. **No Special Privileges**: Works without SUPER or other special MySQL privileges
2. **Better Error Handling**: Detailed PHP exceptions with context
3. **Easier Maintenance**: Central class file for all production logic
4. **Transaction Support**: Ensures data integrity
5. **Portability**: Works across different hosting environments

## Troubleshooting

If issues occur:

1. Check PHP error logs
2. Verify database column names match exactly in SQL queries
3. Test with `test_production_manager.php` to isolate issues
4. Verify cost_per_unit is set for materials
5. Check for transaction conflicts with other operations 