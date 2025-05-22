# File Changes Log

## 2025 - 5 - 1
- production/php_action/createSalesOrder.php (Fixed issue with stock reduction not working on hosted cPanel - updated table reference and error handling)

## 2025 - 4 - 30
- production/php_action/core.php (Fixed mysqli_stmt fetch method compatibility issue in hasPermission function)

## 2025 - 4 - 25
- production/php_action/updateSalesPayment.php (Enhanced payment proof handling with better validation and file management)
- production/php_action/updateSalesPayment.php (Fixed database connection, improved error handling and input validation)
- production/php_action/updateSalesPayment.php (Fixed file upload path and improved database queries)
- production/account_payments.php (Enhanced financial summary with VAT and withholding tax cards, improved card design)
- production/account_payments.php (Updated purchase transactions status column to show Active/Inactive instead of 1/0)
- production/account_payments.php (Added company information to print header from company_settings table)

## 2025 - 4 - 24
- production/php_action/editPurchase.php (Fixed stock movement handling and current_stock updates during purchase updates)
- production/php_action/updatePurchasePayment.php (Added payment proof upload and improved payment date validation)
- production/purchase.php (Added payment proof upload field and client-side validations)
- production/php_action/fetchPurchasePayments.php (Fixed payment proof display in payment history)
- production/purchase.php (Fixed payment proof preview in payment history modal)

## 2024 - 3 - 1
- production/purchase.php - Updated payment proof display handling in payment history 

## 2025-04-25
- production/purchase.php - Updated payment history modal to include proof column and preview functionality
- production/php_action/fetchPurchasePayments.php - Added payment proof handling and permission checks
- production/purchase_payments.php - Created new page for viewing all purchase payments
- production/php_action/fetchAllPurchasePayments.php - Created new endpoint for fetching all purchase payments with filtering 

## March 19, 2024
- production/custom/js/sales.js 
- includes/print_settings.php
- setup_tables.php

## 2024-03-24
- production/settings.php (Updated system-based permissions to include configuration, backup, database, email, maintenance and security permissions)
- production/products.php (Updated product-based permissions to include attributes, categories, brands, and inventory management)
- production/production_orders.php (Updated production-based permissions to include quality, schedule, waste and reporting permissions)
- production/php_action/createSalesPayment.php (Updated payment proof upload directory path)
- production/sales_reports.php (Updated order-based permissions to include additional checks)

## 2024-03-21
- production/role_management.php (Added order.report.export permission check)
- production/custom/js/role_management.js (Added order.report.export to Invoice & Order permission group)
- production/sales_reports.php (Added order-based permission system and access control)
- production/sales.php (Added order-based permission system and access control)
- production/purchase.php (Updated permissions to include purchase.payment.edit)
- production/settings.php - Updated permission checks and admin role validation
- production/settings.php - Updated settings access control to be more flexible while maintaining security
- production/settings.php - Added granular settings permissions (view/edit) and improved access control system 
- production/account_payments.php - Added VAT and withholding tax columns to sales and purchases tables
- telegram_bot_settings.php - Updated permission structure with new API/Telegram permissions
- php_action/telegram_bot_management.php - Updated permission system with granular controls and admin access
- production/settings.php (Fixed Unit management modals and AJAX functionality)
- production/php_action/fetchSelectedUnit.php (Created dedicated file for fetching unit data for editing)
- production/php_action/deleteUnit.php (Created new file for unit deletion)
- production/php_action/editUnit.php (Fixed unit editing functionality and added proper error handling)
- production/php_action/deleteUnit.php (Improved unit deletion with better validation and error handling)
- php_action/updatePrintTemplate.php
- orders.php - Updated permission system with granular controls for orders, payments, shipping, and reports
- sql/order_permissions.sql - Added new order-related permissions and role mappings
- php_action/createOrder.php - Updated permission checks and error handling
- php_action/fetchOrder.php - Added permission validation and improved error responses
- custom/js/order.js - Enhanced error handling for permission-related issues

## 2024-03-01
- production/php_action/classes/RawMaterialManager.php (New file)
- production/php_action/consumeProductionMaterial.php (Updated)
- production/php_action/updateProductionProgress.php (Updated)
- production/php_action/fetchRawMaterialHistory.php (Updated stock movement history to show detailed production order information)
- production/account_payments.php (Created: 2024-03-01)
- guest/includes/db_connect.php (Removed BASEPATH check, fixed database name)
- guest/php_action/guestLogin.php (Updated)
- guest/dashboard.php (Added proper session handling and security checks)
- guest/includes/header.php (Removed duplicate session handling, fixed paths)
- guest/php_action/guestLogin.php (Added error reporting and improved session management)
- guest/php_action/guestLogin.php
- guest/logs/ (directory created)
- warranty/create_warranty_table.sql - Created new file for warranty certificate table structure
- warranty/index.php - Updated file structure and authentication flow
- warranty/warranty_functions.php - Created functions file for warranty operations
- warranty/generate_certificate.php - Created PDF generation handler
- warranty/get_client_info.php - Created AJAX handler for client information
- sql/warranty_permissions.sql - Added warranty certificate permissions
- includes/sidebar.php - Added warranty certificate menu item

