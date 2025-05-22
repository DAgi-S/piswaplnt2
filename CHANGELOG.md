# Changelog

## [2024-04-12] - Quality Control Module PDO Update
### Changed
- Updated all quality control related PHP files to use PDO instead of mysqli methods:
  - Converted fetchQC.php to use PDO query methods
  - Updated createQC.php to use PDO prepared statements
  - Modified removeQC.php to use PDO delete operations
  - Enhanced editQC.php with PDO update operations
  - Improved error handling with PDO error information
  - Enhanced database connection handling
  - Added robust input validation for quality control operations

## [Version 1.0.23] - 2024-04-01

## [2024-03-22]
### Added
- Added new Quick Action Button (FAB) to the header with popup menu for common actions:
  - Add Digital Swap shortcut
  - Add New Order shortcut
  - Add Quotation shortcut
  - Add Product shortcut with modal integration
  - Add GPS Letter shortcut
- Enhanced mobile responsiveness for Quick Action Button
- Added smooth animations and transitions for better UX
- Implemented proper event handling for the Quick Action menu

### Fixed
- Fixed Add Product modal trigger from Quick Action menu
- Implemented proper navigation handling for Add Product when accessed from different pages
- Added session storage handling for modal state persistence

### Technical Updates
- Added z-index management for proper modal and menu layering
- Enhanced click event handling and propagation
- Added proper mobile touch event support
- Implemented session storage for cross-page modal triggering

### UI Improvements
- Added floating action button with modern design
- Enhanced popup menu styling with icons and hover effects
- Improved mobile layout and touch targets
- Added consistent styling with existing theme
- Implemented smooth transitions and animations

## [1.1.0] - 2024-04-12

### Added
- New Sales Performance section in Annual Report
  - Monthly sales trends visualization
  - Sales summary metrics (orders, revenue, collection rate)
  - Recent sales table with payment status
  - Sales performance indicators and growth metrics

### Changed
- Moved Generate PDF and Print buttons to top of Annual Report page
- Improved print preview layout and styling
- Enhanced report header with company information
- Updated data fetching mechanism for sales performance metrics

### Removed
- Stock Movement Analysis section from Annual Report
- POS cart floating button from Annual Report view
- Unnecessary elements from print preview

### Fixed
- Print preview formatting and page breaks
- Currency formatting consistency
- Table responsiveness in reports
- Chart rendering in print view

### Technical
- Added new fetchSalesPerformanceData.php for sales metrics
- Implemented prepared statements for secure database queries
- Added dynamic year-over-year growth calculation
- Enhanced error handling in data fetching
- Optimized SQL queries for better performance

## [1.0.0] - 2024-04-11

### Initial Release
- Basic reporting functionality
- Production management system
- Sales and purchase tracking
- Inventory management
- User authentication
- Basic dashboard

## [2024-03-21]
### Fixed
- Removed problematic user menu dropdown HTML from header.php that was causing layout issues
- Dashboard now displays correctly with proper styling and functionality

## [Previous Changes]
### Added
- Created dashboard.php with proper database queries and error handling
- Added custom dashboard styling (custom/css/dashboard.css)
- Implemented statistics cards for products, orders, and purchases
- Added recent purchases and top suppliers tables
- Implemented proper session handling and security checks

### Fixed
- Fixed login system functionality
- Improved database connection handling
- Added proper error reporting
- Enhanced UI/UX with responsive design
- Implemented proper data formatting for currency and numbers

## [1.0.x] - 2024-03-xx

### Changed
- Updated material usage report to use material_consumption table
- Improved material usage report UI with better filtering and export options
- Added detailed consumption tracking with production order references
- Enhanced summary statistics to show material usage metrics
- Added export functionality for PDF and Excel formats

### Added
- Material Usage Report print template with charts, tables, and summary cards
- Chart visualizations for material usage by category
- Monthly trend visualization for material consumption
- Improved print functionality for reports

### Fixed
- Corrected database name in configuration to match existing database
- Fixed data fetching for older MySQL versions

