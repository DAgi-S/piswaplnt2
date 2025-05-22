# Headers Documentation

## Overview
The headers module provides the core navigation and layout structure for the application. It includes multiple header files for different sections of the system, each with its own specific functionality and access controls. The headers handle user authentication, navigation, and UI components across the application.

## File Structure

```
├── includes/
│   └── header.php                # Main system header
├── production/
│   └── includes/
│       └── header.php           # Production module header
├── guest/
│   └── includes/
│       └── header.php           # Guest portal header
└── gpsBusiness/
    └── includes/
        └── header.php           # GPS Business module header
```
#headers table structure 


CREATE TABLE headers (
    header_id INT AUTO_INCREMENT PRIMARY KEY,
    header_name VARCHAR(255) NOT NULL,
    header_type VARCHAR(50) NOT NULL,
    header_content TEXT NOT NULL,
    status TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);



## Main System Header (includes/header.php)

### Core Features
- User authentication check
- Session management
- Navigation menu
- Quick action buttons
- Notification system
- Responsive design
- Permission-based menu items

### Key Components
1. Authentication
   - Session validation
   - Login check
   - Redirect handling

2. Navigation Menu
   - Dashboard
   - Product Management
   - Invoice Management
   - Accounts
   - GPS Letter
   - Digital Swap
   - GPS Business
   - Settings

3. Quick Actions
   - Add Digital Swap
   - Add New Order
   - Add Quotation
   - Add Product
   - Add GPS Letter

### CSS/JS Dependencies
```html
<!-- Core CSS -->
<link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assests/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="custom/css/custom.css">

<!-- JavaScript -->
<script src="assests/jquery/jquery.min.js"></script>
<script src="assests/bootstrap/js/bootstrap.min.js"></script>
```

### Table and Header Management
```php
// Table Styles
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

.table-striped > tbody > tr:nth-of-type(even) {
    background-color: #ffffff;
}

.table > tbody > tr > td {
    vertical-align: middle;
    padding: 4px 8px;
}

// DataTables Styles
.dataTables_wrapper .dataTables_length, 
.dataTables_wrapper .dataTables_filter, 
.dataTables_wrapper .dataTables_info, 
.dataTables_wrapper .dataTables_processing, 
.dataTables_wrapper .dataTables_paginate {
    font-size: 10px;
}

// Button Styles
.btn {
    padding: 2px 6px;
    font-size: 10px;
}

.btn-group {
    display: flex;
    gap: 2px;
}

// Action Column
.action-buttons {
    white-space: nowrap;
}

// Status Labels
.label {
    font-size: 9px;
    padding: 3px 6px;
}

// Search and Length Menu
.dataTables_length select {
    height: 25px;
    font-size: 10px;
    padding: 2px;
}

.dataTables_filter input {
    height: 25px;
    font-size: 10px;
    padding: 2px 6px;
}

// Pagination
.pagination > li > a {
    padding: 4px 8px;
    font-size: 10px;
}
```

### Table Management Features
1. Responsive Tables
   - Mobile-friendly design
   - Horizontal scrolling
   - Column visibility toggle
   - Row expansion

2. DataTables Integration
   - Server-side processing
   - Client-side sorting
   - Custom filtering
   - Export functionality
   - Print options

3. Table Actions
   - Row selection
   - Bulk actions
   - Inline editing
   - Quick filters
   - Status indicators

4. Performance Optimization
   - Lazy loading
   - Pagination
   - Caching
   - Debounced search

### Header Management Features
1. Dynamic Headers
   - Permission-based visibility
   - Context-aware display
   - User preferences
   - Module-specific headers

2. Header Components
   - Navigation menus
   - Quick actions
   - Notifications
   - User profile
   - Search bar

3. Responsive Behavior
   - Collapsible menus
   - Mobile optimization
   - Touch interactions
   - Adaptive layouts

4. Customization Options
   - Theme selection
   - Layout preferences
   - Component visibility
   - Position settings

## Production Module Header (production/includes/header.php)

### Core Features
- Production-specific navigation
- Raw materials management
- Purchase management
- Sales management
- Production management
- Reports
- Settings

### Key Components
1. Navigation Menu
   - Dashboard
   - Raw Materials
   - Purchase
   - Sales
   - Production
   - Reports
   - Settings

2. Production Management
   - Production Orders
   - Bill of Materials
   - Inventory Tracking
   - Products
   - Quality Control
   - Expense Management

