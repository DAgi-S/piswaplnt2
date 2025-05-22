# 📝 Changelog

All notable changes to this project will be documented here.

## [Version 1.0.29] - 2024-04-12

### 🐛 Bug Fixes
- Fixed column name references in stock movements (id -> movement_id)
- Updated SQL queries to use correct column names
- Improved database schema compatibility
- Enhanced error messages for database operations

### 🔄 Database Changes
- Updated queries to use proper column names
- Fixed field order in INSERT statements
- Enhanced parameter binding for prepared statements

## [Version 1.0.28] - 2024-04-12

### 🐛 Bug Fixes
- Fixed reference validation in stock movements
- Added proper reference ID checking for purchases
- Enhanced error messages for invalid references

### 🔄 Database Changes
- Added validation for purchase reference IDs
- Improved reference checking queries

## [Version 1.0.27] - 2024-04-12

### 🐛 Bug Fixes
- Fixed item existence check to use correct status column
- Updated product ID column reference in queries
- Enhanced database compatibility for product checks

### 🔄 Database Changes
- Updated queries to use proper column names (status instead of active)
- Added proper product ID column reference

## [Version 1.0.26] - 2024-04-12

### 🐛 Bug Fixes
- Fixed item type mapping in stock movements (product -> finished_good)
- Added proper validation for warehouse stock item types
- Enhanced error handling in stock movement creation

### 🔄 Database Changes
- Added proper item type validation in warehouse stock updates

## [Version 1.0.25] - 2024-04-12

### 🐛 Bug Fixes
- Fixed stock movement processing error for products
- Added missing product "HP Laptop" to products table
- Implemented stock movement approval system
- Added proper validation for item types in stock movements

### 🔄 Database Changes
- Added stock movement approval tables
- Added new product entry for HP Laptop
- Enhanced stock movement validation

## [Version 1.0.23] - 2024-04-01

### ✨ Features & Improvements
- **Quality Control Module Data Enhancement**:
  - Added sample data to quality_control table (3 records)
  - Added sample data to quality_control_parameters table (3 records)
  - Added sample data to quality_control_results table (9 records)
  - Added sample data to quality_defect_types table (3 records)
  - Added sample data to quality_inspection_points table (3 records)
  - Enhanced visual display of quality control data in the UI

## [Version 1.0.22] - 2024-04-01

### 🛠️ Fixes
- **Quality Control Module Output Buffer Fixes**:
  - Added output buffer control at the beginning of all QC API files
  - Added proper CORS headers to prevent cross-origin issues
  - Enhanced clean output buffer handling before JSON encoding
  - Added early script termination to prevent trailing output
  - Fixed JSON parsing errors by ensuring no BOM markers are present
  - Improved error logging for better troubleshooting
  - Added explicit content type headers for API responses

## [Version 1.0.21] - 2024-03-31

### 🛠️ Fixes
- **Quality Control Module JSON Output Fixes**:
  - Fixed JSON parsing errors by ensuring clean output without extra characters
  - Added output buffer cleaning to prevent malformed JSON
  - Implemented automatic sample data creation when table is empty
  - Added proper error message handling for database operations
  - Enhanced handling of null values in JSON output
  - Added early script termination to prevent trailing output
  - Fixed cross-origin issues with better Content-Type headers

## [Version 1.0.20] - 2024-03-31

### 🛠️ Fixes
- **Quality Control Module Additional Bug Fixes**:
  - Enhanced database table existence checking with case-insensitive comparisons
  - Added detailed error logging and debugging capabilities
  - Fixed table alias inconsistencies across the application
  - Added record count validation for quality control entries
  - Improved JavaScript error handling and reporting
  - Added external libraries fallback for moment.js and toastr
  - Improved DataTable initialization with better error reporting
  - Added debug mode toggle to troubleshoot issues in production

## [Version 1.0.19] - 2024-03-31

### 🛠️ Fixes
- **Quality Control Module Bug Fixes**:
  - Fixed database query JOIN issues in fetchQC.php
  - Updated table aliases for consistency across all SQL queries
  - Corrected production order status values to match database schema
  - Added error logging for troubleshooting
  - Fixed compatibility with existing quality control tables
  - Enhanced error handling with detailed error messages

