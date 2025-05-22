# Database Structure Documentation

## Overview
This document provides a comprehensive overview of the database structure used in the system. The database is organized into logical categories to manage different aspects of the business operations.

## Table Categories

### 1. User Management
| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| users | Stores user information and authentication details | id, username, email, password, status |
| roles | Defines different user roles in the system | id, role_name, description |
| permissions | Manages specific permissions for roles | id, permission_name, description |
| role_permissions | Links roles to specific permissions | role_id, permission_id |
| user_roles | Associates users with roles | user_id, role_id |
| user_tokens | Manages user authentication tokens | user_id, token, expires_at |
| guest_users | Stores information about guest users | id, email, created_at |
| guest_account_links | Links guest users to accounts | guest_id, account_id |

### 2. Authentication & Security
| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| sessions | Manages user sessions | id, user_id, token, expires_at |
| audit_log | Tracks system-wide audit events | id, user_id, action, timestamp |
| audit_logs | Detailed audit logging | id, user_id, action, details, timestamp |
| system_audit_trails | System-level audit trails | id, action, user_id, timestamp |
| user_permissions | User-specific permissions | user_id, permission_id |

### 3. Product & Inventory Management
| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| products | Main product information | product_id, name, description, price |
| categories | Product categories | id, name, parent_id |
| brands | Product brands | id, name, description |
| inventory_locations | Storage locations | id, name, description |
| warehouse_stock | Current stock levels | product_id, warehouse_id, quantity |
| stock_movements | Tracks inventory movements | id, product_id, quantity, type, date |
| inventory_adjustments | Records inventory adjustments | id, product_id, quantity, reason |
| raw_materials | Raw material information | id, name, description, unit |
| raw_material_movements | Raw material movement tracking | id, material_id, quantity, type |
| warehouse_zones | Warehouse zone definitions | id, name, warehouse_id |

### 4. Sales & Orders
| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| orders | Main order information | id, order_number, customer_id, total |
| order_items | Individual order items | id, order_id, product_id, quantity |
| sales_orders | Sales order records | id, order_number, customer_id, total |
| sales_order_items | Sales order line items | id, order_id, product_id, quantity |
| sales_payments | Sales payment records | id, order_id, amount, payment_date |
| payment_methods | Available payment methods | id, name, description |
| payment_categories | Payment categories | id, name, description |
| payment_followups | Payment follow-up tracking | id, order_id, followup_date, status |

### 5. Purchases & Suppliers
| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| purchases | Purchase order records | id, purchase_number, supplier_id, total |
| purchase_items | Purchase order items | id, purchase_id, product_id, quantity |
| purchase_payments | Purchase payment records | id, purchase_id, amount, date |
| suppliers | Supplier information | id, name, contact, address |
| purchase_requisitions | Purchase requisition records | id, requester_id, status, date |
| purchase_approvals | Purchase approval records | id, requisition_id, approver_id, status |
| purchase_documents | Purchase document storage | id, purchase_id, document_type, file_path |

### 6. Production Management
| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| production_orders | Production order records | id, order_number, status, start_date |
| production_products | Production product information | id, name, description, specifications |
| production_categories | Production categories | id, name, description |
| production_brands | Production brands | id, name, description |
| production_progress | Production progress tracking | id, order_id, status, timestamp |
| production_quality_checks | Quality control records | id, order_id, check_type, result |
| production_machine_maintenance | Machine maintenance records | id, machine_id, maintenance_type, date |
| production_shift_schedules | Shift scheduling | id, start_time, end_time, workers |

### 7. Financial Management
| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| expenses | Expense records | id, category_id, amount, date |
| expense_categories | Expense categories | id, name, description |
| expense_entries | Detailed expense entries | id, expense_id, amount, description |
| expense_audit_logs | Expense audit records | id, expense_id, action, timestamp |
| recurring_expenses | Recurring expense records | id, category_id, amount, frequency |
| loans | Loan records | id, borrower_id, amount, status |
| loan_payments | Loan payment records | id, loan_id, amount, payment_date |

### 8. Reporting & Analytics
| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| reports | Report definitions | id, name, type, parameters |
| report_generation_logs | Report generation history | id, report_id, generated_by, timestamp |
| scheduled_reports | Scheduled report configurations | id, report_id, schedule, recipients |
| report_schedules | Report scheduling details | id, report_id, frequency, next_run |
| report_users | Report user assignments | report_id, user_id, access_level |

### 9. System Configuration
| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| settings | System-wide settings | id, key, value, description |
| system_config_settings | Configuration settings | id, category_id, key, value |
| system_config_categories | Configuration categories | id, name, description |
| company_settings | Company-specific settings | id, setting_key, setting_value |
| currency_settings | Currency configuration | id, currency_code, exchange_rate |
| tax_rates | Tax rate definitions | id, name, rate, description |

### 10. Notification System
| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| notifications | Notification records | id, user_id, type, message |
| notification_templates | Notification templates | id, name, content, type |
| notification_channels | Notification channels | id, name, type, settings |
| notification_queue | Notification queue | id, notification_id, status, scheduled_at |
| notification_logs | Notification delivery logs | id, notification_id, status, timestamp |
| user_notifications | User notification preferences | user_id, notification_type, enabled |
| user_notification_preferences | User notification settings | user_id, channel_id, preferences |

### 11. Integration & External Services
| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| telegram_bot_settings | Telegram bot configuration | id, bot_token, chat_id |
| telegram_notification_settings | Telegram notification settings | id, template_id, enabled |
| telegram_notification_templates | Telegram message templates | id, name, content |
| telegram_notification_logs | Telegram notification history | id, message_id, status, timestamp |
| system_integration_logs | Integration activity logs | id, service, action, timestamp |

### 12. Quality Control
| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| quality_control | Quality control records | id, product_id, test_type, result |
| quality_control_parameters | QC parameters | id, name, description, limits |
| quality_control_results | QC test results | id, control_id, parameter_id, value |
| quality_defect_types | Defect type definitions | id, name, description, severity |
| quality_inspection_points | Inspection point definitions | id, name, location, frequency |

## Database Relationships

### Key Relationships
1. Users ↔ Roles (Many-to-Many)
2. Products ↔ Categories (Many-to-One)
3. Orders ↔ Order Items (One-to-Many)
4. Purchases ↔ Purchase Items (One-to-Many)
5. Production Orders ↔ Production Progress (One-to-Many)
6. Expenses ↔ Expense Categories (Many-to-One)
7. Notifications ↔ Users (Many-to-One)

## Indexes and Optimization

### Key Indexes
1. Users: email, username
2. Products: name, category_id
3. Orders: order_number, customer_id
4. Purchases: purchase_number, supplier_id
5. Production Orders: order_number, status
6. Expenses: date, category_id

## Maintenance Guidelines

### Regular Tasks
1. Database optimization
2. Index maintenance
3. Log cleanup
4. Backup verification
5. Performance monitoring

### Backup Strategy
1. Daily incremental backups
2. Weekly full backups
3. Monthly archive backups
4. Automated backup verification

## Security Considerations

### Data Protection
1. Encrypted sensitive data
2. Regular security audits
3. Access control implementation
4. Audit trail maintenance
5. Data retention policies 