## [2024-04-11]
### Fixed
- Fixed JSON parsing errors in AJAX responses in quality control page
- Resolved 404 not found errors for JavaScript libraries
- Improved error handling for DataTables
- Fixed JOIN queries to use LEFT JOIN for better data reliability
- Updated library paths to use correct directory structure

### Added
- Added enhanced debugging tools for Quality Control page
- Created database connection test script (test_db.php)
- Implemented detailed server-side logging with client display
- Added fetch API test to verify backend communication

### Technical Updates
- Improved error handling in PHP backend with detailed debugging
- Enhanced buffer management to prevent JSON parsing issues
- Added robust null checking for database fields
- Implemented direct fetch testing for API endpoints
- Added proper formatting for debug output in browser

## [2024-04-01]
### Added
- Sample data for quality control testing and development
- Data entries in quality_control_results, quality_defect_types, and quality_inspection_points tables

### Fixed
- Fixed DataTables sorting icons display issues in quality control page
- Improved Select2 initialization with robust error handling and fallbacks
- Enhanced responsive table styling for better mobile experience

### UI Improvements
- Added custom CSS for status labels with appropriate colors (passed/failed/partial)
- Implemented responsive table design for quality control data
- Enhanced debug message styling for better visibility
- Fixed Select2 dropdown styling and initialization
- Improved overall quality control page user experience

## [2024-04-11] - New Telegram Bot Created
- Created new Telegram bot @Ramamanufacturing_bot
- Bot token generated and stored securely
- Initial bot setup completed
- Ready for command configuration and functionality implementation

## [Version 1.0.24] - 2024-04-12

### 🔄 Changed
- **Transaction History Improvements**:
  - Fixed image preview functionality in transaction history
  - Enhanced error handling for image loading
  - Improved user experience with smoother preview transitions

- **Dashboard Analytics Enhancement**:
  - Added interactive chart features
  - Implemented custom date range selection
  - Optimized data loading and visualization
  - Enhanced user interface for better data interpretation

- **Orders Module DataTable and Payment Status Update**:
  - Moved DataTable initialization to separate file: `custom/js/manageOrder.js`
  - Implemented proper instance management to prevent multiple initializations
  - Added conditional loading of DataTable code only when needed
  - Improved payment status handling and display
  - Enhanced error handling for DataTable operations
  - Optimized table rendering performance

## [1.0.25] - 2024-04-12

### Added
- New financial data endpoint (`fetchFinancialData.php`) with comprehensive metrics:
  - Revenue and expense calculations
  - Profit margins and growth metrics
  - Monthly trend data for charts
  - Robust error handling and JSON responses
- Completed comprehensive database structure verification
- Confirmed presence of all required tables for core functionality
- Validated table organization across different modules:
  - Production Management
  - Inventory & Materials
  - Quality Control
  - Sales & Orders
  - Purchase Management
  - User Management & Security
  - Business & Financial

### Fixed
- Corrected table name in stock movement query from `inventory_locations` to `storage_locations`
- Improved error handling in financial data fetching
- Fixed JSON parsing issues in annual report generation

## [2024-04-12] - Production Order Notifications
### Added
- Telegram notifications for new production orders:
  - Integrated Telegram bot API
  - Automatic notifications when orders are created
  - Detailed order information in notifications
  - Emoji-enhanced message formatting

## [2024-04-12] - Production Order Details Enhancement
### Fixed
- Modal backdrop cleanup after print preview:
  - Removed lingering modal backdrop after printing
  - Fixed modal reinitialization
  - Improved print preview cleanup process

### Added
- Print button in Production Order Details popup:
  - Added print button with icon in modal footer
  - Implemented print functionality for order details
  - Clean print layout with proper formatting
  - Added order number and header to printed output
  - Ensured tables and progress bars print correctly

## [2024-04-12] - Production Order System Improvements
### Fixed
- Production order creation issues:
  - Fixed JSON parsing errors in response
  - Added proper product name retrieval
  - Improved error handling for Telegram notifications
  - Fixed undefined variable issues
  - Added robust JSON encoding error handling

## [2024-04-12] - Production Order Notifications Enhancement
### Added
- Telegram notifications for production order completion:
  - Automatic notifications when orders are marked as completed
  - Detailed completion information including quantities and dates
  - Emoji-enhanced message formatting
  - Error handling to prevent notification issues from affecting core functionality

