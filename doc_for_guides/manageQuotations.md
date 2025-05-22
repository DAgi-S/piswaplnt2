# Quotations Management System Documentation

## Overview
The Quotations Management System is a comprehensive solution for creating, managing, and tracking quotations. It provides functionality for creating new quotations, managing existing ones, sending them via email, and generating printable versions.

## File Structure
```
├── manageQuotations.php          # Main quotations management interface
├── quotation.php                 # Create new quotation interface
├── php_action/
│   ├── createQuotation.php       # Handle quotation creation
│   ├── editQuotation.php         # Handle quotation editing
│   ├── removeQuotation.php       # Handle quotation deletion
│   ├── fetchQuotations.php       # Fetch all quotations
│   ├── fetchQuotationDetails.php # Fetch single quotation details
│   ├── printQuotation.php        # Generate printable quotation
│   ├── sendQuotationEmail.php    # Handle email sending
│   └── generateQuotationNumber.php# Generate unique quotation numbers
├── custom/js/
│   ├── manageQuotations.js       # Main quotations management logic
│   ├── quotation.js              # Quotation creation logic
│   └── editQuotation.js          # Quotation editing logic
└── email_templates/
    └── quotation_email.php       # Email template for quotations
```

## Core Components

### 1. Quotation Management Interface
- DataTable-based listing of all quotations
- Search and filter capabilities
- Action buttons for view, edit, email, and delete operations
- Responsive design for various screen sizes

### 2. Quotation Creation
```javascript
// Example of creating a new quotation
function createQuotation() {
    $.ajax({
        url: 'php_action/createQuotation.php',
        type: 'POST',
        data: $('#createQuotationForm').serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                window.location.href = 'manageQuotations.php';
            }
        }
    });
}
```

### 3. Quotation Editing
```javascript
// Example of editing a quotation
function editQuotation(id) {
    $.ajax({
        url: 'php_action/editQuotation.php',
        type: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        dataType: 'json'
    });
}
```

### 4. Email Functionality
```php
// Email configuration
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'mail.lebawi.net';
$mail->SMTPAuth = true;
$mail->Username = 'swapcapital@lebawi.net';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = 465;
```

## Database Structure

### 1. Quotations Table
```sql
CREATE TABLE `quotations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `quotation_number` varchar(20) NOT NULL,
    `client_id` int(11) NOT NULL,
    `sub_total` decimal(10,2) NOT NULL,
    `vat_amount` decimal(10,2) NOT NULL,
    `withholding_amount` decimal(10,2) DEFAULT 0.00,
    `grand_total` decimal(10,2) NOT NULL,
    `note` text,
    `status` int(11) NOT NULL DEFAULT 1,
    `created_by` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `client_id` (`client_id`),
    KEY `created_by` (`created_by`)
);
```

### 2. Quotation Items Table
```sql
CREATE TABLE `quotation_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `quotation_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `description` text,
    `quantity` int(11) NOT NULL,
    `unit_price` decimal(10,2) NOT NULL,
    `total_price` decimal(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `quotation_id` (`quotation_id`),
    KEY `product_id` (`product_id`)
);
```

### 3. Clients Table
```sql
CREATE TABLE `clients` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `company_name` varchar(255) NOT NULL,
    `tin_number` varchar(50),
    `address` text,
    `phone` varchar(50),
    `email` varchar(100),
    `status` int(11) DEFAULT 1,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tin_number` (`tin_number`)
);
```

### 4. Email Logs Table
```sql
CREATE TABLE `email_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `quotation_id` int(11) NOT NULL,
    `sent_to` varchar(255) NOT NULL,
    `subject` varchar(255) NOT NULL,
    `message` text,
    `status` enum('sent','failed') NOT NULL,
    `error_message` text,
    `sent_by` int(11) NOT NULL,
    `sent_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `quotation_id` (`quotation_id`),
    KEY `sent_by` (`sent_by`)
);
```

### 5. Products Table
```sql
CREATE TABLE `products` (
    `product_id` int(11) NOT NULL AUTO_INCREMENT,
    `product_code` varchar(50) NOT NULL,
    `name` varchar(255) NOT NULL,
    `description` text,
    `unit` varchar(20) NOT NULL,
    `selling_price` decimal(10,2) NOT NULL,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`product_id`),
    UNIQUE KEY `product_code` (`product_code`)
);
```

### 6. Permissions Table
```sql
CREATE TABLE `permissions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `description` text,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
);