## [Version 1.0.18] - 2024-03-31

### 🚀 New Features
- **Quality Control Module Implementation**:
  - Added complete quality control tracking system for production orders
  - Implemented QC entry creation with automatic status determination
  - Added reporting of passed, failed, and partially passed inspections
  - Integrated with production order workflow
  - Added defect tracking and analysis
  - Implemented responsive data tables with full CRUD functionality
  - Added validation for quantities (passed + failed = checked)
  - Implemented real-time calculated fields in forms

## [Version 1.0.17] - 2024-03-29

### 🚀 New Features
- **Enhanced Material Usage Report Data Handling**:
  - Added comprehensive debug logging for SQL queries
  - Implemented automatic sample data generation for empty tables
  - Added data validation and fallback mechanisms
  - Improved error handling in database operations
  - Added transaction safety for data operations
  - Implemented query result tracking with row counts

## [Version 1.0.16] - 2024-03-29

### 🔄 Updates
- **Completely Redesigned Material Usage Report Implementation**:
  - Removed all external JavaScript library dependencies
  - Created custom, pure JavaScript implementation for all features
  - Implemented vanilla JS search functionality
  - Added custom table sorting with direction indicators
  - Replaced Chart.js with custom HTML/CSS visual representations
  - Created pure JavaScript Excel export functionality
  - Improved error handling with fallback display options
  - Enhanced browser compatibility across all platforms

## [Version 1.0.15] - 2024-03-29

### 🛠️ Fixes
- Fixed **Material Usage Report JavaScript Issues**:
  - Resolved 'undefined' variable errors in the DataTable initialization
  - Fixed cross-origin object issues with Chart.js integration
  - Implemented sequentially loading JavaScript dependencies
  - Added fallback mechanism for jQuery loading
  - Enhanced error handling for chart initialization
  - Improved DataTable compatibility with older browsers
  - Added data existence checks before chart rendering

## [Version 1.0.14] - 2024-03-28

### 🔄 Updates
- Enhanced **Material Usage Report**:
  - Added visual data representations with charts (pie chart for categories, line chart for trend analysis)
  - Implemented data export functionality with multiple formats (Excel, CSV, PDF)
  - Fixed SQL queries to use correct field names for database compatibility
  - Added interactive data tables with sorting and searching capabilities
  - Improved UI with card-based layout and responsive design
  - Added comprehensive filtering options for better data analysis

## [Version 1.0.13] - 2024-03-28

### 🚀 New Features
- Added **Material Consumption Tracking**:
  - Implemented automatic recording of material consumption when creating production orders
  - Created new `material_consumption` table to track detailed material usage
  - Consumption records include production order reference, material details, and quantities
  - Added transaction safety to ensure data integrity
  - Integrated with existing production order workflow without affecting other functionality

## [Version 1.0.12] - 2024-03-27

### 📝 Documentation
- Added **Enhanced Production Orders System Analysis**:
  - Created comprehensive v2 analysis document with detailed database structure
  - Documented complete production workflow and status progression
  - Added material handling process documentation
  - Included production progress tracking details
  - Documented quality control integration
  - Added information about database triggers and stored procedures
  - Included transaction safety and security considerations
  - Added performance optimization details and future enhancement opportunities

## [Version 1.0.11] - 2024-03-25

### 📝 Documentation
- Added **Production Orders System Analysis**:
  - Created comprehensive analysis of the production orders module
  - Documented security and permission handling
  - Detailed UI components and workflow states
  - Analyzed technical implementation details
  - Identified integration points with other system modules
  - Highlighted potential enhancements and technical debt

## [Version 1.0.9] - 2024-03-22

### 🛠️ Fixes
- Fixed **Sales Order Payment Display**:
  - Corrected payment method display in view modal
  - Fixed undefined payment method in payment history
  - Updated payment method field to show correct values
  - Enhanced payment history display formatting

- Enhanced **Sales Order Print Layout**:
  - Fixed order lookup by order number
  - Added comprehensive payment history section
  - Improved payment information display
  - Added payment processor details
  - Enhanced date and amount formatting

