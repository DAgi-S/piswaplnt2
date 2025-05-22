# User Management Documentation

## Overview
The user management module provides a comprehensive interface for managing system users, their roles, and access permissions. It allows administrators to create, edit, and delete user accounts, assign roles, and manage user statuses.

## File Structure

```
├── user.php                      # Main user management interface
├── custom/js/
│   └── user.js                  # Frontend JavaScript functionality
├── php_action/
│   ├── createUser.php           # Handle user creation
│   ├── updateUser.php           # Handle user updates
│   ├── deleteUser.php           # Handle user deletion
│   └── fetchUsers.php           # Fetch all users
```

## Database Structure

### Users Table (`users`)
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    profile_image VARCHAR(255),
    phone VARCHAR(20),
    language VARCHAR(10) DEFAULT 'en',
    timezone VARCHAR(50) DEFAULT 'UTC',
    dashboard_preferences TEXT,
    account_id INT,
    telegram_chat_id VARCHAR(50),
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id)
);
```

### User Roles Table (`user_roles`)
```sql
CREATE TABLE user_roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Frontend Components

### User Management Interface (user.php)
- Main interface for managing users
- Features:
  - User listing with DataTables
  - Add/Edit user modal
  - Delete user confirmation
  - Role assignment
  - Status management
  - User details view

### JavaScript Module (user.js)
```javascript
// Core Functions
- loadUsers()                    // Fetch and display users
- createUser()                   // Create new user
- updateUser()                   // Update existing user
- deleteUser()                   // Delete user
- toggleUserStatus()             // Toggle user active/inactive status

// Event Handlers
- User form submission
- Role selection
- Status updates
- Modal interactions
```

## Backend API Endpoints

### User Management APIs
1. Create User
   ```
   POST: php_action/createUser.php
   Payload: {
       username: string,
       email: string,
       password: string,
       role_id: number,
       full_name: string,
       phone: string,
       language: string,
       timezone: string
   }
   Response: {
       success: boolean,
       messages: string,
       user_id: number
   }
   ```

2. Update User
   ```
   POST: php_action/updateUser.php
   Payload: {
       user_id: number,
       username: string,
       email: string,
       password: string (optional),
       role_id: number,
       full_name: string,
       phone: string,
       language: string,
       timezone: string
   }
   Response: {
       success: boolean,
       messages: string
   }
   ```

3. Delete User
   ```
   POST: php_action/deleteUser.php
   Payload: {
       user_id: number
   }
   Response: {
       success: boolean,
       messages: string
   }
   ```

4. Fetch Users
   ```
   GET: php_action/fetchUsers.php
   Response: {
       success: boolean,
       data: Array<{
           user_id: number,
           username: string,
           email: string,
           full_name: string,
           role_name: string,
           status: number,
           last_login: string
       }>
   }
   ```

## Features

### User Management
1. User Creation
   - Username and email validation
   - Password hashing
   - Role assignment
   - Profile information
   - Language and timezone settings

2. User Editing
   - Profile updates
   - Role changes
   - Password reset
   - Status management
   - Preference updates

3. User Deletion
   - Confirmation dialog
   - Data cleanup
   - Related records handling

4. Status Management
   - Active/Inactive toggle
   - Last login tracking
   - Account status monitoring

## Security Measures

### Access Control
- Permission-based access control
- Role-based restrictions
- Password encryption
- Session management

### Data Validation
```php
// User validation
if(empty($username) || empty($email) || empty($password)) {
    return false;
}

// Role validation
if(!is_numeric($roleId)) {
    return false;
}
```

### SQL Injection Prevention
- Prepared statements
- Parameter binding
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

### User Management
1. User Creation
   - Validate all required fields
   - Check username/email uniqueness
   - Enforce password policies
   - Set appropriate roles

2. User Updates
   - Validate changes
   - Maintain audit trail
   - Handle password changes securely
   - Update related records

3. User Deletion
   - Confirm deletion
   - Handle related data
   - Maintain system integrity
   - Document changes

### Data Management
1. Regular backups
2. Data validation
3. Error logging
4. Audit trail maintenance

## Implementation Examples

### User Creation
```javascript
function createUser() {
    const formData = {
        username: $('#username').val(),
        email: $('#email').val(),
        password: $('#password').val(),
        role_id: $('#role_id').val(),
        full_name: $('#full_name').val(),
        phone: $('#phone').val(),
        language: $('#language').val(),
        timezone: $('#timezone').val()
    };
    
    $.ajax({
        url: 'php_action/createUser.php',
        method: 'POST',
        data: formData,
        success: function(response) {
            // Handle response
        }
    });
}
```

### User Update
```javascript
function updateUser() {
    const formData = {
        user_id: $('#editUserId').val(),
        username: $('#editUsername').val(),
        email: $('#editEmail').val(),
        password: $('#editPassword').val(),
        role_id: $('#editRoleId').val(),
        full_name: $('#editFullName').val(),
        phone: $('#editPhone').val(),
        language: $('#editLanguage').val(),
        timezone: $('#editTimezone').val()
    };
    
    $.ajax({
        url: 'php_action/updateUser.php',
        method: 'POST',
        data: formData,
        success: function(response) {
            // Handle response
        }
    });
}
```

## Troubleshooting

### Common Issues
1. User Creation Failures
   - Check required fields
   - Verify username/email uniqueness
   - Validate password strength
   - Check role existence

2. Update Issues
   - Verify user existence
   - Check permission levels
   - Validate input data
   - Check related records

3. Deletion Problems
   - Verify user existence
   - Check dependencies
   - Validate permissions
   - Check system integrity

## Maintenance

### Regular Tasks
1. User account review
2. Role assignment audit
3. Password policy enforcement
4. Inactive account cleanup
5. Security updates

### Database Maintenance
1. Regular backups
2. Index optimization
3. Data cleanup
4. Performance monitoring 