### CSS/JS Dependencies
```html
<!-- Production CSS -->
<link rel="stylesheet" href="../assests/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assests/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="../custom/css/custom.css">

<!-- Production JavaScript -->
<script src="../assests/jquery/jquery.min.js"></script>
<script src="../assests/bootstrap/js/bootstrap.min.js"></script>
```

## Guest Portal Header (guest/includes/header.php)

### Core Features
- Guest user authentication
- Account selection
- Limited access navigation
- Transaction history
- Reports access
- Profile management

### Key Components
1. Authentication
   - Guest session check
   - Account validation
   - Access level verification

2. Navigation Menu
   - Dashboard
   - Reports
     - Transaction History
     - Audit Report
     - Annual Report
     - Category Report

3. Account Management
   - Account selection
   - Account switching
   - Account details display

### CSS/JS Dependencies
```html
<!-- Guest Portal CSS -->
<link rel="stylesheet" href="/pistocklntmarch/assests/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="/pistocklntmarch/assests/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="/pistocklntmarch/custom/css/custom.css">

<!-- Guest Portal JavaScript -->
<script src="/pistocklntmarch/assests/jquery/jquery.min.js"></script>
<script src="/pistocklntmarch/assests/bootstrap/js/bootstrap.min.js"></script>
```

## GPS Business Header (gpsBusiness/includes/header.php)

### Core Features
- GPS Business navigation
- Orders & Payments
- Sales & Expenses
- Business Management
- Reports
- Main system access

### Key Components
1. Navigation Menu
   - Orders & Payments
   - Sales & Expenses
   - GPS Business
   - Business Management
   - Main System

2. Business Management
   - Business Cycles
   - Profit Distribution
   - Business Reports
   - GPS Investors
   - GPS Profit

### CSS/JS Dependencies
```html
<!-- GPS Business CSS -->
<link rel="stylesheet" href="../assests/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assests/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="../custom/css/custom.css">

<!-- GPS Business JavaScript -->
<script src="../assests/jquery/jquery.min.js"></script>
<script src="../assests/bootstrap/js/bootstrap.min.js"></script>
```

## Security Features

### Authentication
- Session validation
- Permission checks
- Access level verification
- Guest user restrictions

### Access Control
- Role-based menu items
- Permission-based navigation
- Module-specific access
- Guest access limitations

## Best Practices

### Header Implementation
1. Session Management
   - Validate sessions
   - Handle timeouts
   - Manage redirects
   - Clear invalid sessions

2. Navigation
   - Group related items
   - Use clear labels
   - Implement dropdowns
   - Show active states

3. Responsive Design
   - Mobile-friendly menus
   - Collapsible navigation
   - Touch-friendly buttons
   - Adaptive layouts

### Performance Optimization
1. Resource Loading
   - Minimize CSS/JS
   - Use CDN where possible
   - Implement caching
   - Optimize images

2. Code Organization
   - Modular structure
   - Clear comments
   - Consistent naming
   - DRY principles

## Troubleshooting

### Common Issues
1. Session Problems
   - Check session start
   - Verify session variables
   - Validate redirects
   - Clear browser cache

2. Navigation Issues
   - Check permission settings
   - Verify menu structure
   - Test responsive design
   - Validate links

3. Performance Problems
   - Check resource loading
   - Monitor network requests
   - Optimize assets
   - Clear browser cache

## Maintenance

### Regular Tasks
1. Security Updates
   - Update dependencies
   - Check vulnerabilities
   - Review permissions
   - Audit access logs

2. Performance Monitoring
   - Check load times
   - Monitor resources
   - Optimize code
   - Update assets

3. Content Updates
   - Review navigation
   - Update links
   - Check permissions
   - Validate functionality 

## Header Management Implementation (header_management.php)

### Overview
The header management system provides a centralized interface for managing all header configurations across different modules of the application. It implements CRUD operations with proper permission controls and validation.

### Permission Structure
```php
// Required Permissions
- manage_headers          // Base permission for accessing header management
- view_headers           // Permission to view header configurations
- create_headers         // Permission to create new headers
- edit_headers          // Permission to modify existing headers
- delete_headers        // Permission to remove headers
```

### Module-Specific Permissions
1. Main System Header
   ```php
   - manage_main_header
   - view_main_header
   - edit_main_header
   ```

2. Production Module Header
   ```php
   - manage_production_header
   - view_production_header
   - edit_production_header
   ```

