# Purchase Module Documentation

## Overview
The purchase module provides a comprehensive interface for managing purchase orders, supplier payments, and related operations. It includes features for creating, viewing, and managing purchase orders, handling supplier payments, and tracking order statuses.

## File Structure

```
├── purchase.php                      # Main purchase interface
├── custom/js/
│   └── purchase.js                  # Frontend JavaScript functionality
├── php_action/
│   ├── createPurchaseOrder.php      # Handle purchase order creation
│   ├── addPurchasePayment.php       # Handle payment additions
│   ├── updatePurchaseOrder.php      # Handle order updates
│   ├── fetchPurchaseOrders.php      # Fetch purchase orders
│   └── fetchPurchasePayments.php    # Fetch payment history
```

## Database Structure

### Purchase Orders Table (`purchase_orders`)
```sql
CREATE TABLE purchase_orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    total_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    balance DECIMAL(10,2) NOT NULL,
    payment_status ENUM('paid', 'partial', 'unpaid') NOT NULL,
    order_status ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id)
);
```

### Purchase Order Items Table (`purchase_order_items`)
```sql
CREATE TABLE purchase_order_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 15.00,
    tax_amount DECIMAL(10,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    received_quantity INT DEFAULT 0,
    FOREIGN KEY (order_id) REFERENCES purchase_orders(order_id),
    FOREIGN KEY (product_id) REFERENCES production_products(product_id)
);
```

### Purchase Payments Table (`purchase_payments`)
```sql
CREATE TABLE purchase_payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Check') NOT NULL,
    reference_number VARCHAR(50),
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES purchase_orders(order_id)
);
```

### Suppliers Table (`suppliers`)
```sql
CREATE TABLE suppliers (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    country VARCHAR(50),
    postal_code VARCHAR(20),
    tax_id VARCHAR(50),
    credit_limit DECIMAL(10,2) DEFAULT 0.00,
    payment_terms VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Warehouses Table (`warehouses`)
```sql
CREATE TABLE warehouses (
    warehouse_id INT PRIMARY KEY AUTO_INCREMENT,
    warehouse_name VARCHAR(100) NOT NULL,
    location VARCHAR(255),
    manager_id INT,
    contact_phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(user_id)
);
```


### Users Table (`users`)
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    profile_image VARCHAR(255),
    phone VARCHAR(20),
    language VARCHAR(10) DEFAULT 'en',
    timezone VARCHAR(50) DEFAULT 'UTC',
    dashboard_preferences TEXT,
    account_id INT,
    telegram_chat_id VARCHAR(50),
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id)
);
```

### User Roles Table (`user_roles`)
```sql
CREATE TABLE user_roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Permissions Table (`permissions`)
```sql
CREATE TABLE permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT, NOT NULL
    permission_name VARCHAR(50) NOT NULL UNIQUE, NOT NULL
    description TEXT, NOT NULL
    module VARCHAR(50) NOT NULL
    created_at Timestamp, NOT NULL
    updated_at Timestamp, NOT NULL
);
```

### Role Permissions Table (`role_permissions`)
```sql
CREATE TABLE role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
);
```

## Frontend Components

### Purchase Interface (purchase.php)
- Main interface for managing purchase orders
- Features:
  - Purchase order listing with DataTables
  - Add/Edit purchase order modal
  - View order details modal
  - Payment management
  - Status management
  - Order item management
  - Tax and discount calculations
  - Receipt management

### JavaScript Module (purchase.js)
```javascript
// Core Functions
- loadPurchaseOrders()              // Fetch and display purchase orders
- createPurchaseOrder()             // Create new purchase order
- addOrderItem()                    // Add item to order
- removeOrderItem()                 // Remove item from order
- calculateTotals()                 // Calculate order totals
- addPayment()                      // Add payment to order
- updateOrderStatus()               // Update order status
- viewOrderDetails()                // View order details
- updateReceivedQuantity()          // Update received quantity

