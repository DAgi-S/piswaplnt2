# PiStock Inventory & Production Management System

## Overview
PiStock is a comprehensive Manufacturing ERP (Enterprise Resource Planning) system designed for inventory management, production tracking, quality control, and sales management. The system provides end-to-end solutions for manufacturing businesses, from raw material procurement to finished goods sales.

## Core Modules

### Production Management
- **Bill of Materials (BOM)**: Define product composition with raw material requirements
- **Production Orders**: Create and track manufacturing work orders
- **Production Planning**: Schedule and monitor production activities
- **Material Requirements**: Calculate and allocate materials for production
- **Production Progress Tracking**: Record real-time progress on production orders
- **Quality Control**: Inspect and validate product quality at various stages

### Inventory Management
- **Raw Materials**: Track raw material inventory with minimum stock levels
- **Finished Goods**: Manage produced items inventory
- **Warehouses**: Support for multiple warehouse locations
- **Stock Movements**: Record all inventory transactions with full audit trail
- **Inventory Adjustments**: Make corrections with proper authorization
- **Low Stock Alerts**: Automatic notifications for items below minimum levels

### Quality Control
- **Inspection Points**: Define quality checkpoints throughout production
- **Quality Parameters**: Set measurable quality standards
- **Defect Tracking**: Record and categorize production defects
- **Quality Reporting**: Generate comprehensive quality analytics

### Purchasing
- **Purchase Orders**: Create and manage supplier orders
- **Supplier Management**: Maintain supplier information and performance history
- **Purchase Payments**: Track payments and outstanding balances
- **Goods Receipt**: Record material receipts against purchase orders

### Sales & Order Management
- **Sales Orders**: Process customer orders
- **Customer Management**: Maintain customer information
- **Order Fulfillment**: Track order status from creation to delivery
- **Invoicing**: Generate and track customer invoices
- **Payment Tracking**: Record customer payments

### Reporting & Analytics
- **Production Reports**: Analyze production efficiency and capacity
- **Inventory Reports**: Track stock levels and movements
- **Quality Reports**: Monitor defect rates and quality metrics
- **Financial Reports**: Generate profit, revenue and expense reports
- **Custom Dashboards**: Create user-specific data visualizations

## Technical Architecture

### Database
- MySQL database with optimized schema
- 130+ tables covering all aspects of manufacturing operations
- Comprehensive foreign key relationships ensuring data integrity
- Schema management system for maintaining database structure

### Backend
- PHP-based application with modular design
- Role-based access control system
- Middleware pattern for request processing
- API endpoints for external integrations
- Transaction-based operations for data integrity

### Frontend
- Responsive web interface
- Bootstrap-based UI components
- DataTables for data presentation
- Chart.js for data visualization
- JavaScript-based form validation and dynamic UI updates

## Security Features
- Role-based access control with granular permissions
- Comprehensive audit logging
- CSRF protection for all forms
- Rate limiting to prevent abuse
- Session management with secure token handling
- Input validation and sanitization

## Implementation Methods
- Modular architecture allowing for component-based deployment
- Class-based logic implementation instead of stored procedures
- Transaction support for data integrity across operations
- Comprehensive error handling and logging
- Automated schema validation and updates

## Deployment
- Deployment instructions provided for step-by-step installation
- Backup mechanisms for safe updates
- Rollback procedures in case of issues
- Testing checklists for verification

## Recent Updates
- Quality Control Module implementation
- Enhanced Material Usage Reporting
- Bug fixes for stock movements
- Database schema compatibility improvements
- Improved error handling and validation

## Required Setup
- Web server with PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+
- Modern web browser with JavaScript enabled

## Documentation
- System analysis documents available in markdown format
- Table structure documentation
- Implementation guides
- Deployment instructions
- Changelog tracking all updates









# Quotation Management System
Amzing Project on Management System

-Open source inventory management system with php and mysql

-Quotation generation and easy to download invoice in PDF format

-Lightweight and easy to use

-Order management and product management can be done with ease

-Report management

-User wise sell report.

# Requirement

```
Need to change
store_url in db_connect.php

Login Credentials
Id : admin
password : password
```
# PiStocklnt - Inventory Management System
## Recent Updates (Date: Current Date)
### Work Done Today
. Attempted to fix DataTables integration in product management
. Updated database queries for product fetching
. Added error logging and debugging for AJAX calls
. Restructured JavaScript initialization for DataTables
### Known Issues
. DataTables initialization not working properly in product.php
. AJAX data fetching needs further debugging
. Table display issues in product management section
### Next Steps
. Debug DataTables library loading
. Verify database connection and query execution
. Implement proper error handling for AJAX calls
. Test and fix product data display
### Git Update Instructions
. Navigate to the project directory
. Run `git add .` to add all changes
. Run `git commit -m "Update: Current Date - Work Done Today"`
. Run `git push origin main` to push changes to GitHub

bash
git add .
git commit -m "Updated product management system with DataTables integration and debugging"
git push origin main


### Technical Notes
- Database table name confirmed as 'products'
- Added debugging logs in PHP and JavaScript
- Updated DataTables CDN integration
- Modified AJAX response handling

