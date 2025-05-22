# SQL Changes Log

## March 19, 2024
- Checked all tables in pistocklntmarch database using command:
```sql
USE pistocklntmarch; SHOW TABLES;
```
- Database contains tables for:
  - Inventory management
  - Production management
  - Sales and purchases
  - Quality control
  - User management
  - System configurations
  - And more...

## March 22, 2024
- Verified all tables in pistocklntmarch database
- Confirmed presence of all tables including recently added print_settings and company_info
- Checked tables in pistocklntmarch database

## March 22, 2024
- Checked tables in pistocklntmarch database
  ```sql
  USE pistocklntmarch;
  SHOW TABLES;
  ```
- Created PHP file to handle print template creation with validation and database insertion
- Added created_at and created_by fields to track template creation details

## March 22, 2024
- Added created_by column to print_settings table
```sql
ALTER TABLE print_settings 
ADD COLUMN created_by INT DEFAULT NULL AFTER created_at,
ADD FOREIGN KEY (created_by) REFERENCES users(id);
```

```sql
-- Add grand_total column to sales_orders table
ALTER TABLE sales_orders 
ADD COLUMN grand_total DECIMAL(15,2) AS (total_amount - withholding_amount) STORED AFTER withholding_amount;

-- Update existing orders to calculate grand_total
UPDATE sales_orders 
SET balance = grand_total - paid_amount 
WHERE id > 0;

CREATE TABLE IF NOT EXISTS print_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(50) NOT NULL,
    page_size VARCHAR(20) DEFAULT 'A4',
    orientation VARCHAR(20) DEFAULT 'portrait',
    margin_top INT DEFAULT 20,
    margin_right INT DEFAULT 20,
    margin_bottom INT DEFAULT 20,
    margin_left INT DEFAULT 20,
    font_family VARCHAR(50) DEFAULT 'Arial',
    font_size INT DEFAULT 12,
    header_alignment VARCHAR(20) DEFAULT 'center',
    logo_path VARCHAR(255) DEFAULT NULL,
    logo_width INT DEFAULT 150,
    footer_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_template (template_name)
);

CREATE TABLE IF NOT EXISTS company_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    address TEXT,
    contact_number VARCHAR(50),
    email VARCHAR(100),
    website VARCHAR(100),
    tax_id VARCHAR(50),
    logo_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add print settings permissions
INSERT INTO permissions (permission_name, description, module) VALUES
('settings.print.manage', 'Manage print layout and print settings', 'Settings');

-- Assign permission to admin role and users with settings access
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, permission_id FROM permissions WHERE permission_name = 'settings.print.manage';

INSERT INTO role_permissions (role_id, permission_id)
SELECT DISTINCT rp.role_id, p2.permission_id
FROM role_permissions rp
JOIN permissions p1 ON rp.permission_id = p1.permission_id
JOIN permissions p2 ON p2.permission_name = 'settings.print.manage'
WHERE p1.permission_name IN ('settings.access', 'settings_access', 'system.settings.access');
```

## 2024-03-21
- Updated order permissions and role mappings:
```sql
ALTER TABLE users ADD COLUMN order_permission INT DEFAULT 0;
UPDATE users SET order_permission = 1 WHERE user_role = 1;
```

## March 21, 2024
- Modified stock movement history query to include production order details:
```sql
SELECT 
    sm.*,
    CASE 
        WHEN sm.reference_type = 'production' THEN po.production_id 
        WHEN sm.reference_type = 'purchase' THEN p.purchase_id 
        ELSE NULL 
    END as reference_number,
    CASE 
        WHEN sm.reference_type = 'production' THEN CONCAT('Production Order #', po.production_id)
        WHEN sm.reference_type = 'purchase' THEN CONCAT('Purchase Order #', p.purchase_id)
        ELSE NULL 
    END as reference_details
FROM stock_movements sm
LEFT JOIN production_orders po ON sm.reference_id = po.id AND sm.reference_type = 'production'
LEFT JOIN purchases p ON sm.reference_id = p.id AND sm.reference_type = 'purchase'
WHERE sm.item_id = ?
ORDER BY sm.created_at DESC
``` 

```sql
INSERT INTO permissions (permission_name, description, module) 
VALUES 
('settings.categories.manage', 'Manage product and service categories', 'Settings'), 
('settings.brands.manage', 'Manage product brands', 'Settings'), 
('settings.units.manage', 'Manage measurement units', 'Settings'), 
('settings.tax.manage', 'Manage tax settings', 'Settings'),
 ('settings.currency.manage', 'Manage currency settings', 'Settings'), 
 ('settings.company.manage', 'Manage company information', 'Settings');
```

## 2024-03-21
- Added system admin and settings permissions
- Added permissions: system.admin, admin.access, settings.access, settings.manage
- Assigned new permissions to admin role
- Updated admin user role assignment

## 2024-03-19
```sql
UPDATE units 
SET unit_name = '[new_name]',
    unit_abbreviation = '[new_abbreviation]',
    unit_description = '[new_description]',
    unit_active = '[new_status]',
    updated_at = CURRENT_TIMESTAMP
WHERE unit_id = [unit_id];
```

