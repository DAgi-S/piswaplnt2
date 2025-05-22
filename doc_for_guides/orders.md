# Orders System Documentation

## Overview
The Orders System is a comprehensive module for managing sales orders, including creation, editing, viewing, and processing of orders. It integrates with the product management system and handles various aspects of order processing including payment tracking and status management.

## File Structure
```
orders.php                 # Main orders management interface
php_action/
  ├── fetchOrders.php     # Fetches order data for display
  ├── fetchOrderDetails.php # Retrieves detailed order information
  ├── printOrder.php      # Handles order printing functionality
  ├── removeOrder.php     # Manages order deletion
  └── editOrder.php       # Handles order updates
custom/js/
  ├── order.js           # Core order management functionality
  └── manageOrder.js     # DataTable initialization and management
```

## Core Components

### 1. Order Management Interface
- Main interface for viewing and managing orders
- DataTable integration for efficient data display
- Action buttons for view, edit, print, and remove operations

### 2. Order Creation
- Form for adding new orders
- Product selection with quantity management
- Automatic price calculation
- Client information management
- Payment status tracking

### 3. Order Processing
- Payment status updates
- Order status management
- Invoice generation
- Order tracking

## Implementation Details

### Order Table Structure
```javascript
// DataTable initialization
manageOrderTable = $('#manageOrderTable').DataTable({
    'ajax': 'php_action/fetchOrders.php',
    'order': [[1, 'desc']],
    'columns': [
        {"data": "order_id"},
        {"data": "order_date"},
        {"data": "fsnum"},
        {"data": "client_name"},
        {"data": "client_contact"},
        {"data": "grand_total"},
        {"data": "payment_status"},
        {"data": "order_id"}
    ]
});
```

### Order Processing Functions
```javascript
// View order details
function viewOrder(orderId) {
    $.ajax({
        url: 'php_action/fetchOrderDetails.php',
        type: 'POST',
        data: {orderId: orderId},
        dataType: 'json',
        success: function(response) {
            // Populate modal with order details
            // Update UI elements
        }
    });
}

// Print order
function printOrder(orderId) {
    $.ajax({
        url: 'php_action/printOrder.php',
        type: 'POST',
        data: {orderId: orderId},
        success: function(response) {
            // Open print window
        }
    });
}

// Remove order
function removeOrder(orderId) {
    if(confirm('Are you sure you want to remove this order?')) {
        $.ajax({
            url: 'php_action/removeOrder.php',
            type: 'POST',
            data: {orderId: orderId},
            dataType: 'json',
            success: function(response) {
                // Handle response
            }
        });
    }
}
```

## Features

### 1. Order Management
- Create new orders
- Edit existing orders
- View order details
- Print orders
- Remove orders
- Track order status

### 2. Payment Processing
- Payment status tracking
- Payment type management
- Amount calculations
- Due amount tracking

### 3. Data Management
- Order history
- Client information
- Product details
- Payment records

## Error Handling
- Form validation
- Data integrity checks
- Error messages display
- Transaction rollback

## Best Practices

### 1. Code Organization
- Modular JavaScript functions
- Clear separation of concerns
- Consistent naming conventions
- Proper error handling

### 2. Performance
- Efficient database queries
- Optimized DataTable usage
- Proper resource management
- Caching where appropriate

### 3. Security
- Input validation
- XSS prevention
- CSRF protection
- Access control

## Maintenance

### Regular Tasks
1. Database optimization
2. Performance monitoring
3. Error log review
4. Security updates

### Troubleshooting
1. Check error logs
2. Verify database connections
3. Test order processing
4. Validate calculations

## Security Considerations

### 1. Data Security
- Secure data transmission
- Input sanitization
- Output escaping
- Access control

### 2. Authentication
- Session management
- Role-based access
- Permission checks
- Token validation

### 3. Network Security
- HTTPS enforcement
- API security
- Rate limiting
- IP filtering

## Implementation Notes

### Development
1. Use proper error handling
2. Implement input validation
3. Follow security best practices
4. Maintain code documentation

### Testing
1. Test all order operations
2. Verify calculations
3. Check error handling
4. Validate security measures

### Deployment
1. Database migration
2. Configuration setup
3. Security hardening
4. Performance optimization

## Database Structure

### 1. Orders Table
```sql
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_date` date NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `client_contact` varchar(255) NOT NULL,
  `client_tin` varchar(255) DEFAULT NULL,
  `sub_total` decimal(10,2) NOT NULL,
  `vat` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `grand_total` decimal(10,2) NOT NULL,
  `paid` decimal(10,2) DEFAULT NULL,
  `due` decimal(10,2) DEFAULT NULL,
  `payment_type` int(11) NOT NULL,
  `payment_status` int(11) NOT NULL,
  `order_status` int(11) NOT NULL DEFAULT 1,
  `fsnum` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Order Items Table
```sql
CREATE TABLE `order_item` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3. Payment Types Table
```sql
CREATE TABLE `payment_type` (
  `payment_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_type_name` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`payment_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4. Payment Status Table
```sql
CREATE TABLE `payment_status` (
  `payment_status_id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_status_name` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`payment_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Table Relationships
1. `orders` table is the main table storing order information
2. `order_item` table stores individual items for each order
3. `payment_type` table defines available payment methods
4. `payment_status` table defines order payment statuses

### Key Fields Description
1. **Orders Table**
   - `order_id`: Unique identifier for each order
   - `order_date`: Date when the order was created
   - `client_name`: Name of the client
   - `client_contact`: Contact information of the client
   - `client_tin`: Tax Identification Number of the client
   - `sub_total`: Total before taxes and discounts
   - `vat`: Value Added Tax amount
   - `total_amount`: Total including VAT
   - `discount`: Any discount applied
   - `grand_total`: Final amount after all calculations
   - `paid`: Amount paid by the client
   - `due`: Remaining amount to be paid
   - `payment_type`: Reference to payment method
   - `payment_status`: Current payment status
   - `order_status`: Status of the order (active/inactive)
   - `fsnum`: Financial Statement Number

2. **Order Items Table**
   - `order_item_id`: Unique identifier for each order item
   - `order_id`: Reference to the parent order
   - `product_id`: Reference to the product
   - `quantity`: Number of items ordered
   - `rate`: Price per unit
   - `total`: Total amount for the item

3. **Payment Types Table**
   - `payment_type_id`: Unique identifier for payment type
   - `payment_type_name`: Name of the payment method
   - `status`: Active status of the payment type

4. **Payment Status Table**
   - `payment_status_id`: Unique identifier for payment status
   - `payment_status_name`: Name of the payment status
   - `status`: Active status of the payment status 