## 2024-03-19
- production/js/stock_movements.js (Created)
- production/stock_movements.php (Created)
- production/php_action/fetchRawMaterialHistory.php (Modified)
- production/php_action/createUnit.php
- production/php_action/editUnit.php
- manage_guests.php (Updated permission system to use granular permissions instead of admin-only access)
- manage_guests.php (Updated asset includes to use CDN links for DataTables and Select2)
- php_action/fetchGuests.php (Updated permission checks to use granular permission system)
- php_action/getGuestInfo.php (Updated permission checks to use granular permission system)
- php_action/editGuest.php (Added granular permission checks)
- php_action/deleteGuest.php (Updated permission checks to use granular permission system)
- php_action/createGuest.php (Added granular permission checks)
- manage_guests.php (Fixed delete guest functionality to pass correct parameter)
- php_action/deleteGuest.php (Fixed delete query to use correct table name and column)
- manage_guests.php (Improved table layout and styling to be more space-optimized)
- manage_guests.php (Enhanced DataTable configuration with responsive design and better UI)

## March 21, 2024
- production/php_action/fetchRawMaterialHistory.php (Updated stock movement history to include production order details)

## March 1, 2024
- production/settings.php
  - Completely restructured permission handling system
  - Added comprehensive permission checks for all settings sections
  - Added separate view/manage permissions
  - Improved admin access checks
  - Added detailed debug logging
  - Updated UI permission checks for all tabs and modals
- production/settings.php (Fixed permissions for category management to properly handle admin and settings access)
- production/settings.php (Added granular permissions for settings module tabs)
- Added new permissions:
  - settings.categories.manage
  - settings.brands.manage
  - settings.units.manage
  - settings.tax.manage
  - settings.currency.manage
  - settings.company.manage
- production/php_action/core.php
  - Enhanced hasPermission() function with special handling for settings access
  - Added master permission checks for admin and settings access
  - Added debugPermissions() function for troubleshooting
  - Improved permission validation logic
  - Added comprehensive permission checks for settings module 

## 2024-06-09
  - production/php_action/editUnit.php  change date (2024-06-09)
  - production/php_action/editCategory.php  change date (2024-06-09)
  - production/php_action/editTax.php  change date (2024-06-09)
  - production/settings.php  change date (2024-06-09)
- production/php_action/core.php 2024-06-09
- production/php_action/createUnit.php 2024-06-09
- production/php_action/editUnit.php 2024-06-09
- production/php_action/deleteUnit.php 2024-06-09
- production/custom/js/settings.js 2024-06-09
- production/php_action/createTax.php 2024-06-09
- production/php_action/editTax.php 2024-06-09
- production/php_action/deleteTax.php 2024-06-09
- production/custom/js/settings.js 2024-06-09
- production/php_action/fetchCurrencySettings.php 2024-06-09
- production/php_action/updateCurrencySettings.php 2024-06-09
- production/custom/js/settings.js 2024-06-09

## 2024-04-28
- include/print_settings.php (2024-04-28)
- production/print_settings_management.php (2024-04-28)
- sql/print_settings.sql (2024-04-28)

## 2024-03-19
- includes/print_settings.php (Moved from production/includes and updated PDO usage)
- print_settings_management.php (Moved from production/ and updated paths and UI)
- print_preview.php (Moved from production/ and added security improvements)
- php_action/config.php (Fixed constant redefinition issues)
- production/php_action/core.php (Updated database functions to use PDO instead of mysqli)

## 2024-03-21

### Updated Files
- `includes/print_settings.php` - Enhanced save_print_settings function with validation and proper timestamp handling
- `print_settings_management.php` - Improved form validation, error handling, and user experience 

## April 28, 2024
- print_settings_management.php (Fixed form submission handling to prevent headers already sent error)
- print_settings_management.php (Updated layout, removed preview tab, improved UI organization)
- includes/db_connect.php (Updated database connection path and error handling)
- includes/config.php (Created new configuration file)
- includes/print_settings.php (Fixed database connection dependency)

## March 22, 2024
- print_settings_management.php
- php_action/createPrintTemplate.php 

## 2024-06-10
- orders.php 2024-06-10 

## 2024-07-09
- check_stock_status.php
- check_sales_stock_deduction.php
- warehouse_stock_viewer.php

## 2024-03-01

### Added
- `custom/css/sidebar.css` - New sidebar styling
- `custom/js/sidebar.js` - New sidebar functionality
- `includes/sidebar.php` - New unified sidebar navigation combining main system, production system, and GPS business system 

## 2024-03-02
- `includes/header.php` - Enhanced header navigation with improved organization, mobile responsiveness, and modern styling

## 2024-03-03
- `portal.php` - Added new quick access portal page with stylized buttons for easy system navigation

## 2024-06-10
- orders.php 2024-06-10 

## 2024-07-09
- check_stock_status.php
- check_sales_stock_deduction.php
- warehouse_stock_viewer.php 

