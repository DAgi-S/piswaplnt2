# System Directory Structure

## Core Directories

### `/includes`
Core system includes and header files:
- `header.php` - Main system header
- `db_connect.php` - Database connection
- `middleware.php` - System middleware
- `auth_check.php` - Authentication checks

### `/php_action`
Backend PHP action handlers:
- `core.php` - Core functionality
- `db_connect.php` - Database connection
- `createRole.php` - Role creation handler
- `updateRole.php` - Role update handler
- `deleteRole.php` - Role deletion handler
- `fetchRoles.php` - Role fetching
- `fetchRolePermissions.php` - Permission fetching
- `updateRolePermissions.php` - Permission updates

### `/production`
Production module files:
- `/includes/header.php` - Production header
- `dashboard.php` - Production dashboard
- `production_orders.php` - Order management
- `bill_of_materials.php` - BOM management
- `quality_control.php` - Quality control
- `inventory_tracking.php` - Inventory management

### `/gpsBusiness`
GPS Business module:
- `/includes/header.php` - GPS Business header
- `gps_business_cycles.php` - Business cycles
- `gps_profit_distribution.php` - Profit distribution
- `gps_business_reports.php` - Business reports

### `/guest`
Guest portal files:
- `/includes/header.php` - Guest portal header
- `dashboard.php` - Guest dashboard
- `transaction_history.php` - Transaction viewing
- `profile.php` - Guest profile management

### `/assets`
Static assets and resources:
- `/bootstrap` - Bootstrap framework files
- `/jquery` - jQuery library
- `/font-awesome` - Font Awesome icons
- `/plugins` - Third-party plugins
- `/custom/css` - Custom CSS files
- `/custom/js` - Custom JavaScript files

### `/docs`
System documentation:
- `permissions.md` - Permission documentation
- `role_management.md` - Role management docs
- `headers.md` - Header documentation
- `database_documentation.md` - Database structure
- `table_structures.md` - Table schemas

### `/config`
Configuration files:
- `database.php` - Database configuration
- `email.php` - Email settings
- `telegram.php` - Telegram bot configuration
- `system.php` - System settings

### `/logs`
System log files:
- `error.log` - Error logs
- `access.log` - Access logs
- `audit.log` - Audit trail
- `php_errors.log` - PHP error logs

## Main System Files

### Core Files
- `index.php` - Main entry point
- `login.php` - Login page
- `dashboard.php` - Main dashboard
- `core.php` - Core functionality
- `system_configuration.php` - System settings

### User Management
- `user.php` - User management
- `role_management.php` - Role management
- `manage_guests.php` - Guest management
- `manage_sessions.php` - Session management

### Business Operations
- `products.php` - Product management
- `orders.php` - Order management
- `managePurchases.php` - Purchase management
- `manageSuppliers.php` - Supplier management
- `manageQuotations.php` - Quotation management

### Reports and Analytics
- `digitalswap-analytics.php` - Digital swap analytics
- `annual_report.php` - Annual reporting
- `audit_log_viewer.php` - Audit log viewing

### Integration and Settings
- `telegram_bot_settings.php` - Telegram settings
- `telegram_notification_management.php` - Notification management
- `header_management.php` - Header management

### Database
- `structure.sql` - Database structure
- `data.sql` - Initial data
- `database_export.sql` - Full database export

## Testing and Development
- `/examples` - Example implementations
- `/system_analysis` - System analysis tools
- Test files:
  - `test_db_connection.php`
  - `test_session.php`
  - `test_ajax_products.php`
  - Various other test files

## Documentation Files
- `CHANGELOG.md` - Change history
- `TROUBLESHOOTING.md` - Troubleshooting guide
- `table_design_rule.md` - Table design rules
- `README_db_structure.md` - Database readme

## Security and Fixes
- `fix_permissions.php` - Permission fixes
- `fix_permissions.sql` - SQL permission fixes
- `fixed_header.php` - Header fixes
- `fixed_middleware.php` - Middleware fixes
- `permission_manager.php` - Permission management

## Mobile Integration
- `/mobile_app` - Mobile application files

## Backup and Recovery
- `/backups` - System backups
- `system_configuration_backup.php` - Configuration backups

