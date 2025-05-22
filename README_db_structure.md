# Database Structure Documentation

## Core Tables

### Users and Authentication
- `users` - User accounts and authentication details #auth
- `permissions` - System permissions #auth
- `audit_log` - System activity logging #audit
- `audit_logs` - Additional audit logging #audit

### Products and Inventory
- `products` - Main products table #inventory
- `categories` - Product categories #inventory
- `brands` - Product brands #inventory
- `inventory_adjustments` - Stock adjustments #inventory
- `inventory_count_sessions` - Physical count sessions #inventory
- `inventory_locations` - Storage locations #inventory
- `inventory_movement_log` - Stock movement tracking #inventory
- `inventory_transfer_logs` - Transfer records #inventory

### Orders and Sales
- `orders` - Main orders table #sales
- `order_items` - Order line items #sales
- `clients` - Client/customer information #sales
- `purchase_approvals` - Purchase approval workflow #purchasing
- `purchase_documents` - Purchase documentation #purchasing

### Production
- `production_orders` - Production order management #production
- `production_products` - Products in production #production
- `production_progress` - Production tracking #production
- `production_quality_checks` - Quality control #production
- `production_shift_schedules` - Work schedules #production
- `production_waste_logs` - Waste tracking #production
- `bill_of_materials` - Product components #production

### Warehouse Management
- `warehouses` - Warehouse locations #warehouse
- `warehouse_zones` - Storage zones #warehouse
- `warehouse_stock_movements` - Stock movements #warehouse

### Settings and Configuration
- `company_settings` - System configuration #settings
- `currency_settings` - Currency configuration #settings

### Digital Transactions
- `digitalswap` - Digital transaction records #digital
- `digital_categories` - Transaction categories #digital
- `digital_transaction_categories` - Category mapping #digital
- `accounts` - Account management #digital

### Expense Management
- `expense_categories` - Expense category definitions #expense
- `expense_entries` - Main expense records #expense
- `expense_attachments` - Expense-related file attachments #expense

## Relationships and Dependencies
- Products → Categories (Many-to-One)
- Products → Brands (Many-to-One)
- Order Items → Products (Many-to-One)
- Orders → Clients (Many-to-One)
- Production Orders → Products (Many-to-One)
- Inventory Movements → Locations (Many-to-One)
- Expense Entries → Expense Categories (Many-to-One)
- Expense Attachments → Expense Entries (Many-to-One)

## Notes
1. All tables use InnoDB engine for transaction support
2. UTF8MB4 character set is used for proper Unicode support
3. Most tables include audit fields (created_at, updated_at)
4. Soft delete pattern used where applicable (deleted flag)
5. Foreign key constraints enforced for data integrity 