- Fixed **Sales Order API**:
  - Corrected order lookup in printSalesOrder.php
  - Updated SQL query to use order_number instead of id
  - Fixed parameter binding in prepared statements
  - Enhanced error handling for invalid order numbers
  - Improved data validation and sanitization

### 🔄 Updates
- Improved **Payment History System**:
  - Added payment processor tracking
  - Enhanced payment method validation
  - Improved payment history layout
  - Added proper data escaping for security

- Enhanced **API Endpoints**:
  - Optimized database queries for better performance
  - Added comprehensive error logging
  - Improved response formatting
  - Enhanced security with proper parameter validation
  - Added detailed error messages for debugging

### 📝 Technical Details
- Payment History Query:
  ```sql
  SELECT sp.*, COALESCE(a.account_owner, 'System') as processed_by
  FROM sales_payments sp
  LEFT JOIN accounts a ON sp.created_by = a.id
  WHERE sp.sales_order_id = ?
  ORDER BY sp.payment_date ASC
  ```
- Payment Display:
  ```php
  // Proper payment method display
  echo e($payment['payment_method'] ?? 'N/A');
  ```
- Order Lookup Query:
  ```sql
  SELECT so.*, 
         c.company_name, c.phone, c.email, c.address,
         COALESCE(a.account_owner, 'System') as created_by_name
  FROM sales_orders so
  LEFT JOIN clients c ON so.client_id = c.id
  LEFT JOIN accounts a ON so.created_by = a.id
  WHERE so.order_number = ?
  ```

## [Version 1.0.7] - 2024-03-22

### 🛠️ Fixes
- Fixed **Sales Order View Modal**:
  - Resolved DataTable initialization issues
  - Fixed view button functionality
  - Corrected date formatting in modal
  - Fixed amount formatting across all displays

### 🔄 Updates
- Enhanced **DataTable Implementation**:
  - Added responsive table features
  - Improved export functionality (CSV, Excel, PDF)
  - Enhanced mobile display
  - Added proper error handling

### 📝 Technical Details
- DataTable Configuration:
  ```javascript
  var salesTable = $('#salesOrdersTable').DataTable({
      "ajax": {
          "url": "php_action/fetchSalesOrders.php",
          "dataSrc": function(response) {
              if (!response.data) return [];
              return response.data;
          }
      },
      "responsive": true,
      "buttons": ['copy', 'csv', 'excel', 'pdf', 'print']
  });
  ```
- Date Formatting:
  ```javascript
  // Consistent date formatting
  const date = new Date(data);
  return date.toLocaleDateString('en-GB');
  ```

## [Version 1.0.6] - 2024-03-22

### 🛠️ Fixes
- Fixed **Sales Order Date Handling**:
  - Resolved issue with order dates being stored as '0000-00-00'
  - Disabled MySQL strict mode for date handling
  - Implemented direct date insertion with proper format validation
  - Added comprehensive date debugging and logging
  - Fixed order number generation based on order date

### 🔄 Updates
- Enhanced **Date Processing System**:
  - Improved date validation and formatting
  - Added detailed logging for date operations
  - Enhanced error handling for date-related issues
  - Implemented proper date comparison logic

### 📝 Technical Details
- Backend Changes:
  ```php
  // Disable MySQL strict mode
  $connect->query("SET SESSION sql_mode = ''");
  
  // Direct date insertion with validation
  $orderDateFormatted = $orderDate->format('Y-m-d');
  // Use direct query with properly formatted date
  "INSERT INTO sales_orders (..., order_date, ...) 
   VALUES (..., '$orderDateFormatted', ...)"
  ```
- Date Validation:
  ```php
  // Validate and parse order date
  $orderDate = DateTime::createFromFormat('Y-m-d', $input['order_date']);
  if (!$orderDate) {
      throw new Exception('Invalid order date format');
  }
  ```

## [Version 1.0.5] - 2024-03-22

### 🛠️ Fixes
- Enhanced **Sales Reports System**:
  - Fixed date range handling and validation
  - Corrected payment status display formatting
  - Improved amount formatting consistency
  - Fixed client dropdown display issues

