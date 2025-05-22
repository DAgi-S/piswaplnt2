# User Roles and Permissions Documentation

## Database Structure

### Users Table (`users`)
- Primary Key: `user_id` (auto-increment)
- Fields:
  - `username` (varchar) - Unique username
  - `email` (varchar) - User's email address
  - `full_name` (varchar) - User's full name
  - `password` (varchar) - Encrypted password
  - `role_id` (int) - Foreign key to roles table
  - `status` (tinyint) - Account status (1=active, 0=inactive)
  - `created_at` (timestamp) - Account creation date
  - `updated_at` (timestamp) - Last update date
  - `last_login` (timestamp) - Last login timestamp
  - `profile_image` (varchar) - Path to profile image
  - `phone` (varchar) - Contact number
  - `language` (varchar) - Preferred language
  - `timezone` (varchar) - User's timezone
  - `dashboard_preferences` (text) - JSON of dashboard settings
  - `account_id` (int) - Associated account ID
  - `telegram_chat_id` (varchar) - Telegram chat ID for notifications

### Roles Table (`user_roles`)
- Primary Key: `role_id` (auto-increment)
- Fields:
  - `role_name` (varchar) - Name of the role
  - `description` (text) - Role description
  - `created_at` (timestamp) - Role creation date

### Permissions Table (`permissions`)
- Primary Key: `permission_id` (auto-increment)
- Fields:
  - `permission_name` (varchar) - Unique permission identifier
  - `description` (text) - Permission description
  - `module` (varchar) - Associated module/feature

### Role Permissions Table (`role_permissions`)
- Composite Key: (`role_id`, `permission_id`)
- Fields:
  - `role_id` (int) - Foreign key to roles
  - `permission_id` (int) - Foreign key to permissions

### Header Permissions Table (`header_permissions`)
- Primary Key: (`role_id`, `header_type`)
- Fields:
  - `role_id` (int) - Foreign key to roles
  - `header_type` (varchar) - Type of header (main, production, guest, gps)
  - `can_view` (tinyint) - View permission (1=yes, 0=no)
  - `can_edit` (tinyint) - Edit permission (1=yes, 0=no)
  - `can_delete` (tinyint) - Delete permission (1=yes, 0=no)

## Available Roles

1. Super Admin (role_id: 1)
   - Full system access with all permissions
   - Can manage other administrators
   - System configuration access

2. Admin (role_id: 2)
   - Full system access with all permissions
   - Cannot manage other administrators

3. User (role_id: 3)
   - Basic user with limited permissions
   - Access to assigned modules only

4. Production Manager (role_id: 5)
   - Manages production operations and schedules
   - Access to production-related modules

5. Production Supervisor (role_id: 6)
   - Supervises production operations
   - Limited production management access

6. Production Operator (role_id: 7)
   - Operates production machinery and processes
   - Basic production access

## Permission Modules

1. Product Module
   - `view_product` - Can view products
   - `create_product` - Can create new products
   - `edit_product` - Can edit existing products
   - `delete_product` - Can delete products
   - `view_brand` - Can view brands
   - `create_brand` - Can create brands
   - `edit_brand` - Can edit brands
   - `delete_brand` - Can delete brands
   - `view_categories` - Can view categories
   - `create_category` - Can create categories
   - `edit_category` - Can edit categories
   - `delete_category` - Can delete categories
   - `manage_brands` - Can manage brands
   - `manage_categories` - Can manage categories
   - `view_products` - View Products

2. Order/Invoice Module
   - `view_orders` - Can view orders
   - `create_order` - Can create orders
   - `edit_order` - Can edit orders
   - `delete_order` - Can delete orders
   - `view_order_reports` - Can view order reports
   - `view_invoice` - Can view invoices
   - `create_invoice` - Can create invoices
   - `edit_invoice` - Can edit invoices
   - `delete_invoice` - Can delete invoices

