# File Change Log

## 2024-05-02
- includes/functions.php - Created new file with utility functions including logActivity
- sql/activity_logs.sql - Created new file for activity_logs table structure
- includes/core.php - Added include for functions.php

## 2024-05-01
- custom/css/custom.css - Created new file with custom styles
- php_action/guestLogin.php - Fixed session handling and improved error handling
- index.php - Updated login form JavaScript to handle server redirects
- includes/session_config.php - Created new file for session configuration
- includes/config.php - Moved session settings to session_config.php
- includes/core.php - Updated to load session_config.php before starting session
- includes/config.php - Created configuration file with system settings
- includes/core.php - Created core functionality file
- includes/constants.php - Created system-wide constants file
- includes/db_connect.php - Updated to use configuration constants
- Created directories: uploads, temp, logs
- index.php - Added core.php requirement and login check
- dashboard.php - Added core.php requirement and user data check
- profile.php - Added core.php requirement and user data check
- logout.php - Added core.php requirement and activity logging
- access_denied.php - Added core.php requirement
- php_action/guestLogin.php - Added core.php requirement
- php_action/changePassword.php - Added core.php requirement
- php_action/switchAccount.php - Updated to use core.php functions
- php_action/guestLogin.php - Updated database connection path
- includes/header.php - Fixed database connection path
- includes/header.php - Added missing forward slash in db_connect.php path
- index.php - Fixed redirection path after successful login
- php_action/guestLogin.php - Added redirect URL to response
- index.php - Updated JavaScript to handle absolute URL redirection
- index.php - Complete redesign of login page with improved UI and security
- php_action/guestLogin.php - Complete rewrite with enhanced security features and logging
- php_action/guestLogin.php - Simplified login process by removing session tracking, login attempts, and activity logging
- includes/header.php - Updated to Bootstrap 5 and fixed asset paths
- dashboard.php - Updated to Bootstrap 5 and improved layout
- includes/core.php - Improved error handling, simplified getCurrentUser function, and removed unused functions
- includes/header.php - Removed duplicate session checks and improved account handling
- dashboard.php - Added support for users without linked accounts and fixed redirect loop
- includes/header.php - Fixed null array access errors and improved data handling
- dashboard.php - Fixed htmlspecialchars warnings and improved error handling
- dashboard.php - Added JavaScript code to fetch and display dashboard data
- php_action/fetchDashboardAnalytics.php - Fixed queries, improved error handling, and added better data formatting

## 2024-03-20
- php_action/guestLogin.php (Updated - Improved login security and session handling)
- index.php (Updated - Removed redundant login checks)
- includes/functions.php (Updated - Removed duplicate cleanInput function)
- includes/functions.php (Updated - Removed duplicate isLoggedIn function)
- includes/functions.php (Updated - Removed duplicate formatDate function)
- includes/functions.php (Updated - Removed duplicate formatCurrency function)
- includes/functions.php (Updated)
- php_action/guestLogin.php
- index.php

### Database Changes
- Created guest_login_attempts table for tracking login attempts 

