# Expense Management Table Structure

## Overview
This document details the database structure for the expense management module. All tables use InnoDB engine and UTF8MB4 character set for proper Unicode support.

## Tables

### expense_categories
Manages expense categories and their budget limits.

```sql
CREATE TABLE expense_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    budget_limit DECIMAL(15,2) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    UNIQUE KEY unique_category_name (name, deleted),
    INDEX idx_created_by (created_by),
    INDEX idx_updated_by (updated_by),
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### Fields
- `category_id`: Unique identifier for the category
- `name`: Category name (unique when not deleted)
- `description`: Optional category description
- `budget_limit`: Maximum budget allocated for this category
- `is_active`: Category status (1=active, 0=inactive)
- `deleted`: Soft delete flag
- `created_at`: Record creation timestamp
- `updated_at`: Last update timestamp
- `created_by`: User who created the category
- `updated_by`: User who last updated the category

### expense_entries
Stores individual expense records.

```sql
CREATE TABLE expense_entries (
    expense_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    expense_date DATE NOT NULL,
    description TEXT,
    amount DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    notes TEXT,
    attachment VARCHAR(255),
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    INDEX idx_category (category_id),
    INDEX idx_created_by (created_by),
    INDEX idx_updated_by (updated_by),
    FOREIGN KEY (category_id) REFERENCES expense_categories(category_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### Fields
- `expense_id`: Unique identifier for the expense
- `category_id`: Reference to expense category
- `expense_date`: Date of the expense
- `description`: Expense description
- `amount`: Expense amount
- `payment_method`: Method of payment (cash, bank, card)
- `status`: Current status (pending, approved, rejected)
- `notes`: Additional notes
- `attachment`: Path to attached file
- `is_deleted`: Soft delete flag
- `created_at`: Record creation timestamp
- `updated_at`: Last update timestamp
- `created_by`: User who created the expense
- `updated_by`: User who last updated the expense

### expense_audit_logs
Tracks changes to expense records.

```sql
CREATE TABLE expense_audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    changes JSON,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### Fields
- `log_id`: Unique identifier for the log entry
- `user_id`: User who made the change
- `action`: Type of action (create, update, delete)
- `table_name`: Name of the affected table
- `record_id`: ID of the affected record
- `changes`: JSON object containing old and new values
- `created_at`: When the change occurred

## Permissions
The following permissions are defined for expense management:

```sql
INSERT INTO permissions (permission_name, description, module) VALUES
('create_expense', 'Create new expense entries', 'expense'),
('edit_expense', 'Edit existing expense entries', 'expense'),
('delete_expense', 'Delete expense entries', 'expense'),
('view_expense', 'View expense entries', 'expense'),
('approve_expense', 'Approve expense entries', 'expense'),
('manage_expense_categories', 'Manage expense categories', 'expense'),
('view_expense_reports', 'View expense reports and analytics', 'expense');
```

## Relationships
- `expense_entries.category_id` → `expense_categories.category_id`
- `expense_categories.created_by` → `users.user_id`
- `expense_categories.updated_by` → `users.user_id`
- `expense_entries.created_by` → `users.user_id`
- `expense_entries.updated_by` → `users.user_id`
- `expense_audit_logs.user_id` → `users.user_id`

## Indexes
- Primary keys on all ID fields
- Foreign key indexes for all relationships
- Unique index on category name (with deleted flag)
- Index on expense date for date range queries
- Index on status for filtering

## Notes
1. All monetary values use DECIMAL(15,2) for precision
2. Soft delete implemented on both categories and entries
3. Full audit trail maintained through audit_logs
4. File attachments stored as paths for efficiency
5. JSON type used for flexible change tracking 