### 🔄 Updates
- Improved **Date Handling System**:
  - Added proper date range constraints
  - Enhanced date validation for custom ranges
  - Implemented consistent date format (YYYY-MM-DD)
  - Added automatic date range updates

### ⚡ Optimizations
- Enhanced **Report Generation**:
  - Added dynamic filename generation for exports
  - Improved table footer calculations
  - Enhanced summary card updates
  - Added visual indicators for payment status

### 📝 Technical Details
- Frontend Improvements:
  ```javascript
  // Date range initialization
  $('#startDate, #endDate').attr('max', todayStr);
  $('#startDate').on('change', function() {
      $('#endDate').attr('min', $(this).val());
  });
  ```
- Amount Formatting:
  ```javascript
  function formatAmount(amount) {
      return parseFloat(amount).toLocaleString('en-US', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
      });
  }
  ```

## [Version 1.0.4] - 2024-03-22

### 🛠️ Fixes
- Fixed **Sales Order Date Handling**:
  - Resolved order date validation and formatting issues
  - Corrected order date initialization in frontend forms
  - Fixed order number generation to use selected order date
  - Implemented proper date validation in backend

### 🔄 Updates
- Enhanced **Date Handling System**:
  - Added validation to prevent future dates for orders
  - Improved date format consistency (YYYY-MM-DD)
  - Enhanced delivery date validation relative to order date
  - Added clear error messages for date-related issues

### ⚡ Optimizations
- Improved **Order Date Processing**:
  - Optimized date parsing and validation logic
  - Enhanced error handling for date-related issues
  - Added comprehensive date validation in both frontend and backend
  - Implemented consistent date format checks

### 📝 Technical Details
- Frontend Changes:
  ```javascript
  // Initialize order date with today's date
  $('#order_date')
      .attr('type', 'date')
      .attr('max', todayStr)
      .val(todayStr)
      .prop('required', true);
  ```
- Backend Validation:
  ```php
  // Parse and validate the order date
  $orderDate = DateTime::createFromFormat('Y-m-d', $input['order_date']);
  if (!$orderDate) {
      throw new Exception('Invalid order date format');
  }
  ```

## [Version 1.0.3] - 2024-03-21

### 🛠️ Fixes
- Enhanced **POS Receipt Printing**:
  - Fixed receipt printing functionality with proper data handling
  - Corrected fetch URL path for sale details
  - Improved error handling and validation for receipt data
  - Added better logging for debugging print issues

### 🔄 Updates
- Improved **Receipt Layout and Content**:
  - Added column headers for better readability
  - Enhanced item display with separate columns for quantity, price, and total
  - Improved formatting for totals section
  - Added proper handling of discount and withholding amounts

### ⚡ Optimizations
- Enhanced **Data Processing**:
  - Improved validation for items array
  - Added comprehensive error logging
  - Optimized print window handling
  - Enhanced response data formatting

### 🔒 Security
- Implemented **Better Error Handling**:
  - Added proper error messages for debugging
  - Improved security for unauthorized access
  - Enhanced data validation and sanitization
  - Better session handling

## [Version 1.0.2] - 2024-03-20

### 🛠️ Fixes
- Fixed **POS Sale Creation** issues:
  - Resolved duplicate order number generation errors
  - Fixed table locking issues during sale creation
  - Corrected parameter count mismatch in SQL queries
  - Fixed printReceipt function undefined error

### 🔄 Updates
- Enhanced **POS Transaction System**:
  - Improved order number generation logic
  - Updated table locking mechanism for better concurrency
  - Enhanced error handling and validation
  - Improved client-side print functionality

### ⚡ Optimizations
- Improved **Database Operations**:
  - Implemented proper table locking for multiple tables
  - Optimized transaction handling
  - Enhanced data consistency checks
  - Better error reporting and logging

## [Version 1.0.1] - 2024-03-19

### 🛠️ Fixes
- Fixed **POS receipt printing** functionality:
  - Resolved "data.items is undefined" error in receipt printing
  - Added proper data validation and error handling
  - Implemented robust sale details fetching endpoint
  - Enhanced receipt formatting and display