## 2024-03-19
- composer.json - Created new file and added PhpSpreadsheet dependency
- vendor/* - Added PhpSpreadsheet library and its dependencies
- php_action/exportTransactions.php - Fixed SQL queries and updated export format to match current database structure
- transaction_history.php - Fixed print preview to show all transactions and improved print layout styling
- dashboard.php (Updated with improved session handling and UI)
- php_action/fetchDashboardAnalytics.php (Fixed session handling and authentication)
- includes/header.php (Updated with improved navigation and styling)
- custom/css/dashboard.css (Created new file for dashboard styles)
- sql/fix_guest_tables.sql (Created)
- audit_report.php
- php_action/fetchAuditReport.php (Fixed reference_id column issue in SQL query)

## 2024-03-21
- includes/constants.php (Removed duplicate constants to fix redefinition warnings)
- includes/header.php (Fixed account switching dropdown to properly display accounts)
- Fixed account_id references in account switching functionality
- Added proper account linking in database
- php_action/switchAccount.php
- custom/css/modern.css

Changes:
- Enhanced account switching functionality
- Improved account dropdown UI
- Added better error handling and validation
- Added visual styling for account switching

## 2024-03-26
- transaction_history.php (Updated styles for better UI/UX)
- custom/css/reports.css (Created shared styles for reports)
- annual_report.php (Updated to use shared styles and improved UI)
- audit_report.php (Updated to use shared styles and improved UI)
- includes/header.php (Added Reports dropdown menu with Annual and Audit reports)
- php_action/fetchAnnualReport.php (Created new file for handling annual report data)

## 2024-03-21
- php_action/fetchDashboardAnalytics.php (Fixed duplicate account entries in dashboard)
- includes/core.php (Improved account switching session handling)
- Fixed session handling and account switching functionality
- includes/header.php (Fixed getCurrentUser function to use correct session variable)
- includes/header.php (Fixed account query to use correct guest_id field)
- Added better error handling for inactive accounts
- php_action/fetchTransactions.php (Updated to handle active account switching)
- transaction_history.php (Added account switch refresh functionality)
- Improved transaction history display and account switching
- Fixed transaction data filtering by active account

## 2024-03-21
- php_action/fetchDashboardAnalytics.php (Fixed duplicate account entries in dashboard)
- includes/core.php (Improved account switching session handling)
- Fixed session handling and account switching functionality
- includes/header.php (Fixed getCurrentUser function to use correct session variable)
- includes/header.php (Fixed account query to use correct guest_id field)
- Added better error handling for inactive accounts
- Removed `php_action/fetch_transactions.php` (Removed duplicate file to fix transaction history duplication issue)
- includes/core.php (Improved account switching session handling)
- Fixed session handling and account switching functionality
- includes/header.php (Fixed getCurrentUser function to use correct session variable)
- includes/header.php (Fixed account query to use correct guest_id field)
- Added better error handling for inactive accounts
- `php_action/fetchTransactions.php` (Fixed SQL queries to prevent duplicate transactions in DataTable)
  - Added DISTINCT clause to main transaction query
  - Updated JOIN conditions to prevent duplicates
  - Fixed transaction count in summary query 

## 2024-03-21
- `annual_report.php` (Fixed undefined array key and deprecated htmlspecialchars warnings)
  - Added null coalescing operators for safe array access
  - Added default values for htmlspecialchars calls 

## 2024-03-21
- `annual_report.php` (Improved print preview layout)
  - Removed navigation header from print view
  - Adjusted top margins and padding for cleaner print layout
  - Fixed spacing issues in print preview 

## 2024-03-21
- `audit_report.php` (Improved print preview layout)
  - Removed navigation header from print view
  - Set 11px base font size for print preview
  - Optimized table and badge styling for print
  - Adjusted spacing and margins for cleaner print layout 

## 2024-03-21
- `php_action/fetchAuditReport.php` (Fixed calculation issues)
  - Added proper running balance calculation considering previous months
  - Fixed monthly and annual summary calculations
  - Added status check to only include completed transactions
  - Improved transaction ordering and balance tracking 

## 2024-03-21
- `php_action/fetchAuditReport.php` (Fixed monthly and annual summary calculations)
  - Improved running balance calculation logic
  - Fixed monthly deposits and withdrawals totals
  - Corrected annual summary calculation from monthly data
  - Added proper transaction date handling for balance calculations 

## 2024-03-21
- `php_action/fetchAuditReport.php` (Fixed audit report calculations based on actual table structure)
  - Added proper JOIN with guest_account_links table
  - Fixed transaction type handling for deposits and withdrawals
  - Added guest_id validation in queries
  - Improved balance calculation logic with proper table relationships 

## 2024-03-21
- `php_action/fetchAuditReport.php` (Fixed transaction fetching logic)
  - Updated queries to use proper subqueries instead of JOINs
  - Fixed parameter binding order in prepared statements
  - Improved account and guest user validation
  - Corrected transaction filtering logic 

## 2024-03-21
- `php_action/fetchAuditReport.php` (Fixed transaction fetching to match working query structure)
  - Updated queries to use proper JOINs instead of subqueries
  - Added DISTINCT to prevent duplicate records
  - Added Currency field from accounts table
  - Simplified balance calculation logic 

## 2024-03-21
- `php_action/fetchAuditReport.php` (Fixed balance calculation logic)
  - Added proper tracking of opening and closing balances
  - Fixed running balance calculation for each transaction
  - Added balance tracking for monthly summaries
  - Improved annual summary with proper opening and closing balances 