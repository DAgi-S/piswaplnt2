# Expense Module Table Structures

This document lists all tables related to the expense management system and their structures.

## Core Expense Tables

### 1. expense_categories
Table for managing expense categories and their hierarchies.

```sql
CREATE TABLE `expense_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `parent_id` int(11) DEFAULT NULL,
    `monthly_budget` decimal(15,2) DEFAULT 0.00,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `deleted` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`parent_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 2. expense_entries
Table for storing individual expense records.

```sql
CREATE TABLE `expense_entries` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `expense_number` varchar(50) NOT NULL,
    `expense_date` date NOT NULL,
    `category_id` int(11) NOT NULL,
    `cost_center_id` int(11) NOT NULL,
    `amount` decimal(15,2) NOT NULL,
    `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
    `total_amount` decimal(15,2) NOT NULL,
    `description` text NOT NULL,
    `payment_method` enum('cash','bank','card') NOT NULL DEFAULT 'cash',
    `reference_number` varchar(100),
    `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `approved_by` int(11) DEFAULT NULL,
    `approved_at` timestamp NULL DEFAULT NULL,
    `rejected_by` int(11) DEFAULT NULL,
    `rejected_at` timestamp NULL DEFAULT NULL,
    `rejection_reason` text,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `deleted` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_expense_number` (`expense_number`),
    FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`),
    FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`),
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
    FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 3. recurring_expenses
Table for managing recurring/scheduled expenses.

```sql
CREATE TABLE `recurring_expenses` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category_id` int(11) NOT NULL,
    `description` text NOT NULL,
    `amount` decimal(15,2) NOT NULL,
    `frequency` enum('daily','weekly','monthly','quarterly','yearly') NOT NULL,
    `start_date` date NOT NULL,
    `end_date` date DEFAULT NULL,
    `last_generated` date DEFAULT NULL,
    `next_generation` date NOT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `deleted` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`),
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 4. production_order_expenses
Table for tracking expenses related to production orders.

```sql
CREATE TABLE `production_order_expenses` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `production_order_id` int(11) NOT NULL,
    `expense_entry_id` int(11) NOT NULL,
    `amount` decimal(15,2) NOT NULL,
    `description` text,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    FOREIGN KEY (`production_order_id`) REFERENCES `production_orders` (`id`),
    FOREIGN KEY (`expense_entry_id`) REFERENCES `expense_entries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## Related Tables

### 1. cost_centers
Table for managing cost centers that expenses can be allocated to.

```sql
CREATE TABLE `cost_centers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `code` varchar(20) NOT NULL,
    `description` text,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `deleted` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_cost_center_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## Audit and Logging Tables

### 1. expense_audit_log
Table for tracking changes to expense records.

```sql
CREATE TABLE `expense_audit_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `expense_id` int(11) NOT NULL,
    `action` enum('created','updated','deleted','approved','rejected') NOT NULL,
    `changes` json DEFAULT NULL,
    `performed_by` int(11) NOT NULL,
    `performed_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    FOREIGN KEY (`expense_id`) REFERENCES `expense_entries` (`id`),
    FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## Key Features of the Tables

1. **Data Integrity**
   - Foreign key constraints ensure referential integrity
   - Appropriate data types and lengths for each field
   - Enum types for status and other fixed-value fields
   - Unique constraints where necessary

2. **Audit Trail**
   - Created and updated timestamps on all tables
   - Soft delete functionality via `deleted` flag
   - Comprehensive audit logging
   - User tracking for all major actions

3. **Business Logic Support**
   - Status tracking for expenses (pending/approved/rejected)
   - Support for recurring expenses
   - Category hierarchy support
   - Cost center allocation
   - Tax handling
   - Multiple payment methods

4. **Performance Considerations**
   - Appropriate indexing on frequently queried fields
   - Optimized data types for storage efficiency
   - JSON support for flexible audit logging

## Notes
1. All monetary amounts use DECIMAL(15,2) for precision
2. All tables use UTF8MB4 character set for proper Unicode support
3. InnoDB engine is used for transaction support
4. Timestamps are automatically managed for created_at/updated_at
5. Soft delete pattern is implemented across all tables 