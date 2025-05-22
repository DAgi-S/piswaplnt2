# System Configuration Documentation

## Database Structure

### Categories Table (`system_config_categories`)
- Primary Key: `category_id` (auto-increment)
- Fields:
  - `category_name` (varchar(50)) - Unique name of the category
  - `description` (text) - Category description
  - `display_order` (int) - Order for display in UI
  - `is_active` (tinyint) - Category status
  - `created_at` (timestamp) - Creation timestamp
  - `updated_at` (timestamp) - Last update timestamp

### Settings Table (`system_config_settings`)
- Primary Key: `setting_id` (auto-increment)
- Fields:
  - `category_id` (int) - Foreign key to categories
  - `setting_key` (varchar(100)) - Unique setting identifier
  - `setting_value` (text) - Configuration value
  - `setting_type` (varchar(20)) - Data type (text, number, email, password)
  - `display_name` (varchar(100)) - User-friendly name
  - `description` (text) - Setting description
  - `validation_rules` (varchar(255)) - Input validation rules
  - `is_encrypted` (tinyint) - Whether value should be encrypted
  - `is_active` (tinyint) - Setting status
  - `created_at` (timestamp) - Creation timestamp
  - `updated_at` (timestamp) - Last update timestamp
  - `created_by` (int) - User ID who created
  - `updated_by` (int) - User ID who last updated

### History Table (`system_configuration_history`)
- Primary Key: `history_id` (auto-increment)
- Fields:
  - `setting_id` (int) - Foreign key to settings
  - `old_value` (text) - Previous value
  - `new_value` (text) - New value
  - `changed_by` (int) - User ID who made change
  - `change_type` (enum) - Type of change (create/update/delete)
  - `change_reason` (text) - Reason for change
  - `created_at` (timestamp) - Change timestamp

#Table structure related to system

system_config_settings
    setting_id 	category_id 	setting_key 	setting_value 	data_type 	display_name 	description 	is_required 	validation_rules 	default_value 	created_at 	updated_at 	

system_integration_logs
 	id 	integration_type 	status 	request_data 	response_data 	error_message 	created_at 	

system_config_categories
    category_id 	category_name 	description 	display_order 	created_at 	updated_at 	

system_configuration_history
    id 	config_key 	old_value 	new_value 	changed_by 	created_at 	

system_backup_logs
    id 	backup_date 	backup_type 	file_path 	status 	size_in_bytes 	created_at 	

system_audit_trails
    id 	user_id 	action_type 	table_name 	record_id 	old_values 	new_values 	ip_address 	created_at 	



## Configuration Categories

1. Email Configuration (display_order: 1)
   - mail_server: SMTP server address (default: smtp.gmail.com)
   - mail_port: SMTP port (default: 587)
   - mail_username: SMTP username
   - mail_password: SMTP password (encrypted)
   - mail_encryption: TLS/SSL setting
   - mail_from_address: Default sender email
   - mail_from_name: Default sender name

2. Database Configuration (display_order: 2)
   - db_host: Database host
   - db_name: Database name
   - db_user: Database username
   - db_password: Database password (encrypted)

3. Backup Configuration (display_order: 3)
   - backup_dir: Backup directory path
   - backup_frequency: Backup interval in hours
   - keep_backups: Number of backups to retain

4. Maintenance Configuration (display_order: 4)
   - maintenance_mode: Enable/disable maintenance mode
   - maintenance_message: Custom maintenance message

5. Security Configuration (display_order: 5)
   - Pending implementation

6. Notification Configuration (display_order: 6)
   - Pending implementation

7. Integration Configuration (display_order: 7)
   - Pending implementation

## Implementation Status

### Completed Features
- ✓ Basic database structure
- ✓ Email configuration interface
- ✓ Database configuration interface
- ✓ Backup configuration interface
- ✓ Maintenance mode interface
- ✓ Permission-based access control
- ✓ Configuration value encryption
- ✓ Change history logging
- ✓ Form validation
- ✓ AJAX-based configuration testing

### Pending Features
- Security configuration implementation
- Notification system setup
- Integration settings
- Real-time validation
- Configuration import/export
- Backup automation
- Email notification system
- Configuration templates

## Security Features
- Role-based access control
- Encrypted sensitive data
- Change history tracking
- Input validation
- CSRF protection
- Session management

## Notes
- All sensitive configuration values are encrypted
- Changes are logged in history table
- UI includes testing capabilities for email and database
- Backup system includes manual and scheduled options
- Maintenance mode affects all non-admin users