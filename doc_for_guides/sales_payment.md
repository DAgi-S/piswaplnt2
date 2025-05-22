# Sales Payment Module Documentation

## Overview
The sales payment module provides a comprehensive interface for managing sales payments, payment processing, and tracking payment statuses. It includes features for creating, viewing, and managing payments for sales orders.

## File Structure

```
├── sales_payment.php                # Main sales payment interface
├── custom/js/
│   └── sales_payment.js            # Frontend JavaScript functionality
├── php_action/
│   ├── createSalesPayment.php      # Handle payment creation
│   ├── updateSalesPayment.php      # Handle payment updates
│   ├── fetchSalesPayments.php      # Fetch payment records
│   ├── fetchSalesPaymentDetails.php # Fetch detailed payment information
│   ├── confirmSalesPayment.php     # Handle payment confirmation
│   └── fetchAccountsForSales.php   # Fetch accounts for payment processing
├── assets/
│   └── images/
│       └── payment_proofs/         # Storage for payment proof files
```

## Database Structure

### Sales Payments Table (`sales_payments`)
```sql
CREATE TABLE sales_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sales_order_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Check') NOT NULL,
    status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
    reference_number VARCHAR(50),
    notes TEXT,
    payment_proof VARCHAR(255),
    account_id INT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### Sales Orders Table (`sales_orders`)
```sql
CREATE TABLE sales_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    transaction_id VARCHAR(50),
    client_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    order_date DATE NOT NULL,
    delivery_date DATE,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    withholding_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    balance DECIMAL(10,2) NOT NULL,
    payment_status ENUM('paid', 'partial', 'unpaid') NOT NULL,
    order_status ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### Accounts Table (`accounts`)
```sql
CREATE TABLE accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    account_owner VARCHAR(100) NOT NULL,
    account_platform VARCHAR(100) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    number_of_transactions INT DEFAULT 0,
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Clients Table (`clients`)
```sql
CREATE TABLE clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(100) NOT NULL,
    tin_number VARCHAR(50),
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Activity Log Table (`activity_log`)
```sql
CREATE TABLE activity_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    reference_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Audit Log Table (`audit_log`)
```sql
CREATE TABLE audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    reference_id INT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Sales Status History Table (`sales_status_history`)
```sql
CREATE TABLE sales_status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sales_order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

## Frontend Components

### Payment Interface (sales_payment.php)
- Main interface for managing sales payments
- Features:
  - Payment listing with DataTables
  - Add/Edit payment modal
  - View payment details modal
  - Payment confirmation/rejection
  - Payment proof upload
  - Filter and search functionality
  - Summary cards for totals

### JavaScript Module (sales_payment.js)
```javascript
// Core Functions
- loadSalesPayments()           // Fetch and display payments
- createPayment()              // Create new payment
- updatePayment()              // Update existing payment
- confirmPayment()             // Confirm payment
- rejectPayment()             // Reject payment
- viewPaymentDetails()         // View payment details
- calculateSummary()          // Calculate payment summaries

// Event Handlers
- Payment form submission
- Payment proof upload
- Status updates
- Modal interactions
- Filter application
```

## Backend API Endpoints

### Payment Management APIs
1. Create Payment
   ```
   POST: php_action/createSalesPayment.php
   Payload: {
       order_id: number,
       payment_date: date,
       amount: number,
       payment_method: string,
       reference_number: string,
       payment_proof: file,
       notes: string
   }
   Response: {
       success: boolean,
       messages: string,
       payment_id: number
   }
   ```

2. Update Payment
   ```
   POST: php_action/updateSalesPayment.php
   Payload: {
       payment_id: number,
       payment_date: date,
       amount: number,
       payment_method: string,
       reference_number: string,
       payment_proof: file,
       notes: string
   }
   Response: {
       success: boolean,
       messages: string
   }
   ```

3. Confirm/Reject Payment
   ```
   POST: php_action/confirmSalesPayment.php
   Payload: {
       payment_id: number,
       status: string,
       notes: string
   }
   Response: {
       success: boolean,
       messages: string
   }
   ```

4. Fetch Payments
   ```
   GET: php_action/fetchSalesPayments.php
   Query Parameters: {
       start_date: date,
       end_date: date,
       status: string,
       payment_method: string,
       account_id: number,
       client_id: number
   }
   Response: {
       success: boolean,
       data: Array<{
           payment_id: number,
           order_number: string,
           client_name: string,
           payment_date: date,
           amount: number,
           method: string,
           reference: string,
           status: string
       }>
   }
   ```

## Features

### Payment Management
1. Payment Creation
   - Order selection
   - Payment date setting
   - Amount entry
   - Payment method selection
   - Reference number entry
   - Payment proof upload
   - Notes addition

2. Payment Viewing
   - Payment details
   - Order information
   - Client details
   - Payment proof display
   - Status history
   - Notes display

3. Payment Processing
   - Multiple payment methods
   - Reference tracking
   - Payment confirmation
   - Payment rejection
   - Status updates

4. Payment Filtering
   - Date range filter
   - Status filter
   - Payment method filter
   - Account filter
   - Client filter

### Summary Features
1. Payment Statistics
   - Total payments count
   - Total amount received
   - Pending payments count
   - Pending amount total

2. Payment Status Tracking
   - Pending payments
   - Confirmed payments
   - Rejected payments
   - Payment history

## Security Measures

### Access Control
- Permission-based access control
- Server-side validation
- Client-side validation
- Data sanitization

### Required Permissions
1. View Permissions
   - `view_sales_payments` - View payment list
   - `view_payment_details` - View payment details

2. Action Permissions
   - `create_payment` - Create new payments
   - `edit_payment` - Edit existing payments
   - `confirm_payment` - Confirm payments
   - `reject_payment` - Reject payments
   - `delete_payment` - Delete payments

### Data Validation
```php
// Payment validation
if(empty($orderId) || empty($paymentDate) || $amount <= 0) {
    return false;
}