## 2024-07-11
- index.html (Created futuristic landing page for Lebawi Net Trading PLC)
- index.html (Added interactive Star Collector game for visitor engagement)
- index.html (Added Number Guessing game with tabbed interface for multiple game options) 

## 2024-03-01
- custom/js/digitalswap.js - Fixed header dropdown functionality by modifying dropdown handling code to prevent conflicts with Bootstrap dropdowns (2024-03-01) 

## 2024-03-19
- manage_guests.php (Updated permission system to use granular permissions instead of admin-only access) 

## 2024-03-21
- guest/php_action/core.php (Removed duplicate validateGuestLogin function)
- guest/php_action/functions.php
- guest/php_action/guestLogin.php
- guest/index.php
- guest/setup_database.php
- guest/setup_guest_tables.sql 

## March 1, 2024
- guest/php_action/core.php (2024-03-01)
  - Removed duplicate logGuestActivity() function definition
- guest/index.php (2024-03-01)
  - Redesigned guest login page to match main system login style and functionality
- guest/php_action/guestLogin.php (2024-03-01)
  - Enhanced login security with improved validation and error handling
  - Added account status and expiry checks
  - Added secure password handling
  - Improved session management
- guest/php_action/functions.php (2024-03-01)
  - Enhanced login validation with detailed error tracking
  - Improved security for login attempts tracking
  - Added reason logging for failed attempts
  - Updated session handling functions 

## 2023-07-20

- guest/dashboard.php (Removed "No direct script access" restrictions, updated for Digital Swap data)
- guest/php_action/fetchDashboardAnalytics.php (Removed permissions, simplified for Digital Swap transactions)
- guest/includes/header.php (Simplified header with basic navigation)
- guest/php_action/guestLogin.php (Fixed session variable compatibility)

## 2023-07-19

- guest/index.php
- guest/dashboard.php
- guest/php_action/guestLogin.php
- guest/php_action/functions.php
- guest/php_action/logout.php
- guest/includes/header.php
- guest/includes/footer.php 

## 2024-03-21
- guest/php_action/guestLogin.php
- guest/php_action/switchAccount.php
- guest/php_action/logout.php
- guest/php_action/getCategoryReport.php
- guest/php_action/core.php (Removed direct script access restriction)
- guest/php_action/db_connect.php (Removed direct script access restriction)
- guest/php_action/fetchCategoryReport.php (Removed direct script access restriction)
- guest/includes/header.php (Removed direct script access restriction) 

## 2024-03-01
- guest/php_action/guestLogin.php (Updated redirect path to use full application path)
- guest/index.php (Enhanced login redirect handling and error management)
- guest/includes/header.php (Updated all paths to use full application path /pistocklnt1march/) 

## 2024-03-02
- changelog/sql_changelog.md (Added ALTER TABLE statement for guest_users linked_account_id modification) 

## March 2024

### March 1, 2024
- warranty/index.php
- warranty/warranty_functions.php
- warranty/generate_certificate.php
- warranty/get_client_info.php
- warranty/create_warranty_table.sql
- includes/sidebar.php (updated)
- uploads/signatures/ (directory created) 

## 2024-03-25
- warranty/auth.php (Updated file paths and security checks)
- warranty/index.php (Created standalone page with proper dependencies)
- warranty/classes/WarrantyCertificatePDF.php (Fixed TCPDF integration and improved PDF layout)
- warranty/assets/css/style.css (Added custom styling)
- warranty/assets/js/script.js (Added client-side functionality)
- warranty/includes/config.php (Added configuration settings)
- warranty/generate_pdf.php (Created PDF generation handler)
- warranty/get_client_info.php (Created client data endpoint)

## May 8, 2025
- warranty/index.php - Updated file paths and BASEPATH definition
- warranty/warranty_functions.php - Fixed BASEPATH check
- sql/create_activity_log.sql - Created new file for user activity log table
- warranty/create_warranty_table.sql - Created new file for warranty certificates table
- warranty/preview_certificate.php - Added certificate preview functionality
- warranty/index.php - Added preview button and modal for certificate preview

## May 9, 2025
- warranty/preview_certificate.php - Fixed CORS headers and permission checking
- warranty/index.php - Updated JavaScript code for preview functionality
- warranty/preview_certificate.php - Updated permission check to use database roles and permissions
- Database - Cleaned up duplicate warranty permissions
- warranty/preview_certificate.php - Removed permission check for preview functionality while maintaining login security
- warranty/preview_certificate.php - Added placeholder data support for empty fields in preview mode
- warranty/print_preview.php - Created new file with attractive certificate design
- warranty/index.php - Added print preview functionality with new design 

## 2024-03-08

- warranty/index.php - Fixed print preview functionality and modal structure
- warranty/index.php - Updated print preview functionality
- warranty/create_warranty_terms_templates.sql - Created new file for terms templates table
- warranty/warranty_terms_functions.php - Created new file for terms template functions
- warranty/get_template_content.php - Created new file for template content retrieval
- warranty/index.php - Added terms template selection functionality
- warranty/index.php - Fixed print preview functionality and modal structure
- warranty/index.php - Updated print preview functionality 