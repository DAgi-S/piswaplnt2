# Suppliers Management System Documentation

## Overview
The Suppliers Management System is a comprehensive solution for managing supplier information, including creation, editing, and tracking of supplier records. The system provides functionality for adding new suppliers, managing existing ones, and maintaining supplier relationships.

## File Structure
```
├── manageSuppliers.php          # Main suppliers management interface
├── php_action/
│   ├── createSupplier.php       # Handle supplier creation
│   ├── editSupplier.php         # Handle supplier editing
│   ├── removeSupplier.php       # Handle supplier deactivation
│   ├── fetchSuppliers.php       # Fetch all suppliers
│   └── fetchSelectedSupplier.php # Fetch single supplier details
├── custom/js/
│   └── supplier.js              # Main suppliers management logic
└── includes/
    ├── header.php              # Common header include
    └── footer.php              # Common footer include
```

## Core Components

### 1. Supplier Management Interface
```html
<div class="panel panel-default">
    <div class="panel-heading">
        <div class="page-heading">
            <i class="fa fa-truck"></i> Manage Suppliers
            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addSupplierModal">
                <i class="fa fa-plus"></i> Add Supplier
            </button>
        </div>
    </div>
    <div class="panel-body">
        <table class="table" id="manageSupplierTable">
            <thead>
                <tr>
                    <th>Company Name</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>TIN</th>
                    <th>Status</th>
                    <th>Options</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
```

### 2. Supplier Creation
```javascript
$("#submitSupplierForm").unbind('submit').bind('submit', function() {
    var form = $(this);
    $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: form.serialize(),
        dataType: 'json',
        success:function(response) {
            if(response.success === true) {
                $("#submitSupplierForm")[0].reset();
                manageSupplierTable.ajax.reload(null, false);
                $("#addSupplierModal").modal('hide');
            }
        }
    });
    return false;
});
```

### 3. Supplier Editing
```javascript
function editSupplier(supplierId) {
    if(supplierId) {
        $("#supplierId").val(supplierId);
        $.ajax({
            url: 'php_action/fetchSelectedSupplier.php',
            type: 'post',
            data: {supplierId: supplierId},
            dataType: 'json',
            success:function(response) {
                $("#editCompanyName").val(response.company_name);
                $("#editContactPerson").val(response.contact_person);
                $("#editEmail").val(response.email);
                $("#editPhone").val(response.phone);
                $("#editAddress").val(response.address);
                $("#editActive").val(response.active);
            }
        });
    }
}
```

## Database Structure

### 1. Suppliers Table
```sql
CREATE TABLE `suppliers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `company_name` varchar(255) NOT NULL,
    `contact_person` varchar(255) NOT NULL,
    `email` varchar(100),
    `phone` varchar(50),
    `address` text,
    `tin_number` varchar(50),
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tin_number` (`tin_number`)
);
```

### 2. Supplier Transactions Table
```sql
CREATE TABLE `supplier_transactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `supplier_id` int(11) NOT NULL,
    `transaction_type` enum('purchase','payment','return') NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `reference_no` varchar(50) NOT NULL,
    `transaction_date` datetime NOT NULL,
    `created_by` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `supplier_id` (`supplier_id`),
    KEY `created_by` (`created_by`),
    CONSTRAINT `fk_supplier_transactions_supplier` 
        FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
);
```

### 3. Supplier Documents Table
```sql
CREATE TABLE `supplier_documents` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `supplier_id` int(11) NOT NULL,
    `document_type` varchar(50) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `uploaded_at` datetime NOT NULL,
    `uploaded_by` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `supplier_id` (`supplier_id`),
    CONSTRAINT `fk_supplier_documents_supplier` 
        FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
);
```

## Features

### 1. Supplier Management
- Create new suppliers
- Edit existing suppliers
- Deactivate suppliers
- View supplier details
- Export supplier data

### 2. Data Validation
```javascript
// Client-side validation
function validateForm() {
    var companyName = $("#companyName").val();
    var contactPerson = $("#contactPerson").val();
    var phone = $("#phone").val();
    
    if(companyName === "") {
        $("#companyName").after('<p class="text-danger">Company name is required</p>');
        return false;
    }
    if(contactPerson === "") {
        $("#contactPerson").after('<p class="text-danger">Contact person is required</p>');
        return false;
    }
    if(phone === "") {
        $("#phone").after('<p class="text-danger">Phone number is required</p>');
        return false;
    }
    return true;
}
```

### 3. DataTable Integration
```javascript
manageSupplierTable = $('#manageSupplierTable').DataTable({
    'ajax': 'php_action/fetchSuppliers.php',
    'order': [],
    'pageLength': 10,
    'responsive': true,
    'dom': '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
           '<"row"<"col-sm-12"tr>>' +
           '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    'buttons': ['copy', 'csv', 'print']
});
```

## Security Features

### 1. Input Sanitization
```php
function sanitize($input) {
    global $connect;
    return mysqli_real_escape_string($connect, strip_tags(trim($input)));
}

$companyName = sanitize($_POST['companyName']);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
```

### 2. SQL Injection Prevention
```php
$stmt = $connect->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $supplierId);
$stmt->execute();
```

### 3. Access Control
```php
if (!hasPermission('manage_suppliers')) {
    header('Location: access_denied.php');
    exit();
}
```

## Error Handling

### 1. Server-side Validation
```php
if(empty($_POST['companyName'])) {
    $valid['success'] = false;
    $valid['messages'] = "Company name is required";
    echo json_encode($valid);
    exit();
}
```

### 2. AJAX Error Handling
```javascript
error: function(xhr, status, error) {
    console.error('Error:', error);
    $('.messages').html('<div class="alert alert-danger">' +
        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
        'Error occurred while processing request' +
    '</div>');
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

### 2. Configuration
```php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'stock_management');

// File upload configuration
define('UPLOAD_PATH', 'uploads/suppliers/');
define('ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
```

### 3. Maintenance Tasks
- Regular database optimization
- Log file management
- Document file cleanup
- Security updates

## Styling

### 1. Table Styling
```css
.table {
    font-size: 11px !important;
}

.table > thead > tr > th {
    background-color: #337ab7;
    color: white;
    font-weight: normal;
    vertical-align: middle !important;
    border-bottom: 0 !important;
}

.table-striped > tbody > tr:nth-of-type(odd) {
    background-color: #f9f9f9;
}
```

### 2. Modal Styling
```css
.supplier-modal .modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.supplier-modal .form-group {
    margin-bottom: 15px;
}

.supplier-modal .control-label {
    font-weight: 600;
}
```

### 3. Button Styling
```css
.btn-group {
    display: flex;
    gap: 2px;
}

.action-buttons {
    white-space: nowrap;
}

.label {
    display: inline-block;
    min-width: 60px;
    text-align: center;
}
```

## Troubleshooting

### Common Issues
1. Database Connection Errors
   - Check connection settings
   - Verify database credentials
   - Check server status

2. File Upload Issues
   - Check file permissions
   - Verify upload directory exists
   - Check file size limits

3. DataTable Loading Issues
   - Check AJAX response format
   - Verify column definitions
   - Check for JavaScript errors

### Debug Tools
1. Console logging
2. Error logs
3. Database query logs
4. Network request monitoring 