### 🔄 Updates
- Enhanced **POS receipt system**:
  - Added product codes to receipt items
  - Improved date formatting using actual sale date
  - Better numeric value handling with parseFloat/parseInt
  - Added comprehensive error messages for debugging

### 🚀 New Features
- Created new **Sale Details API Endpoint** (`fetchSaleDetails.php`):
  - Secure data fetching with prepared statements
  - Comprehensive sale information retrieval
  - Detailed item data including product codes
  - Proper error handling and response formatting

### ⚡ Optimizations
- Improved **Receipt Generation**:
  - Optimized print window handling
  - Enhanced receipt styling for thermal printers
  - Better memory management with proper window cleanup
  - Reduced unnecessary data processing

## [Version 1.0.0] - 2024-03-18

### 🚀 New Features
- Implemented **POS System Core Functionality**:
  - Sales order creation and processing
  - Real-time stock management
  - Client management with quick-add feature
  - Payment method handling
  - Receipt generation system

### 🔄 Updates
- Enhanced **User Interface**:
  - Modern responsive design with Tailwind CSS
  - Dynamic product grid with search and filtering
  - Interactive cart management
  - Real-time total calculations
  - Client selection with Select2 integration

### ⚡ Optimizations
- Implemented **Database Structure**:
  - Efficient schema design for sales orders
  - Optimized table relationships
  - Proper indexing for better performance
  - Transaction support for data integrity

### 🛠️ Security
- Added **Security Measures**:
  - Session-based authentication
  - SQL injection prevention
  - XSS protection
  - CSRF protection
  - Input validation and sanitization

## [Version 1.0.8] - 2024-03-22

### 🛠️ Fixes
- Fixed **DataTable Initialization**:
  - Resolved "salesTable is undefined" error
  - Made DataTable instance globally accessible
  - Improved table initialization order
  - Fixed tooltip reinitialization

### 🔄 Updates
- Enhanced **Code Organization**:
  - Added consistent number formatting helper
  - Improved code structure and readability
  - Optimized table rendering performance
  - Enhanced error handling for table operations

### 📝 Technical Details
- DataTable Scope Fix:
  ```javascript
  // Make DataTable globally accessible
  window.salesTable = $('#salesOrdersTable').DataTable({
      // ... configuration ...
  });
  ```
- Number Formatting:
  ```

## [Unreleased]

### Fixed
- Fixed quality control functionality by updating all quality control related PHP files to use PDO instead of mysqli methods
  - Updated fetchQC.php to use PDO query methods
  - Updated createQC.php to use PDO prepared statements
  - Updated removeQC.php to use PDO delete operations
  - Updated editQC.php to use PDO update operations
  - Improved error handling with PDO error information
  - Fixed database connection handling
  - Enhanced input validation for quality control operations

## [1.1.0] - 2024-03-04

### Added
- Added toggle button in sales order details modal to switch between Pending and Completed status
- Added "Mark as Paid" button in sales order details modal to quickly mark orders as fully paid
- Added automatic payment record creation when marking order as paid
- Added visibility controls for status toggle buttons based on order state

### Changed
- Updated sales order view modal to include status management buttons
- Improved payment status handling to support quick status changes
- Enhanced UI feedback for status changes with notifications

### Fixed
- Fixed payment status synchronization in the view modal
- Fixed button visibility states based on order status
- Fixed real-time updates of payment history after status changes

## [1.0.0] - Initial Release

### Features
- Basic sales order management
- Payment processing
- Order status tracking
- Client management
- Product inventory tracking
- Payment history
- Order details view
- Print functionality

## [2024-03-12]
### Added
- New `StockMovementManager` class for improved stock movement handling
  - Transaction-based stock updates with automatic rollback
  - Comprehensive data validation for all movement operations
  - Duplicate movement prevention within time window
  - Real-time stock availability checks
  - Detailed audit logging for all movements
  - Better error handling with descriptive messages
  - Support for multiple item types and warehouses
  - Reference validation for purchases, sales, and production orders

### Changed
- Enhanced stock movement validation logic
- Improved warehouse stock updates with atomic operations
- Better error handling and user feedback

### Security
- Added transaction-based operations to prevent data inconsistency
- Implemented proper input validation and sanitization
- Added audit logging for all stock movements