3. Digital Swap Module
   - `digitalswap` - Can access digital swap module (base permission)
   - `view_digitalswap` - Can view digital swap transactions
   - `create_digitalswap` - Can create digital swap transactions
   - `view_digitalswap_reports` - Can view digital swap reports
   - `view_digitalswap_report` - Can view detailed digital swap report
   - `view_digitalswap_analytics` - Can view digital swap analytics
   - `manage_swaps` - Can manage swaps
   - `view_swap_analytics` - Can view swap analytics
   - `view_swap_reports` - Can view swap reports
   - `edit_swap` - Can edit swaps
   - `delete_swap` - Can delete swaps

4. Letter Module
   - `view_letter` - Can view GPS letters (base permission)
   - `view_gps_letter` - Can view and generate GPS letters
   - `create_letter` - Can create letters
   - `edit_letter` - Can edit letters
   - `delete_letter` - Can delete letters
   - `manage_templates` - Can manage letter templates

5. Dashboard Module
   - `view_dashboard` - Can view dashboard
   - `view_dashboard_stats` - Can view dashboard statistics
   - `view_low_stock` - Can view low stock alerts
   - `view_revenue` - Can view revenue statistics
   - `view_recent_orders` - Can view recent orders
   - `view_recent_letters` - Can view recent letters
   - `view_analytics` - Can view analytics

6. User Management Module
   - `manage_users` - Can manage users
   - `manage_roles` - Can manage roles
   - `view_users` - Can view users
   - `create_user` - Can create users
   - `edit_user` - Can edit users
   - `delete_user` - Can delete users
   - `view_user` - Can view users
   - `add_user` - Add new users

7. Role Management Module
   - `view_roles` - View roles list
   - `add_role` - Add new roles
   - `edit_role` - Edit existing roles
   - `delete_role` - Delete roles
   - `assign_permissions` - Assign permissions to roles

8. System Configuration Module
   - `view_system_config` - View system configuration settings
   - `edit_system_config` - Edit system configuration settings
   - `manage_system_config` - Manage system configuration
   - `edit_email_config` - Edit email configuration settings
   - `edit_database_config` - Edit database configuration settings
   - `edit_backup_config` - Edit backup configuration settings
   - `edit_maintenance_mode` - Edit maintenance mode settings

9. Production Module
   - `view_production_orders` - View production orders
   - `create_production_order` - Create new production orders
   - `edit_production_order` - Edit existing production orders
   - `delete_production_order` - Delete production orders
   - `manage_bom` - Manage Bill of Materials
   - `view_production_reports` - View production reports
   - `manage_production_schedule` - Manage production schedule
   - `manage_quality_control` - Manage quality control checks
   - `manage_production_waste` - Manage production waste logs
   - `view_production_analytics` - View production analytics
   - `view_bom` - View Bill of Materials
   - `edit_bom` - Edit Bill of Materials
   - `delete_bom` - Delete Bill of Materials
   - `view_production_schedule` - View production schedule
   - `edit_production_schedule` - Edit production schedule
   - `access_production_dashboard` - Can access production dashboard

10. Inventory Module
    - `view_raw_materials` - View raw materials
    - `manage_raw_materials` - Manage raw materials
    - `edit_raw_materials` - Edit raw materials
    - `delete_raw_materials` - Delete raw materials
    - `view_finished_goods` - View finished goods
    - `manage_finished_goods` - Manage finished goods
    - `edit_finished_goods` - Edit finished goods
    - `delete_finished_goods` - Delete finished goods
    - `view_warehouse_stock` - View warehouse stock
    - `manage_warehouse_stock` - Manage warehouse stock
    - `transfer_stock` - Transfer stock between locations

11. Quality Control Module
    - `approve_quality_checks` - Approve quality checks
    - `view_quality_reports` - View quality reports

12. Purchasing Module
    - `manage_purchases` - Manage purchases
    - `manage_suppliers` - Manage suppliers
    - `manage_quotations` - Manage quotations

