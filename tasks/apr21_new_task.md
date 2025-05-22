new_task.md

---

### ✅ **Cursor AI Prompt: Add Role-Based Permission Check in PHP (No SQL Functions)**

```
Create a reusable PHP-based permission check system for our production management platform. Follow these strict rules and guidelines:

🔒 System Context:
- We cannot use SQL functions or procedures because our MySQL server (cPanel shared hosting) does not allow SUPER privilege or DEFINER.
- Our database has existing tables: `users`, `roles`, `permissions`, `role_permissions` with proper relations.
- Each user has a `role_id`, and roles are linked to permissions via `role_permissions`.

📁 TASKS:
1. Create a file called `include/functions.php` if it doesn't exist.
2. Inside it, define this PHP function:

```php
function has_permission($user_id, $permission_name, $conn) {
    $sql = "SELECT 1 
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            JOIN role_permissions rp ON r.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE u.user_id = ? 
            AND p.permission_name = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $user_id, $permission_name);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}
```

3. Also create an optional helper function `check_access()` inside `include/helpers.php` or similar file:

```php
function check_access($permission) {
    global $conn;
    return has_permission($_SESSION['user_id'], $permission, $conn);
}
```

4. The functions should **not modify the database structure**, just rely on existing `users`, `roles`, `permissions`, and `role_permissions` tables.

5. Add usage examples in a comment block at the top of each function.

✅ Constraints:
- Do not alter existing database tables.
- Do not use SQL `FUNCTION`, `PROCEDURE`, or `DEFINER`.
- Do not use external packages. Pure PHP only.
- Add a single-line usage example comment in every file you create.

🎯 Goal:
Enable developers to perform role-based access control using a one-line call like:

```php
if (check_access('dashboard.view')) {
    // allow
} else {
    // access denied
}
```

```

---