```sql
-- Create print_settings table
CREATE TABLE IF NOT EXISTS print_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(50) NOT NULL UNIQUE,
    page_size VARCHAR(20) NOT NULL DEFAULT 'A4',
    orientation VARCHAR(20) NOT NULL DEFAULT 'portrait',
    margin_top VARCHAR(20) NOT NULL DEFAULT '25mm',
    margin_right VARCHAR(20) NOT NULL DEFAULT '20mm',
    margin_bottom VARCHAR(20) NOT NULL DEFAULT '25mm',
    margin_left VARCHAR(20) NOT NULL DEFAULT '20mm',
    font_family VARCHAR(100) NOT NULL DEFAULT 'Arial, sans-serif',
    font_size VARCHAR(20) NOT NULL DEFAULT '12px',
    header_alignment VARCHAR(20) NOT NULL DEFAULT 'left',
    logo_path VARCHAR(255) DEFAULT 'assets/images/logo.png',
    logo_width VARCHAR(20) DEFAULT '150px',
    footer_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create company_info table
CREATE TABLE IF NOT EXISTS company_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(100),
    website VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default company info
INSERT INTO company_info (name, address, phone, email, website)
VALUES (
    'Your Company Name',
    '123 Business Street, City, Country',
    '+1234567890',
    'info@yourcompany.com',
    'www.yourcompany.com'
) ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    address = VALUES(address),
    phone = VALUES(phone),
    email = VALUES(email),
    website = VALUES(website);

-- Insert default print settings template
INSERT INTO print_settings (template_name)
VALUES ('default') ON DUPLICATE KEY UPDATE template_name = VALUES(template_name);
```

## 2024-03-21
```sql
-- Drop existing tables
DROP TABLE IF EXISTS guest_sessions;
DROP TABLE IF EXISTS guest_activity_log;
DROP TABLE IF EXISTS guest_account_links;
DROP TABLE IF EXISTS guest_login_attempts;
DROP TABLE IF EXISTS guest_users;

-- Create guest_users table
CREATE TABLE guest_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    guest_id VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100),
    account_name VARCHAR(100),
    currency VARCHAR(10) DEFAULT 'USD',
    balance DECIMAL(15,2) DEFAULT 0.00,
    linked_account_id VARCHAR(255),
    access_level JSON,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    expiry_date TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create guest_account_links table
CREATE TABLE guest_account_links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    guest_id INT,
    account_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guest_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create guest_activity_log table
CREATE TABLE guest_activity_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    guest_id INT,
    action VARCHAR(50),
    module VARCHAR(50),
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guest_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create guest_login_attempts table
CREATE TABLE guest_login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50),
    ip_address VARCHAR(45),
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create guest_sessions table
CREATE TABLE guest_sessions (
    session_id VARCHAR(255) PRIMARY KEY,
    guest_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guest_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 2023-07-19

- guest/setup_guest_tables.sql - Created tables for guest functionality including guest_users, guest_sessions, guest_activity_log, guest_login_attempts, and guest_account_links

## 2024-03-02

```sql
-- Modify linked_account_id column to support multiple accounts
ALTER TABLE guest_users MODIFY COLUMN linked_account_id VARCHAR(255);
```

## 2024-03-01
- Created warranty_certificates table
- Added warranty certificate permissions:
```sql
INSERT INTO permissions (permission_name, description, module) VALUES
('warranty_certificate', 'Access and manage warranty certificates', 'Warranty'),
('warranty_certificate.view', 'View warranty certificates', 'Warranty'),
('warranty_certificate.create', 'Create new warranty certificates', 'Warranty'),
('warranty_certificate.edit', 'Edit existing warranty certificates', 'Warranty'),
('warranty_certificate.delete', 'Delete warranty certificates', 'Warranty');

-- Assign permissions to admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, permission_id FROM permissions 
WHERE permission_name LIKE 'warranty_certificate%';
```

## March 2024

### March 1, 2024
```sql
-- Added warranty permissions
INSERT INTO permissions (permission_name, description) VALUES
('view_warranty', 'Permission to view warranty certificates'),
('create_warranty', 'Permission to create warranty certificates'),
('manage_warranty', 'Permission to manage warranty settings');

-- Created warranty certificates table
CREATE TABLE IF NOT EXISTS warranty_certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    product_id INT NOT NULL,
    certificate_number VARCHAR(50) UNIQUE NOT NULL,
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    terms TEXT,
    signature_path VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

## May 8, 2025
- Created user_activity_log table
```sql
CREATE TABLE IF NOT EXISTS user_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_description TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- Created warranty_certificates table
```sql
CREATE TABLE IF NOT EXISTS warranty_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certificate_number VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    services_provided TEXT NOT NULL,
    serial_reference VARCHAR(100) NOT NULL,
    invoice_date DATE NOT NULL,
    installation_date DATE NOT NULL,
    certificate_date DATE NOT NULL,
    warranty_duration INT NOT NULL,
    terms_conditions TEXT,
    authorized_by VARCHAR(100) NOT NULL,
    signature_image VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
