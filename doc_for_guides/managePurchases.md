# Purchases Management System Documentation

## Overview
The Purchases Management System is a comprehensive solution for managing purchase transactions, including creation, editing, and tracking of purchases. The system provides functionality for creating new purchases, managing existing ones, handling payments, and generating reports.

## File Structure
```
├── managePurchases.php          # Main purchases management interface
├── purchase_report.php          # Purchase reporting interface
├── php_action/
│   ├── createPurchase.php       # Handle purchase creation
│   ├── editPurchase.php         # Handle purchase editing
│   ├── removePurchase.php       # Handle purchase deletion
│   ├── fetchPurchases.php       # Fetch all purchases
│   ├── fetchSelectedPurchase.php # Fetch single purchase details
│   ├── updatePaymentStatus.php   # Handle payment status updates
│   ├── getPaymentHistory.php    # Fetch payment history
│   ├── getPurchaseReport.php    # Generate purchase reports
│   └── sendPurchaseReport.php   # Email purchase reports
├── custom/js/
│   ├── purchase.js              # Main purchases management logic
│   └── purchase-edit.js         # Purchase editing logic
└── includes/
    ├── header.php              # Common header include
    └── footer.php              # Common footer include
```

## Core Components

### 1. Purchase Management Interface
```html
<div class="panel panel-default">
    <div class="panel-heading">
        <div class="page-heading">
            <i class="glyphicon glyphicon-shopping-cart"></i> Manage Purchases
            <button class="btn btn-success" data-toggle="modal" data-target="#addPurchaseModal">
                <i class="glyphicon glyphicon-plus"></i> Add Purchase
            </button>
        </div>
    </div>
    <div class="panel-body">
        <table class="table" id="managePurchaseTable">
            <thead>
                <tr>
                    <th>Purchase #</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>Sub Total</th>
                    <th>VAT (15%)</th>
                    <th>WHT (2%)</th>
                    <th>Grand Total</th>
                    <th>Payment</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
```

### 2. Purchase Creation
```javascript
function createPurchase() {
    $.ajax({
        url: 'php_action/createPurchase.php',
        type: 'POST',
        data: $('#purchaseForm').serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $("#purchaseForm")[0].reset();
                manageProductTable.ajax.reload(null, false);
                $("#addPurchaseModal").modal('hide');
            }
        }
    });
}
```

### 3. Purchase Editing
```javascript
function editPurchase(purchaseId) {
    $.ajax({
        url: 'php_action/fetchSelectedPurchase.php',
        type: 'post',
        data: {purchaseId: purchaseId},
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editPurchaseModal .modal-body').html(response.html);
                $('#editPurchaseModal').modal('show');
            }
        }
    });
}
```

### 4. Payment Management
```javascript
function updatePaymentStatus(purchaseId, status) {
    if(status === 'Partial') {
        $('#partialPaymentModal').modal('show');
        $('#purchaseId').val(purchaseId);
        return;
    }

    $.ajax({
        url: 'php_action/updatePaymentStatus.php',
        type: 'POST',
        data: {
            purchaseId: purchaseId,
            status: status
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                manageProductTable.ajax.reload(null, false);
            }
        }
    });
}
```

## Database Structure

### 1. Purchases Table
```sql
CREATE TABLE `purchases` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `purchase_number` varchar(20) NOT NULL,
    `supplier_id` int(11) NOT NULL,
    `warehouse_id` int(11) NOT NULL,
    `sub_total` decimal(10,2) NOT NULL,
    `vat_amount` decimal(10,2) NOT NULL,
    `withholding_tax_enabled` tinyint(1) DEFAULT 0,
    `withholding_amount` decimal(10,2) DEFAULT 0.00,
    `grand_total` decimal(10,2) NOT NULL,
    `payment_status` enum('paid','partial','unpaid') NOT NULL DEFAULT 'unpaid',
    `note` text,
    `created_by` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `purchase_number` (`purchase_number`),
    KEY `supplier_id` (`supplier_id`),
    KEY `warehouse_id` (`warehouse_id`),
    KEY `created_by` (`created_by`)
);
```

### 2. Purchase Items Table
```sql
CREATE TABLE `purchase_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `purchase_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` decimal(10,2) NOT NULL,
    `rate` decimal(10,2) NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `purchase_id` (`purchase_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `fk_purchase_items_purchase` 
        FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_purchase_items_product` 
        FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
);
```

### 3. Purchase Payments Table
```sql
CREATE TABLE `purchase_payments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `purchase_id` int(11) NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `payment_date` date NOT NULL,
    `payment_method` enum('cash','bank_transfer','check') NOT NULL,
    `reference_number` varchar(50),
    `notes` text,
    `created_by` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `purchase_id` (`purchase_id`),
    KEY `created_by` (`created_by`),
    CONSTRAINT `fk_purchase_payments_purchase` 
        FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
);
```

## Features

### 1. Purchase Management
- Create new purchases
- Edit existing purchases
- Delete purchases
- View purchase details
- Print purchases
- Track payment status