// File validation
if(!empty($_FILES['payment_proof'])) {
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    if(!in_array($_FILES['payment_proof']['type'], $allowedTypes)) {
        return false;
    }
}
```

## Best Practices

### Payment Processing
1. Payment Creation
   - Validate required fields
   - Check payment amount
   - Verify order balance
   - Upload proof securely

2. Payment Confirmation
   - Verify payment details
   - Check payment proof
   - Update order balance
   - Maintain audit trail

3. Status Updates
   - Document status changes
   - Notify relevant parties
   - Update related records
   - Track modifications

### Data Management
1. Regular backups
2. Data validation
3. Error logging
4. Audit trail maintenance

## Implementation Examples

### Payment Creation
```javascript
function createPayment() {
    const formData = new FormData();
    formData.append('order_id', $('#order_id').val());
    formData.append('payment_date', $('#payment_date').val());
    formData.append('amount', $('#amount').val());
    formData.append('payment_method', $('#payment_method').val());
    formData.append('reference_number', $('#reference_number').val());
    formData.append('payment_proof', $('#payment_proof')[0].files[0]);
    formData.append('notes', $('#notes').val());
    
    $.ajax({
        url: 'php_action/createSalesPayment.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // Handle response
        }
    });
}
```

### Payment Confirmation
```javascript
function confirmPayment(paymentId) {
    const confirmData = {
        payment_id: paymentId,
        status: 'confirmed',
        notes: $('#confirmation_notes').val()
    };
    
    $.ajax({
        url: 'php_action/confirmSalesPayment.php',
        method: 'POST',
        data: confirmData,
        success: function(response) {
            // Handle response
        }
    });
}
```

### Account Loading
```javascript
function loadAccounts() {
    $.ajax({
        url: 'php_action/fetchAccountsForSales.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                // Update filter dropdown
                let filterAccountSelect = $('#account');
                filterAccountSelect.empty();
                filterAccountSelect.append('<option value="">All Accounts</option>');
                
                // Update add payment modal dropdown
                let addPaymentAccountSelect = $('#account_id');
                addPaymentAccountSelect.empty();
                addPaymentAccountSelect.append('<option value="">Select Account</option>');
                
                response.data.forEach(function(account) {
                    let option = $('<option></option>')
                        .attr('value', account.id)
                        .text(account.display_name);
                    
                    filterAccountSelect.append(option.clone());
                    addPaymentAccountSelect.append(option.clone());
                });
            }
        }
    });
}
```

## Troubleshooting

### Common Issues
1. Payment Creation Issues
   - Check required fields
   - Verify file upload
   - Validate amount
   - Check order status

2. Payment Processing Issues
   - Verify payment details
   - Check file types
   - Validate references
   - Check permissions

3. Status Update Problems
   - Verify current status
   - Check permissions
   - Validate transitions
   - Check related records

## Maintenance

### Regular Tasks
1. Payment status review
2. File cleanup
3. Data validation
4. Backup verification
5. Performance optimization

### Database Maintenance
1. Regular backups
2. Index optimization
3. Data cleanup
4. Performance monitoring

## Account Integration
### Account Selection
1. Filter Dropdown
   - Located in the main payment listing page
   - Shows "All Accounts" as default option
   - Used for filtering payment records

2. Payment Modal Dropdowns
   - Add Payment Modal: Shows active accounts for new payments
   - Edit Payment Modal: Shows active accounts for payment updates
   - Format: "{account_owner} - {account_platform} ({currency})"

### Account API Endpoints
1. Fetch Accounts
   ```
   GET: php_action/fetchAccountsForSales.php
   Response: {
       success: boolean,
       data: Array<{
           id: number,
           account_owner: string,
           account_platform: string,
           currency: string,
           display_name: string
       }>,
       count: number
   }
   ```

### Account Status
- Active accounts (status = 1) are available for new payments
- Account status affects visibility in dropdowns
- Currency information is displayed for payment amount context 

## Payment Proof Handling

### File Storage
1. Directory Structure
   - Base Path: `assets/images/payment_proofs/`
   - File Naming: `payment_[unique_id]_[original_filename]`
   - Supported Types: JPEG, PNG, GIF, PDF
   - Maximum Size: 5MB

2. Upload Process
   ```php
   // File validation
   $allowedTypes = array('image/jpeg', 'image/png', 'image/gif', 'application/pdf');
   $maxSize = 5 * 1024 * 1024; // 5MB

   // Directory setup
   $uploadDir = '../assets/images/payment_proofs/';
   if (!file_exists($uploadDir)) {
       mkdir($uploadDir, 0777, true);
   }

   // File storage
   $fileName = 'payment_' . uniqid() . '_' . basename($file['name']);
   $targetPath = $uploadDir . $fileName;
   move_uploaded_file($file['tmp_name'], $targetPath);
   ```

3. File Display
   ```javascript
   // Payment proof display handling
   if (payment.payment_proof) {
       var fileExt = payment.payment_proof.split('.').pop().toLowerCase();
       if (fileExt === 'pdf') {
           // PDF display with viewer
           '<embed src="' + proofPath + '" type="application/pdf" width="100%" height="400px">'
       } else {
           // Image display with responsive design
           '<img src="' + proofPath + '" class="img-responsive">'
       }
   }
   ```

### Security Measures
1. File Validation
   - Type checking against whitelist
   - Size limitation
   - Secure file naming
   - Directory permission management

2. Access Control
   - Files stored outside web root
   - Proper file permissions
   - Type-specific handling
   - Secure path construction

## Payment Details View

### Modal Components
1. Payment Information
   - Payment ID
   - Order Number
   - Client Details
   - Payment Date
   - Amount
   - Method
   - Account
   - Reference Number
   - Status

2. Payment Proof Display
   - Responsive image/PDF viewer
   - Full-size view option
   - Download capability
   - Format-specific controls

3. Status Management
   - Status badge display
   - Confirmation controls
   - Rejection handling
   - Status update tracking

### Implementation
```javascript
// View payment details
function viewPaymentDetails(paymentId) {
    $.ajax({
        url: 'php_action/fetchSalesPaymentDetails.php',
        type: 'POST',
        data: { payment_id: paymentId },
        success: function(response) {
            if (response.success) {
                // Update modal content
                updateModalContent(response.data);
                // Handle payment proof display
                displayPaymentProof(response.data.payment_proof);
                // Update status controls
                updateStatusControls(response.data.status);
            }
        }
    });
}

// Payment proof display
function displayPaymentProof(proofPath) {
    if (proofPath) {
        var fileExt = proofPath.split('.').pop().toLowerCase();
        var container = $('#view_payment_proof');
        
        if (fileExt === 'pdf') {
            container.html(
                '<div class="text-center">' +
                '<embed src="' + proofPath + '" type="application/pdf" width="100%" height="400px">' +
                '<a href="' + proofPath + '" class="btn btn-primary mt-2" target="_blank">' +
                '<i class="fa fa-external-link"></i> Open PDF in New Tab</a>' +
                '</div>'
            );
        } else {
            container.html(
                '<div class="text-center">' +
                '<img src="' + proofPath + '" class="img-responsive" ' +
                'style="max-width: 100%; margin: auto; border: 1px solid #ddd; padding: 5px;">' +
                '<a href="' + proofPath + '" class="btn btn-primary mt-2" target="_blank">' +
                '<i class="fa fa-external-link"></i> View Full Image</a>' +
                '</div>'
            );
        }
    }
} 