-- Default permissions for quotation management
INSERT INTO `permissions` (`name`, `description`) VALUES
('view_quotations', 'Can view quotations list and details'),
('create_quotations', 'Can create new quotations'),
('edit_quotations', 'Can edit existing quotations'),
('delete_quotations', 'Can delete quotations'),
('email_quotations', 'Can send quotations via email'),
('print_quotations', 'Can print quotations');
```

### 7. User Permissions Table
```sql
CREATE TABLE `user_permissions` (
    `user_id` int(11) NOT NULL,
    `permission_id` int(11) NOT NULL,
    PRIMARY KEY (`user_id`, `permission_id`),
    KEY `permission_id` (`permission_id`),
    CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
);
```

## Table Relationships

### Quotation Related Relationships
1. **quotations.client_id → clients.id**
   - One-to-many relationship between clients and quotations
   - A client can have multiple quotations
   - Each quotation belongs to one client

2. **quotation_items.quotation_id → quotations.id**
   - One-to-many relationship between quotations and items
   - A quotation can have multiple items
   - Each item belongs to one quotation

3. **quotation_items.product_id → products.product_id**
   - Many-to-one relationship between items and products
   - Multiple quotation items can reference the same product
   - Each item references one product

### Permission Related Relationships
1. **user_permissions.user_id → users.user_id**
   - Many-to-many relationship between users and permissions
   - Users can have multiple permissions
   - Permissions can be assigned to multiple users

2. **user_permissions.permission_id → permissions.id**
   - Links permissions to users through the junction table
   - Enables flexible permission management

### Email Log Relationships
1. **email_logs.quotation_id → quotations.id**
   - One-to-many relationship between quotations and email logs
   - A quotation can have multiple email logs
   - Each email log is associated with one quotation

2. **email_logs.sent_by → users.user_id**
   - Many-to-one relationship between email logs and users
   - Multiple email logs can be created by the same user
   - Each email log is associated with one sender

## Database Indexes and Constraints

### Performance Optimization Indexes
```sql
-- Quotations table indexes
CREATE INDEX idx_quotation_number ON quotations(quotation_number);
CREATE INDEX idx_quotation_date ON quotations(created_at);
CREATE INDEX idx_quotation_status ON quotations(status);

-- Quotation items indexes
CREATE INDEX idx_item_product ON quotation_items(product_id);
CREATE INDEX idx_item_quotation ON quotation_items(quotation_id);

-- Email logs indexes
CREATE INDEX idx_email_date ON email_logs(sent_at);
CREATE INDEX idx_email_status ON email_logs(status);
```

### Foreign Key Constraints
```sql
-- Quotations table constraints
ALTER TABLE quotations
ADD CONSTRAINT fk_quotation_client
FOREIGN KEY (client_id) REFERENCES clients(id),
ADD CONSTRAINT fk_quotation_creator
FOREIGN KEY (created_by) REFERENCES users(user_id);

-- Quotation items constraints
ALTER TABLE quotation_items
ADD CONSTRAINT fk_item_quotation
FOREIGN KEY (quotation_id) REFERENCES quotations(id)
ON DELETE CASCADE,
ADD CONSTRAINT fk_item_product
FOREIGN KEY (product_id) REFERENCES products(product_id);

-- Email logs constraints
ALTER TABLE email_logs
ADD CONSTRAINT fk_email_quotation
FOREIGN KEY (quotation_id) REFERENCES quotations(id)
ON DELETE CASCADE,
ADD CONSTRAINT fk_email_sender
FOREIGN KEY (sent_by) REFERENCES users(user_id);
```

## Extended Features

### 1. Print Functionality
```javascript
function printQuotation(id) {
    // Print preview window configuration
    const printWindow = window.open('php_action/printQuotation.php?id=' + id, '_blank', 'width=800,height=600');
    
    printWindow.onload = function() {
        printWindow.print();
    };
}
```

#### Print Template Structure
```html
<div class="print-header">
    <div class="company-logo">
        <img src="../assets/images/logo.png" alt="Company Logo">
    </div>
    <div class="company-info">
        <h2>LEBAWI NET TRADING PLC</h2>
        <p>TIN: 0072010209</p>
        <p>Contact: +251924067895 | +251901000231</p>
    </div>
</div>

<div class="quotation-details">
    <table class="info-table">
        <tr>
            <th>Quotation #:</th>
            <td><?php echo $quotation['quotation_number']; ?></td>
            <th>Date:</th>
            <td><?php echo date('d/m/Y', strtotime($quotation['created_at'])); ?></td>
        </tr>
    </table>