## [2024-04-12] - Production Order System Bug Fixes
### Fixed
- JSON parsing errors in production order status updates:
  - Added missing product name and creator name in SQL queries
  - Fixed JOIN statements to use LEFT JOIN for better data handling
  - Added proper null handling with COALESCE
  - Improved error handling for status changes

## [2024-04-12] - Production Order UI Improvements
### Changed
- Enhanced notification handling:
  - Added auto-close functionality to success notifications
  - Improved toaster notification behavior
  - Added progress bar to notifications
  - Better synchronization of table updates with notifications
  - Added close button to notifications

## [2024-04-12] - Production Order Stock Validation Fix
### Fixed
- Added strict stock validation before creating production orders:
  - Server-side validation to prevent creation of orders with insufficient stock
  - Enhanced client-side validation to block submission when stock is insufficient
  - Detailed error messages showing required vs available quantities
  - Removed ability to override stock validation warnings
  - Improved error handling and user feedback

## [Unreleased]

### Fixed
- Production Order Status Transitions
  - Fixed invalid status transition handling from 'confirmed' to 'inprogress'
  - Improved status validation and error messaging
  - Added proper JSON response formatting for status change requests
  - Enhanced error handling and logging for status transitions
  - Normalized status values to prevent case-sensitivity issues
- Fixed mathematical errors in Sales Order Details popup by returning proper floating-point numbers instead of formatted strings
- Modified `fetchSalesOrderDetails.php` to use `floatval()` instead of `number_format()` for all numerical values

## [2024-04-12] - API Layer Implementation
### Added
- New API endpoints for sales and POS functionality:
  - Sales Orders API:
    - GET /api/v2/sales/orders - List all sales orders
    - GET /api/v2/sales/orders/{id} - Get specific order
    - POST /api/v2/sales/orders - Create new order
    - PUT /api/v2/sales/orders/{id} - Update order
    - DELETE /api/v2/sales/orders/{id} - Delete order
  - POS API:
    - POST /api/v2/pos/checkout - Process POS checkout
    - GET /api/v2/pos/products - List available products
    - GET /api/v2/pos/clients - List clients

### Technical Updates
- Implemented proper error handling and HTTP status codes
- Added input validation and sanitization
- Implemented transaction management for data integrity
- Added pagination and search functionality
- Enhanced security with session validation

## [2024-04-12] - Mobile API Implementation
### Added
- New mobile API endpoints for Flutter/React Native integration:
  - Authentication:
    - POST /api/v2/auth/login - User login with token generation
  - Dashboard:
    - GET /api/v2/dashboard/mobile - Mobile-optimized dashboard data
  - POS:
    - GET /api/v2/pos/mobile - List products for POS
    - POST /api/v2/pos/mobile - Process POS checkout
  - Sales Orders:
    - GET /api/v2/sales/mobile - List and view sales orders
  - Purchase Orders:
    - GET /api/v2/purchase/mobile - List and view purchase orders

### Technical Updates
- Implemented token-based authentication
- Added mobile-optimized data structures
- Enhanced error handling and status codes
- Added pagination support for list endpoints
- Implemented proper data relationships and joins
- Added transaction management for data integrity

## [2024-04-12] - Mobile App Authentication Implementation
### Added
- Created authentication service for mobile app:
  - Implemented login functionality with token management
  - Added secure storage for user credentials
  - Created logout functionality
  - Added token validation and user session management
- Implemented login screen with:
  - Email and password validation
  - Loading state handling
  - Error message display
  - Form validation
- Created home screen with:
  - Navigation drawer
  - Logout functionality
  - Basic layout for main sections
  - Menu items for Dashboard, Inventory, Sales, Purchase, and Reports

### Technical Updates
- Added secure storage for sensitive data
- Implemented proper error handling
- Added navigation between screens
- Enhanced UI with Material Design components
- Added proper state management with Provider

## [2024-04-12] - Mobile API Authentication Endpoints
### Added
- Created authentication API endpoints for mobile app:
  - POST /api/v2/auth/login - Handles user login and token generation
  - POST /api/v2/auth/validate_token - Validates user session tokens
