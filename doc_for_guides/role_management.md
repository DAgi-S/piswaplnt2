# Role Management Documentation

## Overview
The role management system provides a comprehensive interface for managing user roles and their associated permissions in the application. It allows administrators to create, edit, and delete roles, as well as assign specific permissions to each role.

## File Structure

```
├── role_management.php              # Main role management interface
├── custom/js/
│   └── role_management.js          # Frontend JavaScript functionality
├── php_action/
│   ├── createRole.php              # Handle role creation
│   ├── updateRole.php              # Handle role updates
│   ├── deleteRole.php              # Handle role deletion
│   ├── fetchRoles.php              # Fetch all roles
│   ├── fetchRolePermissions.php    # Fetch permissions for a role
│   └── updateRolePermissions.php   # Update role permissions
```

## Database Structure

### Roles Table (`user_roles`)
```sql
CREATE TABLE user_roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Role Permissions Table (`role_permissions`)
```sql
CREATE TABLE role_permissions (
    role_id INT,
    permission_id INT,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
);
```

## Frontend Components

### Role Management Interface (role_management.php)
- Main interface for managing roles and permissions
- Features:
  - Role listing
  - Add/Edit role modal
  - Delete role confirmation modal
  - Permission assignment interface
  - Module-wise permission grouping
  - Select all permissions functionality

### JavaScript Module (role_management.js)
```javascript
// Core Functions
- loadRoles()                    // Fetch and display all roles
- loadRolePermissions(roleId)    // Load permissions for selected role
- createRole()                   // Create new role
- updateRole()                   // Update existing role
- deleteRole()                   // Delete role
- savePermissions()             // Save role permissions
- toggleAllPermissions()        // Toggle all permission checkboxes

// Event Handlers
- Role selection
- Permission checkbox changes
- Modal interactions
- Form submissions
```

## Backend API Endpoints

### Role Management APIs
1. Create Role
   ```
   POST: php_action/createRole.php
   Payload: {
       role_name: string,
       description: string
   }
   Response: {
       success: boolean,
       messages: string,
       role_id: number
   }
   ```

2. Update Role
   ```
   POST: php_action/updateRole.php
   Payload: {
       role_id: number,
       role_name: string,
       description: string
   }
   Response: {
       success: boolean,
       messages: string
   }
   ```

3. Delete Role
   ```
   POST: php_action/deleteRole.php
   Payload: {
       role_id: number
   }
   Response: {
       success: boolean,
       messages: string
   }
   ```

4. Fetch Roles
   ```
   GET: php_action/fetchRoles.php
   Response: {
       success: boolean,
       data: Array<{
           role_id: number,
           role_name: string,
           description: string
       }>
   }
   ```

5. Fetch Role Permissions
   ```
   GET: php_action/fetchRolePermissions.php?role_id={role_id}
   Response: {
       success: boolean,
       data: Array<{
           permission_id: number,
           permission_name: string
       }>
   }
   ```

6. Update Role Permissions
   ```
   POST: php_action/updateRolePermissions.php
   Payload: {
       role_id: number,
       permissions: Array<number>  // Array of permission IDs
   }
   Response: {
       success: boolean,
       messages: string
   }
   ```

## Permission Groups
The interface organizes permissions into logical groups:
1. Dashboard
   - `view_dashboard` - View dashboard
   - `view_analytics` - View analytics

2. Product Management
   - `view_product` - View products
   - `create_product` - Create product
   - `edit_product` - Edit product
   - `delete_product` - Delete product
   - `manage_brands` - Manage brands
   - `manage_categories` - Manage categories

3. Invoice Management
   - `view_invoice` - View invoices
   - `create_invoice` - Create invoice
   - `edit_invoice` - Edit invoice
   - `delete_invoice` - Delete invoice
   - `manage_quotations` - Manage quotations
   - `manage_suppliers` - Manage suppliers
   - `manage_purchases` - Manage purchases

4. GPS Letter
   - `view_letter` - Base permission for viewing letters
   - `view_gps_letter` - View and generate GPS letters
   - `create_letter` - Create letter
   - `edit_letter` - Edit letter
   - `delete_letter` - Delete letter
   - `manage_templates` - Manage templates

5. Digital Swap
   - `digitalswap` - Base permission for digital swap module
   - `manage_swaps` - Manage swaps
   - `view_digitalswap` - View digital swap transactions
   - `create_digitalswap` - Create digital swap transactions
   - `view_digitalswap_reports` - View digital swap reports
   - `view_digitalswap_report` - View detailed digital swap report
   - `view_digitalswap_analytics` - View digital swap analytics
   - `view_swap_analytics` - View analytics
   - `view_swap_reports` - View reports
   - `edit_swap` - Edit swap
   - `delete_swap` - Delete swap

6. Account Management
   - `view_accounts` - View accounts
   - `manage_accounts` - Manage accounts
   - `view_transactions` - View transactions
   - `manage_transactions` - Manage transactions
   - `view_audit_report` - View audit report
   - `view_annual_report` - View annual report

7. User Management
   - `view_user` - View users
   - `create_user` - Create user
   - `edit_user` - Edit user
   - `delete_user` - Delete user
   - `manage_roles` - Manage roles

8. Settings
   - `manage_settings` - General settings
   - `manage_email_settings` - Email settings
   - `manage_backup` - Manage backup
   - `import_data` - Import data
   - `export_data` - Export data
   - `view_reports` - View reports

9. GPS Business
   - `manage_business_cycles` - Manage business cycles
   - `manage_profit_distribution` - Manage profit distribution
   - `view_business_reports` - View business reports

10. Production
    - `access_production_dashboard` - Access production dashboard
    - `view_production_orders` - View production orders
    - `create_production_order` - Create production order
    - `edit_production_order` - Edit production order
    - `delete_production_order` - Delete production order
    - `manage_production_schedule` - Manage production schedule
    - `view_production_analytics` - View production analytics
    - `manage_bom` - Manage Bill of Materials
    - `manage_quality_control` - Manage quality control
    - `manage_production_waste` - Manage production waste

11. Guest Management
    - `view_guests` - View guest list and details
    - `manage_guests` - Manage guest accounts and access
    - `view_guest_transactions` - View guest transaction history
    - `view_guest_reports` - View guest reports
    - `create_guest_account` - Create guest accounts
    - `edit_guest_account` - Edit guest accounts
    - `delete_guest_account` - Delete guest accounts
    - `manage_guest_permissions` - Manage guest permissions

## Role Types

### Header Management Roles
- Header Administrator
  - Full access to all header management features
  - Permissions: `manage_headers`, `view_headers`, `create_headers`, `edit_headers`, `delete_headers`
  - Access to all module-specific header permissions
- Header Editor
  - Can view and edit headers but cannot delete
  - Permissions: `view_headers`, `edit_headers`
  - Limited module-specific permissions based on assignment
- Header Viewer
  - Can only view header configurations
  - Permissions: `view_headers`
  - Read-only access to assigned module headers

## Permission Assignment

### Header Permission Assignment
1. Base Header Permissions
   - Assign base permissions (`manage_headers`, `view_headers`, etc.) through role configuration
   - These control overall access to header management functionality

2. Module-Specific Header Permissions
   - Configure through `header_permissions` table
   - Each role can have different permissions for different header types
   - Permissions are granular (view/edit/delete) per header type

3. Header Access Workflow
   - System checks base permissions first
   - Then validates module-specific permissions
   - Enforces least-privilege principle
   - Logs all header management actions

## Role Management Best Practices

### Header Management Guidelines
1. Limit full header management access to trusted administrators
2. Use module-specific permissions for departmental header management
3. Regularly audit header permissions and access logs
4. Implement change approval process for critical header modifications
5. Document all custom header configurations and permissions

## Security Measures

### Access Control
- Only users with `manage_roles` permission can access the role management interface
- Server-side validation for all role operations
- Permission checks before executing any role-related action

### Data Validation
```php
// Role name validation
if(empty($roleName) || strlen($roleName) < 3 || strlen($roleName) > 50) {
    return false;
}

