# Digital Swap System Documentation

## Overview
The Digital Swap System is a comprehensive module for managing digital currency transactions, including deposits and withdrawals. It provides functionality for tracking transactions, managing accounts, and generating reports.

## File Structure
```
digitalswap.php                 # Main digital swap management interface
php_action/
  ├── createDigitalSwap.php    # Handles new transaction creation
  ├── editDigitalswap.php      # Manages transaction updates
  ├── removeDigitalSwap.php    # Handles transaction deletion
  ├── fetchDigitalSwap.php     # Retrieves transaction data
  ├── getDigitalSwapById.php   # Gets specific transaction details
  ├── getDigitalSwapData.php   # Fetches transaction data for display
  ├── getDigitalSwapReport.php # Generates transaction reports
  └── emailDigitalSwap.php     # Handles email notifications
custom/js/
  ├── digitalswap.js          # Core transaction management functionality
  ├── digitalswap-report.js   # Report generation functionality
  └── digitalswap-analytics.js # Analytics and data visualization
```

## Core Components

### 1. Transaction Management Interface
- Main interface for viewing and managing transactions
- DataTable integration for efficient data display
- Action buttons for view, edit, and remove operations

### 2. Transaction Creation
- Form for adding new transactions
- Account selection with platform grouping
- Amount and type management
- Image upload support
- Comment system

### 3. Transaction Processing
- Transaction type tracking (deposit/withdraw)
- Account balance updates
- Image management
- Status tracking

## Implementation Details

### Transaction Table Structure
```javascript
// DataTable initialization
$('#manageDigitalSwapTable').DataTable({
    'ajax': 'php_action/fetchDigitalSwap.php',
    'order': [[1, 'desc']],
    'columns': [
        {"data": "transaction_date"},
        {"data": "type"},
        {"data": "name"},
        {"data": "platform"},
        {"data": "amount"},
        {"data": "image"},
        {"data": "comment"},
        {"data": "action"}
    ]
});
```

### Transaction Processing Functions
```javascript
// View transaction details
function viewDigitalSwap(id) {
    $.ajax({
        url: 'php_action/getDigitalSwapById.php',
        type: 'POST',
        data: {id: id},
        dataType: 'json',
        success: function(response) {
            // Populate modal with transaction details
            // Update UI elements
        }
    });
}

// Remove transaction
function removeDigitalSwap(id) {
    if(confirm('Are you sure you want to remove this transaction?')) {
        $.ajax({
            url: 'php_action/removeDigitalSwap.php',
            type: 'POST',
            data: {id: id},
            dataType: 'json',
            success: function(response) {
                // Handle response
            }
        });
    }
}
```

## Features

### 1. Transaction Management
- Create new transactions
- Edit existing transactions
- View transaction details
- Remove transactions
- Track transaction status

### 2. Account Management
- Account selection
- Platform grouping
- Balance tracking
- Transaction history

### 3. Reporting
- Transaction reports
- Email notifications
- Analytics dashboard
- Data visualization

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
3. Test transaction processing
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
1. Test all transaction operations
2. Verify calculations
3. Check error handling
4. Validate security measures

### Deployment
1. Database migration
2. Configuration setup
3. Security hardening
4. Performance optimization

## Database Structure

### 1. Digital Swap Table
```sql
CREATE TABLE `digitalswap` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `type` varchar(50) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `platform` varchar(255) NOT NULL,
  `platform_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  KEY `type_id` (`type_id`),
  KEY `platform_id` (`platform_id`),
  CONSTRAINT `digitalswap_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `digitalswap_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `transaction_types` (`id`),
  CONSTRAINT `digitalswap_ibfk_3` FOREIGN KEY (`platform_id`) REFERENCES `platforms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Accounts Table
```sql
CREATE TABLE `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_owner` varchar(255) NOT NULL,
  `account_platform` varchar(255) NOT NULL,
  `number_of_transactions` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3. Transaction Types Table
```sql
CREATE TABLE `transaction_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4. Platforms Table
```sql
CREATE TABLE `platforms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Table Relationships
1. `digitalswap` table is the main table storing transaction information
2. `accounts` table stores account information
3. `transaction_types` table defines available transaction types
4. `platforms` table defines available platforms

### Key Fields Description
1. **Digital Swap Table**
   - `id`: Unique identifier for each transaction
   - `account_id`: Reference to the account
   - `transaction_date`: Date of the transaction
   - `type`: Type of transaction (deposit/withdraw)
   - `type_id`: Reference to transaction type
   - `name`: Name associated with the transaction
   - `platform`: Platform name
   - `platform_id`: Reference to platform
   - `amount`: Transaction amount
   - `image`: Image path if any
   - `comment`: Additional comments
   - `status`: Transaction status
   - `created_at`: Creation timestamp
   - `updated_at`: Last update timestamp