// Event Handlers
- Order form submission
- Item management
- Payment processing
- Status updates
- Modal interactions
- Receipt handling
```

## Backend API Endpoints

### Purchase Management APIs
1. Create Purchase Order
   ```
   POST: php_action/createPurchaseOrder.php
   Payload: {
       supplier_id: number,
       warehouse_id: number,
       order_date: date,
       expected_delivery_date: date,
       items: Array<{
           product_id: number,
           quantity: number,
           unit_price: number
       }>,
       notes: string
   }
   Response: {
       success: boolean,
       messages: string,
       order_id: number
   }
   ```

2. Add Payment
   ```
   POST: php_action/addPurchasePayment.php
   Payload: {
       order_id: number,
       payment_date: date,
       amount: number,
       payment_method: string,
       reference_number: string,
       notes: string
   }
   Response: {
       success: boolean,
       messages: string
   }
   ```

3. Update Order Status
   ```
   POST: php_action/updatePurchaseOrder.php
   Payload: {
       order_id: number,
       status: string,
       notes: string
   }
   Response: {
       success: boolean,
       messages: string
   }
   ```

4. Fetch Purchase Orders
   ```
   GET: php_action/fetchPurchaseOrders.php
   Response: {
       success: boolean,
       data: Array<{
           order_id: number,
           order_number: string,
           supplier_name: string,
           order_date: date,
           total_amount: number,
           paid_amount: number,
           balance: number,
           payment_status: string,
           order_status: string
       }>
   }
   ```

## Features

### Order Management
1. Order Creation
   - Supplier selection
   - Warehouse selection
   - Order date setting
   - Expected delivery date setting
   - Item addition/removal
   - Tax calculation
   - Discount application
   - Notes addition

2. Order Viewing
   - Order details
   - Item list
   - Payment history
   - Status tracking
   - Notes display
   - Receipt tracking

3. Payment Processing
   - Multiple payment methods
   - Reference number tracking
   - Payment history
   - Balance calculation
   - Payment status updates

4. Status Management
   - Order status updates
   - Payment status tracking
   - Status change history
   - Notes for status changes
   - Receipt status tracking

### Calculations
1. Order Totals
   - Subtotal calculation
   - Tax calculation (15% VAT)
   - Withholding tax (2%)
   - Discount calculation
   - Final total calculation

2. Payment Tracking
   - Paid amount tracking
   - Balance calculation
   - Payment status updates
   - Payment history

3. Receipt Management
   - Received quantity tracking
   - Partial receipt handling
   - Quality control tracking
   - Return management

## Security Measures

### Access Control
- Permission-based access control
- Server-side validation
- Client-side validation
- Data sanitization

### Data Validation
```php
// Order validation
if(empty($supplierId) || empty($warehouseId) || empty($orderDate)) {
    return false;
}

// Payment validation
if($amount <= 0 || empty($paymentMethod)) {
    return false;
}

// Receipt validation
if($receivedQuantity > $orderedQuantity) {
    return false;
}
```

### SQL Injection Prevention
- Prepared statements
- Parameter binding
- Input sanitization

## Error Handling
```php
try {
    // Database operations
} catch(PDOException $e) {
    // Log error
    // Return user-friendly message
}
```

## Best Practices

### Order Management
1. Order Creation
   - Validate all required fields
   - Check supplier credit limit
   - Verify product availability
   - Calculate accurate totals

2. Payment Processing
   - Verify payment amount
   - Track payment references
   - Update order status
   - Maintain payment history

3. Status Updates
   - Document status changes
   - Notify relevant parties
   - Update related records
   - Maintain audit trail

4. Receipt Management
   - Track received quantities
   - Handle partial receipts
   - Document quality issues
   - Manage returns

### Data Management
1. Regular backups
2. Data validation
3. Error logging
4. Audit trail maintenance

## Implementation Examples

### Order Creation
```javascript
function createPurchaseOrder() {
    const formData = {
        supplier_id: $('#supplier_id').val(),
        warehouse_id: $('#warehouse_id').val(),
        order_date: $('#order_date').val(),
        expected_delivery_date: $('#expected_delivery_date').val(),
        items: getOrderItems(),
        notes: $('#notes').val()
    };
    
    $.ajax({
        url: 'php_action/createPurchaseOrder.php',
        method: 'POST',
        data: formData,
        success: function(response) {
            // Handle response
        }
    });
}
```

### Payment Processing
```javascript
function addPayment() {
    const paymentData = {
        order_id: $('#payment_order_id').val(),
        payment_date: $('#payment_date').val(),
        amount: $('#payment_amount').val(),
        payment_method: $('#payment_method').val(),
        reference_number: $('#reference_number').val(),
        notes: $('#payment_notes').val()
    };
    
    $.ajax({
        url: 'php_action/addPurchasePayment.php',
        method: 'POST',
        data: paymentData,
        success: function(response) {
            // Handle response
        }
    });
}
```

### Receipt Management
```javascript
function updateReceivedQuantity(itemId, quantity) {
    const receiptData = {
        item_id: itemId,
        received_quantity: quantity,
        notes: $('#receipt_notes').val()
    };
    
    $.ajax({
        url: 'php_action/updateReceipt.php',
        method: 'POST',
        data: receiptData,
        success: function(response) {
            // Handle response
        }
    });
}
```

## Troubleshooting

### Common Issues
1. Order Creation Failures
   - Check required fields
   - Verify supplier information
   - Validate product details
   - Check warehouse selection

2. Payment Processing Issues
   - Verify payment amount
   - Check payment method
   - Validate reference numbers
   - Check order status

3. Status Update Problems
   - Verify current status
   - Check permission levels
   - Validate status transitions
   - Check related records

4. Receipt Management Issues
   - Verify received quantities
   - Check quality control status
   - Validate return requests
   - Document issues

## Maintenance

### Regular Tasks
1. Order status review
2. Payment reconciliation
3. Data validation
4. Backup verification
5. Performance optimization
6. Receipt tracking
7. Return management

### Database Maintenance
1. Regular backups
2. Index optimization
3. Data cleanup
4. Performance monitoring 