13. Accounts Module
    - `view_accounts` - View accounts
    - `manage_accounts` - Manage accounts
    - `view_transactions` - View transactions
    - `manage_transactions` - Manage transactions
    - `view_audit_report` - View audit report
    - `view_annual_report` - View annual report

14. Expense Module
    - `approve_expense` - Approve expense entries
    - `manage_expense_categories` - Manage expense categories
    - `view_expense_reports` - View expense reports and analytics
    - `create_expense` - Create new expense entries
    - `edit_expense` - Edit existing expense entries
    - `delete_expense` - Delete expense entries

15. Data Management Module
    - `import_data` - Can import data
    - `export_data` - Can export data
    - `manage_backup` - Manage system backups
    - `email_settings` - Manage email settings

16. GPS Business Module
    - `manage_business_cycles` - Can manage GPS business cycles
    - `manage_profit_distribution` - Can manage profit distribution
    - `view_business_reports` - Can view business reports

17. Guest Management Module
    - `view_guests` - Can view guest list and details
    - `manage_guests` - Can manage guest accounts and access
    - `view_guest_transactions` - Can view guest transaction history
    - `view_guest_reports` - Can view guest reports and analytics
    - `create_guest_account` - Can create new guest accounts
    - `edit_guest_account` - Can edit guest account details
    - `delete_guest_account` - Can delete guest accounts
    - `manage_guest_permissions` - Can manage guest access permissions

18. Telegram Module
    - `view_telegram` - Can view telegram settings and messages
    - `add_telegram` - Can add telegram configurations
    - `edit_telegram` - Can edit telegram settings
    - `manage_telegram_bot` - Can manage telegram bot settings

19. Header Management Module
    - `manage_headers` - Base permission for header management access
    - `view_headers` - Can view header configurations
    - `create_headers` - Can create new headers
    - `edit_headers` - Can edit existing headers
    - `delete_headers` - Can delete headers
    - Module-specific permissions:
      - Main System Header:
        - `manage_main_header` - Can manage main system header
        - `view_main_header` - Can view main header
        - `edit_main_header` - Can edit main header
      - Production Module Header:
        - `manage_production_header` - Can manage production header
        - `view_production_header` - Can view production header
        - `edit_production_header` - Can edit production header
      - Guest Portal Header:
        - `manage_guest_header` - Can manage guest portal header
        - `view_guest_header` - Can view guest header
        - `edit_guest_header` - Can edit guest header
      - GPS Business Header:
        - `manage_gps_header` - Can manage GPS business header
        - `view_gps_header` - Can view GPS header
        - `edit_gps_header` - Can edit GPS header

## Permission Inheritance

- Super Admin and Admin roles inherit all permissions by default
- Other roles are assigned specific permissions based on their responsibilities
- Module-specific roles (e.g., Production Manager) inherit all permissions within their module

## Security Features

1. Role-Based Access Control (RBAC)
   - Permissions are assigned to roles, not directly to users
   - Users inherit permissions from their assigned role
   - Multiple permission levels within each module

2. Permission Checking
   - Server-side permission validation
   - Client-side UI adaptation based on permissions
   - Middleware protection for sensitive routes

3. Audit Trail
   - Permission changes are logged
   - Role assignment changes are tracked
   - Failed permission checks are recorded

## Best Practices

1. Role Assignment
   - Users should be assigned the role with minimum required permissions
   - Regular review of role assignments
   - Document reason for role changes

2. Permission Management
   - Regular audit of permissions
   - Remove unused permissions
   - Document new permission additions

3. Security
   - Encrypt sensitive data
   - Use secure session management
   - Implement request rate limiting

## Implementation Notes

- Permission checks use the `hasPermission()` function
- UI elements are conditionally rendered based on permissions
- API endpoints validate permissions before processing requests
- Permission cache is implemented for performance
- Regular permission cleanup jobs are scheduled 