2. **Accounts Table**
   - `id`: Unique identifier for each account
   - `account_owner`: Name of the account owner
   - `account_platform`: Platform name
   - `number_of_transactions`: Count of transactions
   - `status`: Account status
   - `created_at`: Creation timestamp
   - `updated_at`: Last update timestamp

3. **Transaction Types Table**
   - `id`: Unique identifier for transaction type
   - `name`: Name of the transaction type
   - `status`: Active status of the type

4. **Platforms Table**
   - `id`: Unique identifier for platform
   - `name`: Name of the platform
   - `status`: Active status of the platform

## Image Storage and Management

### 1. Storage Location
- Images are stored in the `assets/images/digitalswap/` directory
- Each image is saved with a unique filename format: `swap_[timestamp]_[original_name]`
- The directory structure:
```
assets/
  └── images/
      └── digitalswap/
          ├── swap_1234567890_image1.jpg
          ├── swap_1234567891_image2.png
          └── ...
```

### 2. Image Processing
- Images are processed before storage:
  - Filename sanitization to remove special characters
  - Timestamp prefix to ensure uniqueness
  - Original extension is preserved
- Maximum file size limit: 5MB
- Allowed file types: JPG, JPEG, PNG
- Image dimensions are not automatically resized

### 3. Image Management Functions
```javascript
// Image upload handling
function handleImageUpload(file) {
    const formData = new FormData();
    formData.append('image', file);
    
    $.ajax({
        url: 'php_action/uploadDigitalSwapImage.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // Handle successful upload
        }
    });
}

// Image preview
function showFullImage(imageUrl) {
    $('#previewImage').attr('src', imageUrl);
    $('#imagePreviewModal').modal('show');
}
```

### 4. Image Security
- File type validation
- Size limit enforcement
- Secure file naming
- Access control for image directory
- Regular cleanup of unused images

### 5. Image Cleanup
- Images are automatically removed when:
  - A transaction is deleted
  - An image is replaced with a new one
  - The transaction is marked as invalid
- Manual cleanup can be performed through the admin interface

### 6. Image Access
- Images can be accessed through:
  - Transaction details view
  - Full-screen preview modal
  - Report generation
  - Email notifications 

## Email Reporting and Processing

### 1. Email Report Generation
- Reports can be generated for:
  - Specific date ranges
  - Selected accounts
  - Transaction types (deposit/withdraw)
  - Custom filters
- Report formats:
  - HTML format for email
  - PDF format for download
  - CSV format for data analysis

### 2. Email Configuration
```php
// Email settings from emailConfig.php
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@example.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_FROM_EMAIL', 'noreply@example.com');
define('SMTP_FROM_NAME', 'Digital Swap System');
```

### 3. Email Processing Functions
```javascript
// Email report generation
function generateEmailReport(formData) {
    $.ajax({
        url: 'php_action/emailDigitalSwap.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                showSuccessMessage('Report sent successfully');
            } else {
                showErrorMessage(response.message);
            }
        }
    });
}

// Email validation
function validateEmail(email) {
    const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    return emailRegex.test(email);
}
```

### 4. Email Report Content
- Report header with date range and filters
- Summary section with:
  - Total transactions
  - Total deposits
  - Total withdrawals
  - Net balance
- Detailed transaction list
- Account-wise summary
- Platform-wise summary

### 5. Email Template
```html
<!DOCTYPE html>
<html>
<head>
    <title>Digital Swap Report</title>
    <style>
        /* Email styles */
        body { font-family: Arial, sans-serif; }
        .report-header { background-color: #f5f5f5; padding: 15px; }
        .summary-section { margin: 20px 0; }
        .transaction-table { width: 100%; border-collapse: collapse; }
        .transaction-table th, .transaction-table td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h2>Digital Swap Report</h2>
        <p>Date Range: [start_date] to [end_date]</p>
    </div>
    <div class="summary-section">
        <!-- Summary content -->
    </div>
    <table class="transaction-table">
        <!-- Transaction details -->
    </table>
</body>
</html>
```

### 6. Email Processing Flow
1. User selects report parameters
2. System validates input data
3. Report data is fetched from database
4. HTML template is populated with data
5. Email is composed with report as attachment
6. Email is sent using configured SMTP settings
7. Success/failure notification is shown to user

### 7. Error Handling
- Email validation errors
- SMTP connection failures
- Report generation errors
- Attachment size limits
- Rate limiting for multiple emails

### 8. Security Measures
- Email address validation
- SMTP authentication
- Secure connection (TLS/SSL)
- Rate limiting
- IP-based restrictions
- Content sanitization

### 9. Email Report Scheduling
- Daily reports
- Weekly summaries
- Monthly statements
- Custom schedule options
- Automated report generation 