## File Structures

### Core System Files
```
├── index.php                    # Main entry point
├── login.php                    # Login page
├── dashboard.php                # Main dashboard
├── core.php                     # Core functionality
├── system_configuration.php     # System settings
├── access_denied.php            # Access denied page
├── logout.php                   # Logout handler
└── .htaccess.txt               # Apache configuration
```

### API Structure
```
├── api/
│   ├── v1/                     # API version 1
│   │   ├── auth/              # Authentication endpoints
│   │   ├── products/          # Product endpoints
│   │   └── orders/            # Order endpoints
│   └── v2/                    # API version 2
│       ├── auth/              # Authentication endpoints
│       ├── protected/         # Protected endpoints
│       └── public/            # Public endpoints
```

### Production Module
```
├── production/
│   ├── includes/              # Production includes
│   │   ├── header.php        # Production header
│   │   └── auth_check.php    # Production auth check
│   ├── dashboard.php         # Production dashboard
│   ├── orders.php            # Order management
│   ├── inventory.php         # Inventory management
│   └── reports/              # Production reports
```

### Guest Module
```
├── guest/
│   ├── includes/             # Guest includes
│   │   ├── header.php       # Guest header
│   │   └── auth_check.php   # Guest auth check
│   ├── dashboard.php        # Guest dashboard
│   ├── profile.php          # Profile management
│   └── transactions.php     # Transaction history
```

### Documentation Structure
```
├── docs/                     # System documentation
│   ├── permissions.md       # Permission documentation
│   ├── role_management.md   # Role management docs
│   ├── headers.md          # Header documentation
│   ├── database_documentation.md  # Database docs
│   └── table_structures.md  # Table schemas
```

### Configuration Files
```
├── config/                   # Configuration files
│   ├── database.php         # Database config
│   ├── email.php            # Email settings
│   ├── telegram.php         # Telegram config
│   └── system.php           # System settings
```

### Log Files
```
├── logs/                    # System logs
│   ├── error.log           # Error logs
│   ├── access.log          # Access logs
│   ├── audit.log           # Audit trail
│   └── php_errors.log      # PHP error logs
```

### Mobile Application
```
├── mobile_app/              # Mobile application
│   ├── lib/                # Main app code
│   │   ├── main.dart      # App entry point
│   │   ├── screens/       # UI screens
│   │   ├── widgets/       # UI components
│   │   ├── models/        # Data models
│   │   ├── services/      # API services
│   │   └── utils/         # Utilities
│   ├── assets/            # Static assets
│   ├── test/              # Test files
│   └── pubspec.yaml       # Dependencies
```

### Test Files
```
├── tests/                  # Test files
│   ├── unit/              # Unit tests
│   ├── integration/       # Integration tests
│   └── functional/        # Functional tests
```

### Backup Structure
```
├── backups/               # System backups
│   ├── database/         # Database backups
│   ├── config/           # Configuration backups
│   └── logs/             # Log backups
```

### Vendor Dependencies
```
├── vendor/               # Third-party dependencies
│   ├── composer/        # Composer packages
│   ├── node_modules/    # Node.js packages
│   └── plugins/         # System plugins
```

### Upload Structure
```
├── uploads/             # File uploads
│   ├── images/         # Image uploads
│   ├── documents/      # Document uploads
│   └── temp/           # Temporary uploads
```

### Email Templates
```
├── email_templates/     # Email templates
│   ├── notifications/   # Notification templates
│   ├── reports/        # Report templates
│   └── system/         # System templates
```

### System Analysis
```
├── system_analysis/     # Analysis tools
│   ├── performance/    # Performance analysis
│   ├── security/       # Security analysis
│   └── reports/        # Analysis reports
```

### Database Structure
```
├── database/           # Database files
│   ├── structure.sql   # Database structure
│   ├── data.sql       # Initial data
│   └── migrations/    # Database migrations
```

### Documentation Guides
```
├── doc_for_guides/     # Documentation guides
│   ├── auth_check.md   # Authentication guide
│   ├── core.md        # Core system guide
│   ├── index.md       # Index system guide
│   ├── mobile_app.md  # Mobile app guide
│   └── directories.md # Directory structure guide
``` 