- Implemented secure token-based authentication
- Added proper error handling and response formatting
- Enhanced security with token expiration (24 hours)
- Added CORS headers for mobile app access

### Technical Updates
- Used PDO for secure database operations
- Implemented proper password verification
- Added token storage in database
- Enhanced error handling with appropriate HTTP status codes
- Added proper JSON response formatting

## [2024-04-12] - Mobile Dashboard Implementation
### Added
- Created mobile dashboard API endpoint:
  - GET /api/v2/dashboard/mobile - Provides mobile-optimized dashboard data
  - Includes summary statistics, recent orders, purchases, and low stock alerts
- Implemented dashboard service in mobile app:
  - Added token-based authentication
  - Created data fetching and error handling
  - Implemented proper response parsing

### Technical Updates
- Added CORS headers for mobile app access
- Implemented secure token validation
- Enhanced error handling with appropriate HTTP status codes
- Added proper JSON response formatting
- Optimized database queries for mobile performance

## [2024-04-12] - Mobile Inventory Management Implementation
### Added
- Created mobile inventory API endpoint:
  - GET /api/v2/inventory/mobile - Provides mobile-optimized inventory data
  - Includes pagination, search, and filtering capabilities
  - Supports sorting and category filtering
- Implemented inventory service in mobile app:
  - Added token-based authentication
  - Created data fetching and error handling
  - Implemented proper response parsing
  - Added support for pagination and filtering

### Technical Updates
- Added CORS headers for mobile app access
- Implemented secure token validation
- Enhanced error handling with appropriate HTTP status codes
- Added proper JSON response formatting
- Optimized database queries for mobile performance

## [2024-04-12] - Mobile Sales Module Implementation
### Added
- Created mobile sales API endpoint:
  - GET /api/v2/sales/mobile - Lists and filters sales orders
  - POST /api/v2/sales/mobile - Creates new sales orders
  - Includes pagination, search, and filtering capabilities
  - Supports date range filtering and status filtering
- Implemented sales service in mobile app:
  - Added token-based authentication
  - Created data fetching and error handling
  - Implemented order creation functionality
  - Added support for pagination and filtering

### Technical Updates
- Added CORS headers for mobile app access
- Implemented secure token validation
- Enhanced error handling with appropriate HTTP status codes
- Added proper JSON response formatting
- Optimized database queries for mobile performance
- Implemented transaction management for order creation

## [2024-04-12] - Purchase Service Enhancement
### Added
- Added `getOrderDetails` method to PurchaseService:
  - Implemented order details fetching by ID
  - Added proper error handling and authentication
  - Enhanced API endpoint integration
  - Improved response parsing and validation

## [2024-04-12] - Purchase Order API Implementation
### Added
- Created mobile API endpoints for purchase order management:
  - GET /api/purchase/mobile - List purchase orders with pagination and filtering
  - POST /api/purchase/mobile - Create new purchase orders
  - GET /api/purchase/mobile_detail - Get detailed purchase order information
- Implemented features:
  - Order listing with search, status, and date range filters
  - Order creation with items and supplier details
  - Detailed order view with items and supplier information
  - Proper error handling and validation
  - Transaction management for data integrity

### Technical Updates
- Added proper CORS headers for mobile app access
- Implemented secure token validation
- Enhanced error handling with appropriate HTTP status codes
- Added proper JSON response formatting
- Optimized database queries for mobile performance

## [2024-04-12] - Reports Module Implementation
### Added
- Created mobile API endpoint for reports:
  - GET /api/reports/mobile.php - Provides various report types:
    - Sales Summary with revenue and order metrics
    - Inventory Status with low stock alerts
    - Purchase Summary with order statistics
    - Production Summary with quantity metrics
- Implemented ReportService in mobile app:
  - Added methods for fetching different report types
  - Implemented date range filtering
  - Added proper error handling
  - Enhanced response parsing

### Technical Updates
- Added proper CORS headers for mobile app access
- Implemented secure token validation
- Enhanced error handling with appropriate HTTP status codes
- Added proper JSON response formatting
- Optimized database queries for mobile performance