# Permissions Implementation Guide

## Table of Contents
1. [Overview](#overview)
2. [Permission Structure](#permission-structure)
3. [Implementation Steps](#implementation-steps)
4. [Code Examples](#code-examples)
5. [Best Practices](#best-practices)
6. [Permission Categories](#permission-categories)
7. [Legacy Support](#legacy-support)

## Overview

The permissions system implements a comprehensive role-based access control (RBAC) approach with granular permissions, supporting both modern hierarchical and legacy flat permission structures. The system is designed to be flexible, maintainable, and secure while supporting backward compatibility.

### Key Components
- Permissions table: Stores individual permissions
- Role permissions table: Maps permissions to roles
- User roles table: Associates users with roles
- Permission checking functions in PHP
- Legacy permission support system
- Permission-based conditional rendering
- Module-specific permission handlers
- Granular access control system

## Permission Structure

### Database Schema
```sql
-- Permissions table structure
CREATE TABLE permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(100) UNIQUE,
    description VARCHAR(255),
    module VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP
);

-- Role permissions mapping
CREATE TABLE role_permissions (
    role_id INT,
    permission_id INT,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id)
);

-- User roles mapping
CREATE TABLE user_roles (
    user_id INT,
    role_id INT,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);
```

### Permission Naming Convention
- Modern Format: `module.section.action`
  - Examples: 
    - `dashboard.analytics.sales`
    - `dashboard.inventory.stock_level`
    - `dashboard.financial.overview`
- Legacy Format: `action_resource`
  - Examples:
    - `view_dashboard`
    - `manage_inventory`
    - `edit_product`

## Implementation Steps

### 1. Initialize Permission Structure
Create a structured permissions array that groups related permissions:

```php
$permissions = array(
    // Core permissions
    'customize' => hasPermission('dashboard.customize'),
    'export' => hasPermission('dashboard.export'),
    
    // Module-specific permissions with legacy fallbacks
    'analytics' => array(
        'sales' => hasPermission('dashboard.analytics.sales') || hasPermission('view_analytics'),
        'inventory' => hasPermission('dashboard.analytics.inventory') || hasPermission('view_analytics'),
        'revenue' => hasPermission('dashboard.analytics.revenue') || hasPermission('view_revenue'),
        'export' => hasPermission('dashboard.analytics.export') || hasPermission('view_analytics')
    ),
    
    // Feature-specific permissions
    'inventory' => array(
        'stock_level' => hasPermission('dashboard.inventory.stock_level'),
        'movements' => hasPermission('dashboard.inventory.movements'),
        'alerts' => hasPermission('dashboard.inventory.alerts'),
        'low_stock' => hasPermission('dashboard.inventory.alerts') || hasPermission('view_low_stock')
    ),
    
    // Legacy permission mapping
    'legacy' => array(
        'view_dashboard' => hasPermission('view_dashboard'),
        'view_analytics' => hasPermission('view_analytics'),
        'view_revenue' => hasPermission('view_revenue')
    )
);
```

### 2. Implement Permission Checks
Create helper functions for permission checking:

```php
// Base permission check function
function hasPermission($permission_name) {
    global $connect;
    
    if (!isset($_SESSION['userId'])) {
        return false;
    }

    $sql = "SELECT 1
            FROM users u
            JOIN user_roles r ON u.role_id = r.role_id
            JOIN role_permissions rp ON r.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE u.user_id = ? AND p.permission_name = ?
            LIMIT 1";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param('is', $_SESSION['userId'], $permission_name);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

// Helper function for combined permission checks
function check_dashboard_permission($new_perm, $legacy_perm = null) {
    if ($legacy_perm) {
        return hasPermission($new_perm) || hasPermission($legacy_perm);
    }
    return hasPermission($new_perm);
}

// Multiple permission checks
function has_any_permission($permissions) {
    foreach ($permissions as $permission) {
        if (hasPermission($permission)) {
            return true;
        }
    }
    return false;
}

function has_all_permissions($permissions) {
    foreach ($permissions as $permission) {
        if (!hasPermission($permission)) {
            return false;
        }
    }
    return true;
}
```

### 3. Implement Access Control
Add permission checks at different levels:

```php
// Page-level access control
if (!hasPermission('dashboard.view') && !hasPermission('view_dashboard')) {
    $_SESSION['error'] = "You don't have permission to view the dashboard";
    header('Location: access_denied.php');
    exit();
}

// Feature-level access control
if ($permissions['analytics']['inventory'] || $permissions['inventory']['stock_level']) {
    // Load inventory analytics data
    $sql = "SELECT COUNT(*) as count FROM products WHERE status = 1";
    $result = $connect->query($sql);
    $countProduct = $result ? $result->fetch_assoc()['count'] : 0;
}

// Action-level access control
if ($permissions['customize']) {
    // Show customization options
}
```

### 4. Handle Unauthorized Access
Implement proper error handling and redirection:

```php
function handle_unauthorized_access($message = '') {
    // Log the unauthorized access attempt
    $user_id = isset($_SESSION['userId']) ? $_SESSION['userId'] : 'Guest';
    $page = $_SERVER['REQUEST_URI'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    
    error_log("Unauthorized access attempt - User: $user_id, Page: $page, IP: $ip, Time: $timestamp");
    
    // Set error message in session if provided
    if (!empty($message)) {
        $_SESSION['error'] = $message;
    }
    
    // Redirect to access denied page
    header('Location: ' . ROOT_URL . 'access_denied.php');
    exit();
}
```

## Best Practices

1. **Granular Permission Structure**
   - Create specific permissions for different sections and actions
   - Use hierarchical naming (module.section.action)
   - Group related permissions in structured arrays
   - Support both new and legacy permission formats
   - Implement module-specific permission handlers

2. **Security Implementation**
   - Check permissions before loading any data
   - Implement both data access and UI rendering checks
   - Never rely solely on hiding UI elements
   - Validate permissions server-side for all actions
   - Log unauthorized access attempts
   - Implement proper error handling and redirection

3. **Performance Optimization**
   - Initialize permissions array at the start
   - Cache permission results where appropriate
   - Use efficient database queries with proper indexing
   - Implement permission caching in session when appropriate
   - Only load data for sections user has access to

4. **Legacy Support**
   - Maintain backward compatibility with legacy permissions
   - Use OR conditions for permission checks
   - Group legacy permissions in a separate array
   - Document legacy permission mappings
   - Plan for permission system upgrades

5. **Maintainability**
   - Use helper functions for complex permission checks
   - Group related permissions logically
   - Document permission requirements for each section
   - Keep SQL scripts for permission management
   - Implement clear error messages and logging

6. **UI/UX Considerations**
   - Gracefully handle missing permissions
   - Show appropriate error messages
   - Maintain consistent UI despite missing sections
   - Consider user experience when hiding/showing features
   - Implement proper feedback for unauthorized actions

## Permission Categories

The system includes the following main permission categories:

1. **Dashboard Permissions**
   - Basic access: `dashboard.view`
   - Analytics: `dashboard.analytics.*`
   - Reports: `dashboard.reports.*`
   - Customization: `dashboard.customize`

2. **Module-Specific Permissions**
   - Products: `product.*`
   - Orders: `order.*`
   - Inventory: `inventory.*`
   - Finance: `finance.*`
   - Users: `user.*`

3. **Action-Based Permissions**
   - View: `*.view`
   - Create: `*.create`
   - Edit: `*.edit`
   - Delete: `*.delete`
   - Manage: `*.manage`

4. **Legacy Permissions**
   - `view_*`
   - `manage_*`
   - `edit_*`
   - `delete_*`

## Example Implementation Workflow

1. **Initialize Permissions**
```php
// Initialize permission structure
$permissions = array(
    'section' => array(
        'feature' => hasPermission('section.feature') || hasPermission('legacy_feature')
    )
);
```

2. **Check Access**
```php
// Check base access
if (!check_dashboard_permission('section.view', 'view_section')) {
    handle_unauthorized_access();
}
```

3. **Load Data**
```php
// Load data based on permissions
if ($permissions['section']['feature']) {
    // Load feature-specific data
}
```

4. **Render UI**
```php
<?php if($permissions['section']['feature']): ?>
    <!-- Render feature-specific UI -->
<?php endif; ?>
```

This guide reflects the actual implementation in the system, providing a comprehensive approach to permission management that supports both modern and legacy systems while maintaining security, performance, and usability. 