// Permission validation
foreach($permissions as $permissionId) {
    if(!is_numeric($permissionId)) {
        return false;
    }
}
```

### SQL Injection Prevention
- Use of prepared statements
- Parameter binding for all database operations
- Input sanitization

## Error Handling
```php
try {
    // Database operations
} catch(PDOException $e) {
    // Log error
    // Return user-friendly message
}
```

## Best Practices

### Role Management
1. Default Roles
   - Super Admin (role_id: 1)
   - Admin (role_id: 2)
   - User (role_id: 3)
   - Production Manager (role_id: 5)
   - Production Supervisor (role_id: 6)
   - Production Operator (role_id: 7)

2. Role Creation Guidelines
   - Use descriptive names
   - Provide clear descriptions
   - Follow principle of least privilege
   - Regular audit of role permissions

### Permission Assignment
1. Module-based grouping
2. Hierarchical permissions
3. Regular permission review
4. Documentation of permission changes

## Implementation Examples

### Adding a New Permission Group
```php
<!-- New Permission Group -->
<div class="permission-group">
    <h5>Group Name</h5>
    <div class="permission-items">
        <div class="permission-item">
            <input type="checkbox" name="permissions[]" value="permission_name">
            Permission Label
        </div>
    </div>
</div>
```

### Role Creation
```javascript
function createRole() {
    const roleName = $('#roleName').val();
    const description = $('#roleDescription').val();
    
    $.ajax({
        url: 'php_action/createRole.php',
        method: 'POST',
        data: {
            roleName: roleName,
            description: description
        },
        success: function(response) {
            // Handle response
        }
    });
}
```

## Troubleshooting

### Common Issues
1. Permission Changes Not Saving
   - Check database connection
   - Verify permission IDs exist
   - Check for transaction rollbacks

2. Role Creation Failures
   - Validate role name uniqueness
   - Check character length limits
   - Verify database constraints

3. Permission Loading Issues
   - Check role_permissions table integrity
   - Verify permission existence
   - Debug AJAX requests

## Maintenance

### Regular Tasks
1. Audit role assignments
2. Review unused permissions
3. Update permission documentation
4. Check for orphaned permissions
5. Validate role hierarchies

### Database Maintenance
1. Regular backups
2. Index optimization
3. Permission cache updates
4. Clean up unused roles 

## Security Considerations

### Header Security
1. Access Control
   - Implement strict role-based access control for headers
   - Use session validation for header management
   - Enforce IP restrictions for header management access

2. Audit Trail
   - Log all header modifications
   - Track permission changes
   - Monitor failed access attempts

## Implementation Notes
// ... existing code ... 