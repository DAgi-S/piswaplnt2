# Database Tables Documentation

## Overview
This document provides detailed information about all database tables in the system, including their structure, relationships, and indexes.

## Table Categories

### 1. User Management Tables

#### users
```sql
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
);
```

#### roles
```sql
CREATE TABLE `roles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `role_name` varchar(50) NOT NULL,
    `description` text,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `role_name` (`role_name`)
);
```

#### permissions
```sql
CREATE TABLE `permissions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `permission_name` varchar(100) NOT NULL,
    `description` text,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `permission_name` (`permission_name`)
);
```

#### user_roles
```sql
CREATE TABLE `user_roles` (
    `user_id` int(11) NOT NULL,
    `role_id` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`user_id`,`role_id`),
    KEY `role_id` (`role_id`),
    CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
);
```

### 2. Product Management Tables

#### products
```sql
CREATE TABLE `products` (
    `product_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `price` decimal(10,2) NOT NULL,
    `category_id` int(11) NOT NULL,
    `brand_id` int(11) NOT NULL,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`product_id`),
    KEY `category_id` (`category_id`),
    KEY `brand_id` (`brand_id`),
    CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
    CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`)
);
```

#### categories
```sql
CREATE TABLE `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `parent_id` int(11) DEFAULT NULL,
    `description` text,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `parent_id` (`parent_id`),
    CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`)
);
```

### 3. Inventory Management Tables

#### warehouse_stock
```sql
CREATE TABLE `warehouse_stock` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `warehouse_id` int(11) NOT NULL,
    `quantity` decimal(10,2) NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `product_warehouse` (`product_id`,`warehouse_id`),
    KEY `warehouse_id` (`warehouse_id`),
    CONSTRAINT `fk_warehouse_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
    CONSTRAINT `fk_warehouse_stock_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
);
```

#### stock_movements
```sql
CREATE TABLE `stock_movements` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `quantity` decimal(10,2) NOT NULL,
    `type` enum('in','out') NOT NULL,
    `reference_id` int(11) NOT NULL,
    `reference_type` varchar(50) NOT NULL,
    `date` datetime NOT NULL,
    `created_by` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    KEY `reference` (`reference_id`,`reference_type`),
    KEY `created_by` (`created_by`),
    CONSTRAINT `fk_stock_movements_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
    CONSTRAINT `fk_stock_movements_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
);
```

### 4. Order Management Tables

#### orders
```sql
CREATE TABLE `orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_number` varchar(20) NOT NULL,
    `customer_id` int(11) NOT NULL,
    `total` decimal(10,2) NOT NULL,
    `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_number` (`order_number`),
    KEY `customer_id` (`customer_id`),
    CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `clients` (`id`)
);
```

#### order_items
```sql
CREATE TABLE `order_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` decimal(10,2) NOT NULL,
    `price` decimal(10,2) NOT NULL,
    `total` decimal(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
    CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
);
```

### 5. Purchase Management Tables

#### purchases
```sql
CREATE TABLE `purchases` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `purchase_number` varchar(20) NOT NULL,
    `supplier_id` int(11) NOT NULL,
    `total` decimal(10,2) NOT NULL,
    `status` enum('pending','approved','received','cancelled') NOT NULL DEFAULT 'pending',
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `purchase_number` (`purchase_number`),
    KEY `supplier_id` (`supplier_id`),
    CONSTRAINT `fk_purchases_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
);
```

#### purchase_items
```sql
CREATE TABLE `purchase_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `purchase_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` decimal(10,2) NOT NULL,
    `price` decimal(10,2) NOT NULL,
    `total` decimal(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `purchase_id` (`purchase_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `fk_purchase_items_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`),
    CONSTRAINT `fk_purchase_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
);
```

### 6. Production Management Tables

#### production_orders
```sql
CREATE TABLE `production_orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_number` varchar(20) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` decimal(10,2) NOT NULL,
    `status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    `start_date` datetime NOT NULL,
    `end_date` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_number` (`order_number`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `fk_production_orders_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
);
```

#### production_progress
```sql
CREATE TABLE `production_progress` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `status` varchar(50) NOT NULL,
    `notes` text,
    `timestamp` datetime NOT NULL,
    `created_by` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `created_by` (`created_by`),
    CONSTRAINT `fk_production_progress_order` FOREIGN KEY (`order_id`) REFERENCES `production_orders` (`id`),
    CONSTRAINT `fk_production_progress_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
);
```

### 7. Financial Management Tables

#### expenses
```sql
CREATE TABLE `expenses` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category_id` int(11) NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `description` text,
    `date` date NOT NULL,
    `created_by` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    KEY `created_by` (`created_by`),
    CONSTRAINT `fk_expenses_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`),
    CONSTRAINT `fk_expenses_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
);
```

#### expense_categories
```sql
CREATE TABLE `expense_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `description` text,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
);
```

### 8. System Configuration Tables

#### settings
```sql
CREATE TABLE `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `key` varchar(50) NOT NULL,
    `value` text NOT NULL,
    `description` text,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `key` (`key`)
);
```

#### system_config_settings
```sql
CREATE TABLE `system_config_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category_id` int(11) NOT NULL,
    `key` varchar(50) NOT NULL,
    `value` text NOT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `category_key` (`category_id`,`key`),
    CONSTRAINT `fk_system_config_settings_category` FOREIGN KEY (`category_id`) REFERENCES `system_config_categories` (`id`)
);
```

### 9. Notification System Tables

#### notifications
```sql
CREATE TABLE `notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `type` varchar(50) NOT NULL,
    `message` text NOT NULL,
    `read` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
);
```

#### notification_templates
```sql
CREATE TABLE `notification_templates` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `content` text NOT NULL,
    `type` varchar(50) NOT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
);
```

### 10. Audit and Logging Tables

#### audit_log
```sql
CREATE TABLE `audit_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `action` varchar(50) NOT NULL,
    `details` text,
    `timestamp` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `action` (`action`),
    CONSTRAINT `fk_audit_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
);
```

#### system_audit_trails
```sql
CREATE TABLE `system_audit_trails` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `action` varchar(50) NOT NULL,
    `user_id` int(11) NOT NULL,
    `details` text,
    `timestamp` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `action` (`action`),
    CONSTRAINT `fk_system_audit_trails_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
);
```

## Indexes Summary

### Primary Indexes
- All tables have an `id` field as PRIMARY KEY
- Composite PRIMARY KEYs for junction tables (e.g., `user_roles`)

### Unique Indexes
- `users`: username, email
- `roles`: role_name
- `permissions`: permission_name
- `orders`: order_number
- `purchases`: purchase_number
- `production_orders`: order_number
- `settings`: key
- `notification_templates`: name

### Foreign Key Indexes
- All foreign key relationships are indexed
- Common foreign keys include:
  - user_id
  - product_id
  - order_id
  - category_id
  - supplier_id

### Performance Indexes
- Date-based indexes for audit and logging tables
- Status-based indexes for order and purchase tables
- Composite indexes for frequently joined fields

## Maintenance Guidelines

### Regular Tasks
1. Index optimization
2. Table statistics updates
3. Foreign key constraint verification
4. Data consistency checks
5. Performance monitoring

### Backup Strategy
1. Daily incremental backups
2. Weekly full backups
3. Monthly archive backups
4. Automated backup verification

## Security Considerations

### Data Protection
1. Sensitive data encryption
2. Regular security audits
3. Access control implementation
4. Audit trail maintenance
5. Data retention policies 