3. Guest Portal Header
   ```php
   - manage_guest_header
   - view_guest_header
   - edit_guest_header
   ```

4. GPS Business Header
   ```php
   - manage_gps_header
   - view_gps_header
   - edit_gps_header
   ```

### Database Structure
```sql
CREATE TABLE headers (
    header_id INT AUTO_INCREMENT PRIMARY KEY,
    header_name VARCHAR(255) NOT NULL,
    header_type VARCHAR(50) NOT NULL,
    header_content TEXT NOT NULL,
    status TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

CREATE TABLE header_permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT,
    header_type VARCHAR(50),
    can_view TINYINT(1) DEFAULT 0,
    can_edit TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);
```

### Implementation Features

1. CRUD Operations
   ```php
   // Create Header
   function createHeader() {
       // Permission check
       if (!hasPermission('create_headers')) {
           return false;
       }
       // Validation and creation logic
   }

   // Update Header
   function updateHeader() {
       // Permission check
       if (!hasPermission('edit_headers')) {
           return false;
       }
       // Validation and update logic
   }

   // Delete Header
   function deleteHeader() {
       // Permission check
       if (!hasPermission('delete_headers')) {
           return false;
       }
       // Validation and deletion logic
   }
   ```

2. Validation Rules
   ```php
   // Header name validation
   if (empty($headerName) || strlen($headerName) > 255) {
       throw new ValidationException('Invalid header name');
   }

   // Header type validation
   $validTypes = ['main', 'production', 'guest', 'gps'];
   if (!in_array($headerType, $validTypes)) {
       throw new ValidationException('Invalid header type');
   }

   // Content validation
   if (empty($headerContent)) {
       throw new ValidationException('Header content cannot be empty');
   }
   ```

3. Security Measures
   ```php
   // XSS Prevention
   $headerName = htmlspecialchars($headerName, ENT_QUOTES, 'UTF-8');
   $headerContent = htmlspecialchars($headerContent, ENT_QUOTES, 'UTF-8');

   // SQL Injection Prevention
   $stmt = $connect->prepare($sql);
   $stmt->bind_param("sssii", $headerName, $headerType, $headerContent, $status, $createdBy);
   ```

### User Interface Components

1. Header List View
   - Display all headers in a grid layout
   - Show header type, status, and creation info
   - Quick actions for edit and delete
   - Preview of header content

2. Add/Edit Modal
   - Form for header details
   - Type selection dropdown
   - Content editor
   - Status toggle
   - Validation feedback

3. Action Buttons
   ```html
   <button class="btn btn-primary">
       <i class="glyphicon glyphicon-plus"></i> Add New Header
   </button>
   <button class="btn btn-warning">
       <i class="glyphicon glyphicon-edit"></i> Edit
   </button>
   <button class="btn btn-danger">
       <i class="glyphicon glyphicon-trash"></i> Delete
   </button>
   ```

### Integration Points

1. Main System
   ```php
   // includes/header.php
   if (hasPermission('manage_main_header')) {
       // Show header management options
   }
   ```

2. Production Module
   ```php
   // production/includes/header.php
   if (hasPermission('manage_production_header')) {
       // Show production header management
   }
   ```

3. Guest Portal
   ```php
   // guest/includes/header.php
   if (hasPermission('manage_guest_header')) {
       // Show guest header management
   }
   ```

4. GPS Business
   ```php
   // gpsBusiness/includes/header.php
   if (hasPermission('manage_gps_header')) {
       // Show GPS header management
   }
   ```

### Best Practices

1. Permission Management
   - Always check permissions before operations
   - Use granular permission control
   - Log permission changes
   - Regular permission audits

2. Content Management
   - Validate input thoroughly
   - Sanitize output
   - Version control headers
   - Backup before changes

3. Performance
   - Cache header content
   - Optimize database queries
   - Minimize header size
   - Use lazy loading

4. Security
   - Input validation
   - Output encoding
   - CSRF protection
   - SQL injection prevention

### Troubleshooting

1. Permission Issues
   - Check role assignments
   - Verify permission settings
   - Review access logs
   - Test permission inheritance

2. Content Problems
   - Validate content format
   - Check character encoding
   - Verify HTML structure
   - Test responsive behavior

3. Database Issues
   - Check connection settings
   - Verify table structure
   - Monitor query performance
   - Regular maintenance 