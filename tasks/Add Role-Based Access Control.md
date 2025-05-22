

---

### ✅ Cursor AI Prompt: Add Role-Based Access Control


 Add role-based access control to our existing PHP production management system. We already have these tables:
 
 `permissions(permission_id, permission_name, description, module)`
 `user_roles(role_id, role_name, description, created_at)`
 `role_permissions(role_id, permission_id)`
 `users(user_id, ..., role_id, ...)`
 
 🧠 Instructions:
 
 1. Create a new file `include/permission.php`. This file will:
     - Start a session if needed
     - Include the DB connection and functions
     - Check if the current user has permission to access this page
     - Block unauthorized users with a simple message or redirect
 
 2. Create/Update `include/functions.php` and implement:
 
 ```php
 function has_permission($user_id, $permission_name, $conn) {
     $sql = "SELECT 1
             FROM users u
             JOIN user_roles r ON u.role_id = r.role_id
             JOIN role_permissions rp ON r.role_id = rp.role_id
             JOIN permissions p ON rp.permission_id = p.permission_id
             WHERE u.user_id = ? AND p.permission_name = ?
             LIMIT 1";
 
     $stmt = $conn->prepare($sql);
     $stmt->bind_param('is', $user_id, $permission_name);
     $stmt->execute();
     $stmt->store_result();
     return $stmt->num_rows > 0;
 }
 ```
 
 3. On each PHP page in the project:
     - Add the following line at the top of the file:
       php
     include('include/permission.php');
     ```
     - Then define the required permission for that page:
     ```php
     $current_page_permission = 'module_name.action'; // Example: "products.view" or "sales.create"
     ```
     - `permission.php` should check the user's session, load the current user ID, and validate permission using `has_permission()`. If the permission fails, show a friendly access denied message or redirect.
 
 4. Do not change or break any existing functionality.
 5. Do not update or alter existing database tables.
 6. Do not hardcode any user or role. Make sure to use session values and dynamic checks only.
 
 Bonus (optional):
 - Log unauthorized attempts or keep a simple audit if needed.
 - Include `account_id` support for multi-tenant permission checking if necessary.
 ```
---

### ✅ Optional Enhancements for Your Dev Notes

- Every permission should follow the naming: `module.action`, like:
  - `product.view`, `product.create`, `sales.report`, `expense.upload`
- You can add an admin UI later to assign permissions to roles visually (Checkbox matrix).
- Add a default role like `Viewer` with only basic read permissions.

---
