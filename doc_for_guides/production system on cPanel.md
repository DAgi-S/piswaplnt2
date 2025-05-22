Hosting and updating your **production system on cPanel** without using GitHub (or version control tools like Git) **requires extra caution** to avoid breaking the live app or losing client data. Since you're managing a live **Production Management System**, here's a solid deployment and update strategy that’s safe, fast, and realistic for your setup:

---

## ✅ Goals:
- Update **features, pages, tables, and code**.
- Avoid breaking the **live system**.
- No GitHub involved.
- Use only **cPanel**, FTP, phpMyAdmin, or File Manager.

---

## 🔐 Suggested Folder Structure

### 1. Keep a separate folder for **Staging**
On your server (via File Manager):

```
/public_html/
  ├── production/   <-- live system clients use this
  ├── staging/      <-- development & testing copy
```

---

## 🛠️ Workflow for Safe Updates (Without GitHub)

### Step 1: ✅ **Upload changes to `/staging` folder**
- Clone your live app into `/staging`
- Apply new features, pages, DB schema changes **here first**.
- Let your team or internal QA test it on `functions.com/staging/`.

---

### Step 2: 🧪 **Test Database Changes Safely**
- Create a **staging database** (e.g., `your_db_staging`)
- Use export/import via phpMyAdmin to mirror production DB
- Apply your **new table structures** to this staging DB
- Make sure no data loss or conflict

---

### Step 3: 🧾 Use a **Change Log / Update Script**
Create a `deploy_update.php` file or `deploy.md` that:

- Lists:
  - Which files to move
  - Which tables or queries to run
- Example:
```md
## Update 2025-04-21
- New: /expense/ module
- Updated: /dashboard.php
- DB:
  - CREATE TABLE `expenses` ...
  - ALTER TABLE `users` ADD `budget_limit` ...
```

---

### Step 4: 💣 **Backup Before Anything**
Before going live:

- Backup Files:
  - Download `/production` folder
- Backup Database:
  - Export `.sql` file from phpMyAdmin

> **NEVER skip this. One missed semi-colon could take the whole thing down.**

---

### Step 5: 🔁 **Update Production in Steps**

#### ✅ Option A: Manual File Upload
1. Download the changed files from local/staging
2. Upload only those via cPanel’s **File Manager** or FTP
3. Replace files **one by one** (or in batches) to avoid full downtime

#### ✅ Option B: Use a zip
1. Zip your update files
2. Upload to `/production`
3. Extract via File Manager

---

### Step 6: 🧮 Apply Database Changes
1. Open phpMyAdmin
2. Run **only new SQL queries**
3. Example:
```sql
CREATE TABLE IF NOT EXISTS `expenses` (...);
ALTER TABLE `users` ADD COLUMN `role_id` INT DEFAULT NULL;
```

> ✅ Use `IF NOT EXISTS` to avoid duplicate errors.

---

## 🧠 Pro Tips

### 🔐 Use Maintenance Mode (Optional)
Add a temporary `maintenance.html` and switch `.htaccess` to route users there during critical updates.

```apacheconf
# Maintenance redirect
RewriteEngine On
RewriteCond %{REMOTE_ADDR} !^123\.456\.789\.000  # your IP
RewriteRule ^(.*)$ /maintenance.html [R=302,L]
```

---

### ✅ Keep a `VERSION.md` File
Inside your `/production` folder, add:

```
# Version 1.3.2 – April 21, 2025
- Added expense module
- Fixed role permission issues
- Improved dashboard stats
```

---

### 📁 Folder Ideas to Track History

```
/changelog/
/backups/
/sql_updates/
```

---

## 🧩 Optional Tools (No GitHub Needed)
- **FileZilla**: For secure FTP transfer
- **LocalSync + FTP Deploy plugins** (for VSCode)
- **Simple deploy.php** script that logs uploads (can help automate)

---

## ✅ Summary

| Step | Task |
|------|------|
| 1 | Clone your site to `/staging/` |
| 2 | Test changes locally |
| 3 | Create change logs |
| 4 | Backup code and database |
| 5 | Upload changes manually or via zip |
| 6 | Apply SQL changes safely |
| 7 | Confirm and test live version |

---

Let me know if you want a ready-to-use `deploy_update.php` script or `.md` changelog template to automate this flow.