# Sales Module Documentation

## Overview
The sales module provides a comprehensive interface for managing sales orders, payments, and related operations. It includes features for creating, viewing, and managing sales orders, handling payments, and tracking order statuses.

## File Structure

```
├── sales.php                      # Main sales interface
├── custom/js/
│   └── sales.js                  # Frontend JavaScript functionality
├── php_action/
│   ├── createSalesOrder.php      # Handle sales order creation
│   ├── addSalesPayment.php       # Handle payment additions
│   ├── updateSalesOrder.php      # Handle order updates
│   ├── fetchSalesOrders.php      # Fetch sales orders
│   └── fetchSalesPayments.php    # Fetch payment history
```

## Database Structure

### Sales Orders Table (`sales_orders`)
```sql
CREATE TABLE sales_orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    client_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    order_date DATE NOT NULL,
    delivery_date DATE,
    total_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    balance DECIMAL(10,2) NOT NULL,
    payment_status ENUM('paid', 'partial', 'unpaid') NOT NULL,
    order_status ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(client_id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id)
);
```

### Sales Order Items Table (`sales_order_items`)
```sql
CREATE TABLE sales_order_items (
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
    FOREIGN KEY (order_id) REFERENCES sales_orders(order_id),
    FOREIGN KEY (product_id) REFERENCES production_products(product_id)
);
```

### Sales Payments Table (`sales_payments`)
```sql
CREATE TABLE sales_payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Check') NOT NULL,
    reference_number VARCHAR(50),
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES sales_orders(order_id)
);
```

### Clients Table (`clients`)
```sql
CREATE TABLE clients (
    client_id INT PRIMARY KEY AUTO_INCREMENT,
    client_name VARCHAR(100) NOT NULL,
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

### Production Products Table (`production_products`)
```sql
CREATE TABLE production_products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(100) NOT NULL,
    product_code VARCHAR(50) UNIQUE,
    description TEXT,
    category_id INT,
    brand_id INT,
    unit_of_measure VARCHAR(20),
    cost_price DECIMAL(10,2),
    selling_price DECIMAL(10,2),
    min_stock_level INT DEFAULT 0,
    max_stock_level INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(category_id),
    FOREIGN KEY (brand_id) REFERENCES product_brands(brand_id)
);
```

### Product Categories Table (`product_categories`)
```sql
CREATE TABLE product_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) NOT NULL,
    description TEXT,
    parent_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES product_categories(category_id)
);
```

### Product Brands Table (`product_brands`)
```sql
CREATE TABLE product_brands (
    brand_id INT PRIMARY KEY AUTO_INCREMENT,
    brand_name VARCHAR(50) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    module VARCHAR(50) NOT NULL
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

### Sales Interface (sales.php)
- Main interface for managing sales orders
- Features:
  - Sales order listing with DataTables
  - Add/Edit sales order modal
  - View order details modal
  - Payment management
  - Status management
  - Order item management
  - Tax and discount calculations

### JavaScript Module (sales.js)
```javascript
// Core Functions
- loadSalesOrders()              // Fetch and display sales orders
- createSalesOrder()             // Create new sales order
- addOrderItem()                 // Add item to order
- removeOrderItem()              // Remove item from order
- calculateTotals()              // Calculate order totals
- addPayment()                   // Add payment to order
- updateOrderStatus()            // Update order status
- viewOrderDetails()             // View order details

// Event Handlers
- Order form submission
- Item management
- Payment processing
- Status updates
- Modal interactions
```

## Backend API Endpoints

### Sales Management APIs
1. Create Sales Order
   ```
   POST: php_action/createSalesOrder.php
   Payload: {
       client_id: number,
       warehouse_id: number,
       order_date: date,
       delivery_date: date,
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
   POST: php_action/addSalesPayment.php
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
   POST: php_action/updateSalesOrder.php
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

4. Fetch Sales Orders
   ```
   GET: php_action/fetchSalesOrders.php
   Response: {
       success: boolean,
       data: Array<{
           order_id: number,
           order_number: string,
           client_name: string,
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
   - Client selection
   - Warehouse selection
   - Order date setting
   - Delivery date setting
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

## Security Measures

### Access Control
- Permission-based access control
- Server-side validation
- Client-side validation
- Data sanitization

### Data Validation
```php
// Order validation
if(empty($clientId) || empty($warehouseId) || empty($orderDate)) {
    return false;
}

// Payment validation
if($amount <= 0 || empty($paymentMethod)) {
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
   - Check product availability
   - Verify client credit limit
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

### Data Management
1. Regular backups
2. Data validation
3. Error logging
4. Audit trail maintenance

## Implementation Examples

### Order Creation
```javascript
function createSalesOrder() {
    const formData = {
        client_id: $('#client_id').val(),
        warehouse_id: $('#warehouse_id').val(),
        order_date: $('#order_date').val(),
        delivery_date: $('#delivery_date').val(),
        items: getOrderItems(),
        notes: $('#notes').val()
    };
    
    $.ajax({
        url: 'php_action/createSalesOrder.php',
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
        url: 'php_action/addSalesPayment.php',
        method: 'POST',
        data: paymentData,
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
   - Verify product availability
   - Validate client information
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

## Maintenance

### Regular Tasks
1. Order status review
2. Payment reconciliation
3. Data validation
4. Backup verification
5. Performance optimization

### Database Maintenance
1. Regular backups
2. Index optimization
3. Data cleanup
4. Performance monitoring 