</div>
```

### 2. Email System

#### Email Configuration
```php
// Email server settings
define('SMTP_HOST', 'mail.lebawi.net');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'swapcapital@lebawi.net');
define('SMTP_PASSWORD', '********');
define('SMTP_FROM_EMAIL', 'swapcapital@lebawi.net');
define('SMTP_FROM_NAME', 'LEBAWI NET TRADING PLC');
```

#### Email Template Structure
```html
<!DOCTYPE html>
<html>
<head>
    <style>
        .email-container { max-width: 600px; margin: 0 auto; }
        .header { background-color: #1976D2; color: white; padding: 20px; }
        .content { padding: 20px; background: #fff; }
        .footer { text-align: center; padding: 20px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h2>LEBAWI NET TRADING PLC</h2>
        </div>
        <div class="content">
            <!-- Dynamic content here -->
        </div>
        <div class="footer">
            <p>This is an automated email. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
```

#### Email Sending Process
1. **Preparation**
```php
// Generate PDF attachment
$dompdf = new Dompdf\Dompdf();
$dompdf->loadHtml($pdfContent);
$dompdf->render();
$pdfOutput = $dompdf->output();
```

2. **Sending**
```php
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = SMTP_HOST;
$mail->SMTPAuth = true;
$mail->Username = SMTP_USERNAME;
$mail->Password = SMTP_PASSWORD;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = SMTP_PORT;

// Add attachment
$mail->addStringAttachment($pdfOutput, $pdfFilename, 'base64', 'application/pdf');
```

3. **Logging**
```php
$sql = "INSERT INTO email_logs (quotation_id, sent_to, subject, status, sent_by, sent_at) 
        VALUES (?, ?, ?, 'sent', ?, NOW())";
```

### 3. Status Management

#### Status Codes
```php
const QUOTATION_STATUS = [
    1 => 'Active',
    2 => 'Pending',
    3 => 'Approved',
    4 => 'Rejected',
    5 => 'Expired',
    6 => 'Converted to Order'
];
```

#### Status Update Process
```javascript
function updateQuotationStatus(id, status) {
    $.ajax({
        url: 'php_action/updateQuotationStatus.php',
        type: 'POST',
        data: {
            quotationId: id,
            status: status
        },
        success: function(response) {
            if(response.success) {
                refreshQuotationTable();
            }
        }
    });
}
```

### 4. Document Generation

#### PDF Generation Process
```php
// Configure PDF options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');

// Load content and render
$dompdf->loadHtml($htmlContent);
$dompdf->render();

// Stream or save
$dompdf->stream("Quotation_" . $quotationNumber . ".pdf", array("Attachment" => false));
```

#### Number Generation
```php
function generateQuotationNumber() {
    $prefix = "QT";
    $year = date('Y');
    $month = date('m');
    $sql = "SELECT MAX(CAST(SUBSTRING(quotation_number, -4) AS UNSIGNED)) as last_num 
            FROM quotations 
            WHERE quotation_number LIKE ?";
    $pattern = $prefix . $year . $month . "%";
    // ... generate sequential number
}
```

## Features

### 1. Quotation Management
- Create new quotations
- Edit existing quotations
- Delete quotations
- View quotation details
- Print quotations
- Email quotations to clients

### 2. Calculations
- Automatic calculation of subtotal
- VAT calculation (15%)
- Optional withholding tax (2%)
- Grand total calculation

### 3. Client Management
- Client selection
- New client creation
- Client information management

### 4. Product Management
- Product selection
- Quantity and price management
- Total calculation per item

## Security Features

### 1. Permission System
```php
if (!hasPermission('view_quotations')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied'
    ]);
    exit();
}
```

### 2. Input Validation
```php
// Validate email
if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Invalid email address');
}
```

### 3. SQL Injection Prevention
```php
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $quotationId);
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
}
```

### 2. Database Transaction Management
```php
try {
    $connect->begin_transaction();
    // ... operations
    $connect->commit();
} catch(Exception $e) {
    $connect->rollback();
    // ... error handling
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
- PHPMailer library
- jQuery DataTables
- Bootstrap 3.x

### 2. Configuration
- Database connection settings
- Email server configuration
- Permission settings
- File paths configuration

### 3. Maintenance Tasks
- Regular database optimization
- Log file management
- Email template updates
- Security updates

## Troubleshooting

### Common Issues
1. Email sending failures
   - Check SMTP settings
   - Verify email credentials
   - Check network connectivity

2. Database errors
   - Check connection settings
   - Verify table structures
   - Check for proper indexing

3. Permission issues
   - Verify user roles
   - Check permission assignments
   - Review access logs

### Debug Tools
1. Console logging
2. Error logs
3. Database query logs
4. Network request monitoring 