### 2. Calculations
```javascript
function calculateTotals() {
    var subTotal = 0;
    
    // Calculate subtotal
    $('#purchaseItems tbody tr').each(function() {
        var quantity = parseFloat($(this).find('.quantity').val()) || 0;
        var rate = parseFloat($(this).find('.rate').val()) || 0;
        subTotal += quantity * rate;
    });
    
    // Calculate VAT (15%)
    var vat = subTotal * 0.15;
    
    // Calculate withholding tax if enabled (2%)
    var withholdingAmount = 0;
    if($('#withholdingTaxEnabled').is(':checked')) {
        withholdingAmount = subTotal * 0.02;
    }
    
    // Calculate grand total
    var grandTotal = subTotal + vat - withholdingAmount;
    
    // Update form fields
    $('#subTotal').val(subTotal.toFixed(2));
    $('#vat').val(vat.toFixed(2));
    $('#withholdingAmount').val(withholdingAmount.toFixed(2));
    $('#grandTotal').val(grandTotal.toFixed(2));
}
```

### 3. Payment Handling
```javascript
function processPaymentUpdate(purchaseId, status, paymentData = null) {
    let data = {
        purchaseId: purchaseId,
        status: status
    };

    if (paymentData) {
        data = {...data, ...paymentData};
    }

    $.ajax({
        url: 'php_action/updatePaymentStatus.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                manageProductTable.ajax.reload(null, false);
            }
        }
    });
}
```

### 4. Reporting
```javascript
function generateReport() {
    $.ajax({
        url: 'php_action/getPurchaseReport.php',
        type: 'POST',
        data: $("#getPurchaseReportForm").serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#previewContent').html(response.html);
            }
        }
    });
}
```

## Security Features

### 1. Permission System
```php
if (!hasPermission('manage_purchases')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied'
    ]);
    exit();
}
```

### 2. Input Validation
```javascript
function validatePurchaseForm() {
    var isValid = true;
    var messages = [];
    
    if (!$('#supplier').val()) {
        messages.push('Please select a supplier');
        isValid = false;
    }
    
    if (!$('#purchaseDate').val()) {
        messages.push('Please select a purchase date');
        isValid = false;
    }
    
    // Validate products
    var hasValidProducts = false;
    $('#purchaseItems tbody tr').each(function() {
        var productId = $(this).find('.product-select').val();
        if (productId) {
            hasValidProducts = true;
            return false;
        }
    });
    
    if (!hasValidProducts) {
        messages.push('Please add at least one product');
        isValid = false;
    }
    
    return isValid;
}
```

### 3. SQL Injection Prevention
```php
$stmt = $connect->prepare("SELECT * FROM purchases WHERE id = ?");
$stmt->bind_param("i", $purchaseId);
$stmt->execute();
```

## Error Handling

### 1. AJAX Error Handling
```javascript
error: function(xhr, status, error) {
    console.error('Error details:', {
        xhr: xhr.responseText,
        status: status,
        error: error
    });
    
    $('#messages').html('<div class="alert alert-danger">' +
        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' +
        'Error occurred while processing request' +
    '</div>');
}
```

### 2. Database Transaction Management
```php
try {
    $connect->begin_transaction();
    
    // Insert purchase header
    $stmt = $connect->prepare("INSERT INTO purchases (...) VALUES (...)");
    $stmt->execute();
    
    $purchaseId = $connect->insert_id;
    
    // Insert purchase items
    foreach($items as $item) {
        $stmt = $connect->prepare("INSERT INTO purchase_items (...) VALUES (...)");
        $stmt->execute();
    }
    
    $connect->commit();
} catch(Exception $e) {
    $connect->rollback();
    throw $e;
}
```

## Best Practices

### 1. Code Organization
- Separation of concerns (PHP, JavaScript, HTML)
- Modular functions
- Clear naming conventions
- Proper error handling

### 2. Security
- Input validation
- SQL injection prevention
- XSS prevention
- CSRF protection

### 3. Performance
- Efficient database queries
- Proper indexing
- Client-side validation
- Server-side validation

## Implementation Notes

### 1. Setup Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- jQuery DataTables
- Bootstrap 3.x
- Select2 for dropdowns
- Moment.js for date handling

### 2. Configuration
```php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'stock_management');

// File paths configuration
define('UPLOAD_PATH', 'uploads/purchases/');
define('REPORT_PATH', 'reports/purchases/');
```

### 3. Maintenance Tasks
- Regular database optimization
- Log file management
- Report file cleanup
- Security updates

## Troubleshooting

### Common Issues
1. Payment Status Updates
   - Check transaction logs
   - Verify payment amounts
   - Check user permissions

2. Report Generation
   - Check date range validity
   - Verify data availability
   - Check file permissions

3. Calculation Issues
   - Verify tax rates
   - Check rounding precision
   - Validate input values

### Debug Tools
1. Console logging
2. Error logs
3. Database query logs
4. Network request monitoring 