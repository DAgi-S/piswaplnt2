production_table_structure

database structure is quite comprehensive and well-organized. The database contains multiple tables that cover various aspects of the system including:
Production Management:
        production_orders, production_products, production_progress
        production_machine_maintenance, production_quality_checks
        production_waste_logs, production_shift_schedules
Inventory & Materials:
        raw_materials, raw_material_movements, raw_material_specifications
        bill_of_materials, inventory_adjustments
        warehouse_stock, warehouse_stock_movements
Quality Control:
        quality_control, quality_control_parameters
        quality_control_results, quality_defect_types
        quality_inspection_points
Sales & Orders:
        sales_orders, sales_order_items, sales_payments
        sales_status_history
Purchase Management:
        purchases, purchase_items, purchase_payments
        purchase_requisitions, purchase_approvals
User Management & Security:
        users, user_roles, role_permissions
        audit_logs, system_audit_trails
        user_notifications, user_dashboard_preferences
Business & Financial:
        gps_business_cycles, gps_profit, gps_payments
        gps_expenses, gps_currency_rates





PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; SHOW TABLES;"
+--------------------------------+
| Tables_in_pistocklntmarch      |
+--------------------------------+
| accounts                       |
| audit_log                      |
| audit_logs                     |
| bill_of_materials              |
| brands                         |
| categories                     |
| clients                        |
| company_settings               |
| currency_settings              |
| dashboard_available_components |
| digital_categories             |
| digital_transaction_categories |
| digitalswap                    |
| email_logs                     |
| generated_letters              |
| gps_balance_accounts           |
| gps_business_cycle_orders      |
| gps_business_cycle_sales       |
| gps_business_cycles            |
| gps_business_expense_history   |
| gps_business_expenses          |
| gps_credit_tracking            |
| gps_currency_rates             |
| gps_customers                  |
| gps_expense_categories         |
| gps_expenses                   |
| gps_investors                  |
| gps_orders                     |
| gps_payment_followup           |
| gps_payments                   |
| gps_profit                     |
| gps_profit_distributions       |
| gps_sales                      |
| guest_account_links            |
| guest_users                    |
| inventory_adjustments          |
| inventory_count_sessions       |
| inventory_locations            |
| inventory_movement_log         |
| inventory_transfer_logs        |
| letter_templates               |
| letter_vehicles                |
| material_allocations           |
| material_consumption           |
| ml_models                      |
| notification_channels          |
| notification_queue             |
| notification_templates         |
| notifications                  |
| operation_workstations         |
| operations                     |
| order_items                    |
| orders                         |
| permissions                    |
| platforms                      |
| prediction_metrics             |
| product_bom                    |
| product_routing                |
| product_settings               |
| production_brands              |
| production_categories          |
| production_machine_maintenance |
| production_order_materials     |
| production_orders              |
| production_predictions         |
| production_products            |
| production_progress            |
| production_quality_checks      |
| production_shift_schedules     |
| production_waste_logs          |
| products                       |
| purchase_approvals             |
| purchase_documents             |
| purchase_items                 |
| purchase_payment_terms         |
| purchase_payments              |
| purchase_requisitions          |
| purchases                      |
| quality_control                |
| quality_control_parameters     |
| quality_control_results        |
| quality_defect_types           |
| quality_inspection_points      |
| quotation_items                |
| quotations                     |
| raw_material_categories        |
| raw_material_movements         |
| raw_material_price_history     |
| raw_material_quality_checks    |
| raw_material_specifications    |
| raw_material_suppliers         |
| raw_materials                  |
| report_generation_logs         |
| report_schedules               |
| report_users                   |
| resource_predictions           |
| role_permissions               |
| sales_order_items              |
| sales_order_items_new          |
| sales_orders                   |
| sales_payments                 |
| sales_status_history           |
| schedule_predictions           |
| scheduled_reports              |
| sessions                       |
| settings                       |
| stock_movements                |
| storage_locations              |
| suppliers                      |
| system_audit_trails            |
| system_backup_logs             |
| system_configuration_history   |
| system_integration_logs        |
| tax_rates                      |
| transaction_types              |
| units                          |
| user_dashboard_preferences     |
| user_notification_preferences  |
| user_notifications             |
| user_roles                     |
| users                          |
| vw_reservation_status          |
| vw_stock_status                |
| warehouse_settings             |
| warehouse_stock                |
| warehouse_stock_movements      |
| warehouse_zones                |
| warehouses                     |
| workstation_assignments        |
| workstations                   |
+--------------------------------+


PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE purchases;"          
+-------------------------+---------------+------+-----+---------------------+-------------------------------+
| Field                   | Type          | Null | Key | Default             | Extra                         |
+-------------------------+---------------+------+-----+---------------------+-------------------------------+
| id                      | int(11)       | NO   | PRI | NULL                | auto_increment                |
| purchase_number         | varchar(50)   | NO   |     | NULL                |                               |
| supplier_id             | int(11)       | NO   | MUL | NULL                |                               |
| purchase_date           | date          | NO   |     | NULL                |                               |
| sub_total               | decimal(25,2) | NO   |     | NULL                |                               |
| vat                     | decimal(25,2) | NO   |     | NULL                |                               |
| vat_amount              | decimal(25,2) | NO   |     | 0.00                |                               |
| grand_total             | decimal(25,2) | NO   |     | NULL                |                               |
| note                    | text          | YES  |     | NULL                |                               |
| payment_status          | varchar(20)   | YES  |     | Unpaid              |                               |
| payment_date            | date          | YES  |     | NULL                |                               |
| last_payment_date       | date          | YES  |     | NULL                |                               |
| paid_amount             | decimal(25,2) | YES  |     | 0.00                |                               |
| status                  | int(11)       | YES  |     | 0                   |                               |
| active                  | int(11)       | YES  |     | 1                   |                               |
| created_by              | int(11)       | NO   | MUL | NULL                |                               |
| warehouse_id            | int(11)       | NO   |     | 1                   |                               |
| created_at              | timestamp     | NO   |     | current_timestamp() |                               |
| updated_at              | timestamp     | NO   |     | current_timestamp() | on update current_timestamp() |
| withholding_tax_enabled | tinyint(1)    | YES  |     | 0                   |                               |
| withholding_tax_amount  | decimal(25,2) | YES  |     | 0.00                |                               |
+-------------------------+---------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE suppliers;"
+----------------+--------------+------+-----+---------------------+-------------------------------+
| Field          | Type         | Null | Key | Default             | Extra                         |
+----------------+--------------+------+-----+---------------------+-------------------------------+
| id             | int(11)      | NO   | PRI | NULL                | auto_increment                |
| company_name   | varchar(255) | NO   |     | NULL                |                               |
| contact_person | varchar(255) | YES  |     | NULL                |                               |
| email          | varchar(255) | YES  |     | NULL                |                               |
| phone          | varchar(50)  | YES  |     | NULL                |                               |
| address        | text         | YES  |     | NULL                |                               |
| tin            | varchar(50)  | YES  |     | NULL                |                               |
| active         | int(11)      | YES  |     | 1                   |                               |
| created_at     | timestamp    | NO   |     | current_timestamp() |                               |
| updated_at     | timestamp    | NO   |     | current_timestamp() | on update current_timestamp() |
+----------------+--------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE purchase_items;"
+-----------------+---------------+------+-----+---------------------+-------------------------------+
| Field           | Type          | Null | Key | Default             | Extra                         |
+-----------------+---------------+------+-----+---------------------+-------------------------------+
| id              | int(11)       | NO   | PRI | NULL                | auto_increment                |
| purchase_id     | int(11)       | NO   | MUL | NULL                |                               |
| product_id      | int(11)       | NO   | MUL | NULL                |                               |
| raw_material_id | int(11)       | YES  | MUL | NULL                |                               |
| warehouse_id    | int(11)       | YES  | MUL | NULL                |                               |
| location_id     | int(11)       | YES  | MUL | NULL                |                               |
| quantity        | int(11)       | NO   |     | NULL                |                               |
| rate            | decimal(25,2) | NO   |     | NULL                |                               |
| total           | decimal(25,2) | NO   |     | NULL                |                               |
| created_at      | timestamp     | NO   |     | current_timestamp() |                               |
| updated_at      | timestamp     | NO   |     | current_timestamp() | on update current_timestamp() |
+-----------------+---------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march>


PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE users;"            
+-----------------------+--------------+------+-----+---------------------+-------------------------------+
| Field                 | Type         | Null | Key | Default             | Extra                         |
+-----------------------+--------------+------+-----+---------------------+-------------------------------+
| user_id               | int(11)      | NO   | PRI | NULL                | auto_increment                |
| username              | varchar(255) | NO   | UNI | NULL                |                               |
| email                 | varchar(255) | NO   |     | NULL                |                               |
| full_name             | varchar(100) | YES  |     | NULL                |                               |
| phone                 | varchar(20)  | YES  |     | NULL                |                               |
| profile_image         | varchar(255) | YES  |     | NULL                |                               |
| language              | varchar(10)  | YES  |     | en                  |                               |
| timezone              | varchar(100) | YES  |     | UTC                 |                               |
| notify_updates        | tinyint(1)   | YES  |     | 0                   |                               |
| notify_alerts         | tinyint(1)   | YES  |     | 0                   |                               |
| notify_reports        | tinyint(1)   | YES  |     | 0                   |                               |
| last_login            | datetime     | YES  |     | NULL                |                               |
| password              | varchar(255) | NO   |     | NULL                |                               |
| role_id               | int(11)      | YES  |     | NULL                |                               |
| status                | tinyint(1)   | YES  |     | 1                   |                               |
| created_at            | timestamp    | NO   |     | current_timestamp() |                               |
| updated_at            | timestamp    | NO   |     | current_timestamp() | on update current_timestamp() |
| dashboard_preferences | text         | YES  |     | NULL                |                               |
| account_id            | int(11)      | YES  | MUL | NULL                |                               |
+-----------------------+--------------+------+-----+---------------------+-------------------------------+




PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE clients;"
+--------------+--------------+------+-----+---------------------+-------------------------------+
| Field        | Type         | Null | Key | Default             | Extra                         |
+--------------+--------------+------+-----+---------------------+-------------------------------+
| id           | int(11)      | NO   | PRI | NULL                | auto_increment                |
| company_name | varchar(255) | NO   |     | NULL                |                               |
| tin_number   | varchar(50)  | YES  |     | NULL                |                               |
| address      | text         | YES  |     | NULL                |                               |
| phone        | varchar(50)  | YES  |     | NULL                |                               |
| email        | varchar(100) | YES  |     | NULL                |                               |
| status       | tinyint(1)   | YES  |     | 1                   |                               |
| created_at   | timestamp    | NO   |     | current_timestamp() |                               |
| updated_at   | timestamp    | NO   |     | current_timestamp() | on update current_timestamp() |
+--------------+--------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE sales_orders;"
+--------------------+------------------------------------------------------+------+-----+---------------------+-------------------------------+
| Field              | Type                                                 | Null | Key | Default             | Extra                         |
+--------------------+------------------------------------------------------+------+-----+---------------------+-------------------------------+
| id                 | int(11)                                              | NO   | PRI | NULL                | auto_increment                |
| order_number       | varchar(50)                                          | NO   | UNI | NULL                |                               |
| transaction_id     | varchar(50)                                          | YES  | UNI | NULL                |                               |
| client_id          | int(11)                                              | NO   | MUL | NULL                |                               |
| warehouse_id       | int(11)                                              | NO   |     | NULL                |                               |
| order_date         | date                                                 | NO   |     | NULL                |                               |
| delivery_date      | date                                                 | YES  |     | NULL                |                               |
| subtotal           | decimal(10,2)                                        | NO   |     | 0.00                |                               |
| tax_amount         | decimal(10,2)                                        | NO   |     | 0.00                |                               |
| discount_amount    | decimal(10,2)                                        | NO   |     | 0.00                |                               |
| withholding_amount | decimal(10,2)                                        | YES  |     | 0.00                |                               |
| total_amount       | decimal(10,2)                                        | NO   |     | 0.00                |                               |
| paid_amount        | decimal(10,2)                                        | NO   |     | 0.00                |                               |
| balance            | decimal(10,2)                                        | NO   |     | 0.00                |                               |
| payment_status     | enum('unpaid','partial','paid')                      | NO   |     | unpaid              |                               |
| order_status       | enum('pending','processing','completed','cancelled') | NO   |     | pending             |                               |
| notes              | text                                                 | YES  |     | NULL                |                               |
| created_by         | int(11)                                              | NO   | MUL | NULL                |                               |
| created_at         | datetime                                             | NO   |     | current_timestamp() |                               |
| updated_at         | datetime                                             | YES  |     | NULL                | on update current_timestamp() |
+--------------------+------------------------------------------------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE sales_order_items;"
+--------------------+---------------+------+-----+---------------------+-------------------------------+
| Field              | Type          | Null | Key | Default             | Extra                         |
+--------------------+---------------+------+-----+---------------------+-------------------------------+
| id                 | int(11)       | NO   | PRI | NULL                | auto_increment                |
| sales_order_id     | int(11)       | NO   | MUL | NULL                |                               |
| product_id         | int(11)       | NO   | MUL | NULL                |                               |
| quantity           | decimal(10,2) | NO   |     | NULL                |                               |
| unit_price         | decimal(10,2) | NO   |     | NULL                |                               |
| tax_rate           | decimal(5,2)  | YES  |     | 0.00                |                               |
| tax_amount         | decimal(10,2) | YES  |     | 0.00                |                               |
| withholding_tax    | decimal(5,2)  | YES  |     | 0.00                |                               |
| withholding_amount | decimal(10,2) | YES  |     | 0.00                |                               |
| discount_percent   | decimal(5,2)  | YES  |     | 0.00                |                               |
| discount_amount    | decimal(10,2) | YES  |     | 0.00                |                               |
| subtotal           | decimal(10,2) | NO   |     | NULL                |                               |
| total              | decimal(10,2) | NO   |     | NULL                |                               |
| notes              | text          | YES  |     | NULL                |                               |
| created_at         | timestamp     | NO   |     | current_timestamp() |                               |
| updated_at         | timestamp     | NO   |     | current_timestamp() | on update current_timestamp() |
+--------------------+---------------+------+-----+---------------------+-------------------------------+

PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE sales_payments;"
+------------------+--------------------------------------+------+-----+---------------------+-------------------------------+
| Field            | Type                                 | Null | Key | Default             | Extra                         |
+------------------+--------------------------------------+------+-----+---------------------+-------------------------------+
| id               | int(11)                              | NO   | PRI | NULL                | auto_increment                |
| sales_order_id   | int(11)                              | NO   | MUL | NULL                |                               |
| payment_date     | datetime                             | NO   |     | NULL                |                               |
| amount           | decimal(10,2)                        | NO   |     | NULL                |                               |
| payment_method   | enum('Cash','Bank Transfer','Check') | NO   |     | NULL                |                               |
| reference_number | varchar(100)                         | YES  |     | NULL                |                               |
| notes            | text                                 | YES  |     | NULL                |                               |
| account_id       | int(11)                              | YES  | MUL | NULL                |                               |
| created_by       | int(11)                              | NO   | MUL | NULL                |                               |
| created_at       | datetime                             | NO   |     | current_timestamp() |                               |
| updated_at       | datetime                             | YES  |     | NULL                | on update current_timestamp() |
+------------------+--------------------------------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march> 




PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE production_orders;"
+--------------------------+----------------------------------------------------------------+------+-----+---------------------+-------------------------------+
| Field                    | Type                                                           | Null | Key | Default             | Extra                         |
+--------------------------+----------------------------------------------------------------+------+-----+---------------------+-------------------------------+
| id                       | int(11)                                                        | NO   | PRI | NULL                | auto_increment                |
| order_number             | varchar(50)                                                    | NO   | UNI | NULL                |                               |
| product_id               | int(11)                                                        | NO   | MUL | NULL                |                               |
| target_quantity          | decimal(10,2)                                                  | NO   |     | NULL                |                               |
| completed_quantity       | decimal(10,2)                                                  | YES  |     | 0.00                |                               |
| start_date               | date                                                           | NO   | MUL | NULL                |                               |
| expected_completion_date | date                                                           | NO   |     | NULL                |                               |
| actual_completion_date   | date                                                           | YES  |     | NULL                |                               |
| status                   | enum('draft','confirmed','inprogress','completed','cancelled') | YES  | MUL | draft               |                               |
| notes                    | text                                                           | YES  |     | NULL                |                               |
| created_at               | timestamp                                                      | NO   |     | current_timestamp() |                               |
| updated_at               | timestamp                                                      | NO   |     | current_timestamp() | on update current_timestamp() |
| created_by               | int(11)                                                        | YES  | MUL | NULL                |                               |
| warehouse_id             | int(11)                                                        | YES  | MUL | NULL                |                               |
+--------------------------+----------------------------------------------------------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march>

PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE production_products;"
+-----------------+---------------------------+------+-----+---------------------+-------------------------------+
| Field           | Type                      | Null | Key | Default             | Extra                         |
+-----------------+---------------------------+------+-----+---------------------+-------------------------------+
| id              | int(11)                   | NO   | PRI | NULL                | auto_increment                |
| product_code    | varchar(50)               | NO   | UNI | NULL                |                               |
| name            | varchar(100)              | NO   |     | NULL                |                               |
| category_id     | int(11)                   | YES  | MUL | NULL                |                               |
| brand_id        | int(11)                   | YES  | MUL | NULL                |                               |
| unit            | varchar(20)               | NO   |     | NULL                |                               |
| current_stock   | decimal(10,2)             | YES  |     | 0.00                |                               |
| min_stock_level | decimal(10,2)             | YES  |     | 0.00                |                               |
| production_cost | decimal(10,2)             | YES  |     | 0.00                |                               |
| selling_price   | decimal(10,2)             | YES  |     | 0.00                |                               |
| description     | text                      | YES  |     | NULL                |                               |
| status          | enum('active','inactive') | YES  |     | active              |                               |
| created_at      | timestamp                 | NO   |     | current_timestamp() |                               |
| updated_at      | timestamp                 | NO   |     | current_timestamp() | on update current_timestamp() |
| created_by      | int(11)                   | YES  | MUL | NULL                |                               |
+-----------------+---------------------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march>


PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE production_quality_checks;"
+---------------+--------------+------+-----+---------------------+----------------+
| Field         | Type         | Null | Key | Default             | Extra          |
+---------------+--------------+------+-----+---------------------+----------------+
| id            | int(11)      | NO   | PRI | NULL                | auto_increment |
| production_id | int(11)      | YES  |     | NULL                |                |
| check_point   | varchar(255) | YES  |     | NULL                |                |
| status        | varchar(50)  | YES  |     | NULL                |                |
| checked_by    | int(11)      | YES  |     | NULL                |                |
| notes         | text         | YES  |     | NULL                |                |
| created_at    | timestamp    | NO   |     | current_timestamp() |                |
+---------------+--------------+------+-----+---------------------+----------------+
PS C:\xampp\htdocs\pistocklnt1march>



PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE quality_control;"          
+---------------------+--------------------------------------------+------+-----+---------------------+----------------+
| Field               | Type                                       | Null | Key | Default             | Extra          |
+---------------------+--------------------------------------------+------+-----+---------------------+----------------+
| id                  | int(11)                                    | NO   | PRI | NULL                | auto_increment |
| production_order_id | int(11)                                    | NO   | MUL | NULL                |                |
| inspection_date     | date                                       | NO   |     | NULL                |                |
| quantity_checked    | decimal(10,2)                              | NO   |     | NULL                |                |
| quantity_passed     | decimal(10,2)                              | NO   |     | NULL                |                |
| quantity_failed     | decimal(10,2)                              | NO   |     | NULL                |                |
| defect_type         | varchar(100)                               | YES  |     | NULL                |                |
| notes               | text                                       | YES  |     | NULL                |                |
| status              | enum('passed','failed','partially_passed') | NO   |     | NULL                |                |
| created_at          | timestamp                                  | NO   |     | current_timestamp() |                |
| created_by          | int(11)                                    | YES  | MUL | NULL                |                |
+---------------------+--------------------------------------------+------+-----+---------------------+----------------+
PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE quality_control_parameters;"
+------------------+--------------+------+-----+---------------------+----------------+
| Field            | Type         | Null | Key | Default             | Extra          |
+------------------+--------------+------+-----+---------------------+----------------+
| id               | int(11)      | NO   | PRI | NULL                | auto_increment |
| parameter_name   | varchar(255) | YES  |     | NULL                |                |
| parameter_type   | varchar(50)  | YES  |     | NULL                |                |
| unit_of_measure  | varchar(50)  | YES  |     | NULL                |                |
| acceptable_range | varchar(100) | YES  |     | NULL                |                |
| created_at       | timestamp    | NO   |     | current_timestamp() |                |
+------------------+--------------+------+-----+---------------------+----------------+
PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE quality_control_results;"   
+----------------+--------------+------+-----+---------------------+----------------+
| Field          | Type         | Null | Key | Default             | Extra          |
+----------------+--------------+------+-----+---------------------+----------------+
| id             | int(11)      | NO   | PRI | NULL                | auto_increment |
| control_date   | datetime     | YES  |     | NULL                |                |
| parameter_id   | int(11)      | YES  | MUL | NULL                |                |
| measured_value | varchar(100) | YES  |     | NULL                |                |
| result_status  | varchar(50)  | YES  |     | NULL                |                |
| inspector_id   | int(11)      | YES  |     | NULL                |                |
| created_at     | timestamp    | NO   |     | current_timestamp() |                |
+----------------+--------------+------+-----+---------------------+----------------+
PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE quality_defect_types;"   
+----------------+--------------+------+-----+---------------------+----------------+
| Field          | Type         | Null | Key | Default             | Extra          |
+----------------+--------------+------+-----+---------------------+----------------+
| id             | int(11)      | NO   | PRI | NULL                | auto_increment |
| defect_name    | varchar(255) | YES  |     | NULL                |                |
| description    | text         | YES  |     | NULL                |                |
| severity_level | varchar(50)  | YES  |     | NULL                |                |
| created_at     | timestamp    | NO   |     | current_timestamp() |                |
+----------------+--------------+------+-----+---------------------+----------------+
PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE quality_inspection_points;"
+-----------------+--------------+------+-----+---------------------+----------------+
| Field           | Type         | Null | Key | Default             | Extra          |
+-----------------+--------------+------+-----+---------------------+----------------+
| id              | int(11)      | NO   | PRI | NULL                | auto_increment |
| inspection_name | varchar(255) | YES  |     | NULL                |                |
| description     | text         | YES  |     | NULL                |                |
| frequency       | varchar(50)  | YES  |     | NULL                |                |
| active          | tinyint(1)   | YES  |     | NULL                |                |
| created_at      | timestamp    | NO   |     | current_timestamp() |                |
+-----------------+--------------+------+-----+---------------------+----------------+
PS C:\xampp\htdocs\pistocklnt1march>






PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE raw_materials;"
+-------------------+---------------------------+------+-----+---------------------+-------------------------------+
| Field             | Type                      | Null | Key | Default             | Extra                         |
+-------------------+---------------------------+------+-----+---------------------+-------------------------------+
| id                | int(11)                   | NO   | PRI | NULL                | auto_increment                |
| material_code     | varchar(50)               | NO   | UNI | NULL                |                               |
| name              | varchar(100)              | NO   |     | NULL                |                               |
| category_id       | int(11)                   | YES  | MUL | NULL                |                               |
| unit              | varchar(20)               | NO   |     | NULL                |                               |
| current_stock     | decimal(10,2)             | YES  |     | 0.00                |                               |
| reserved_quantity | decimal(10,2)             | YES  |     | 0.00                |                               |
| min_stock_level   | decimal(10,2)             | YES  |     | 0.00                |                               |
| cost_per_unit     | decimal(10,2)             | YES  |     | 0.00                |                               |
| description       | text                      | YES  |     | NULL                |                               |
| status            | enum('active','inactive') | YES  | MUL | active              |                               |
| created_at        | timestamp                 | NO   |     | current_timestamp() |                               |
| updated_at        | timestamp                 | NO   |     | current_timestamp() | on update current_timestamp() |
| created_by        | int(11)                   | YES  | MUL | NULL                |                               |
+-------------------+---------------------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march>



PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE raw_material_movements;"
+-------------------------+-----------------------------------------------------+------+-----+---------------------+----------------+
| Field                   | Type                                                | Null | Key | Default             | Extra          |
+-------------------------+-----------------------------------------------------+------+-----+---------------------+----------------+
| id                      | int(11)                                             | NO   | PRI | NULL                | auto_increment |
| material_id             | int(11)                                             | NO   | MUL | NULL                |                |
| movement_type           | enum('in','out')                                    | NO   | MUL | NULL                |                |
| quantity                | decimal(10,2)                                       | NO   |     | NULL                |                |
| reference_type          | enum('purchase','production','adjustment','return') | NO   |     | NULL                |                |
| reference_id            | int(11)                                             | NO   |     | NULL                |                |
| notes                   | text                                                | YES  |     | NULL                |                |
| warehouse_id            | int(11)                                             | YES  | MUL | NULL                |                |
| source_location_id      | int(11)                                             | YES  | MUL | NULL                |                |
| destination_location_id | int(11)                                             | YES  | MUL | NULL                |                |
| created_at              | timestamp                                           | NO   |     | current_timestamp() |                |
| created_by              | int(11)                                             | YES  | MUL | NULL                |                |
+-------------------------+-----------------------------------------------------+------+-----+---------------------+----------------+
PS C:\xampp\htdocs\pistocklnt1march>


PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE product_bom;"           
+-------------------+---------------------------+------+-----+---------------------+-------------------------------+
| Field             | Type                      | Null | Key | Default             | Extra                         |
+-------------------+---------------------------+------+-----+---------------------+-------------------------------+
| id                | int(11)                   | NO   | PRI | NULL                | auto_increment                |
| product_id        | int(11)                   | NO   | MUL | NULL                |                               |
| material_id       | int(11)                   | NO   | MUL | NULL                |                               |
| quantity_required | decimal(10,2)             | NO   |     | NULL                |                               |
| wastage_percent   | decimal(5,2)              | YES  |     | 0.00                |                               |
| status            | enum('active','inactive') | YES  |     | active              |                               |
| created_at        | timestamp                 | NO   |     | current_timestamp() |                               |
| updated_at        | timestamp                 | NO   |     | current_timestamp() | on update current_timestamp() |
| created_by        | int(11)                   | YES  | MUL | NULL                |                               |
+-------------------+---------------------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march>



PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE production_products;"
+-----------------+---------------------------+------+-----+---------------------+-------------------------------+
| Field           | Type                      | Null | Key | Default             | Extra                         |
+-----------------+---------------------------+------+-----+---------------------+-------------------------------+
| id              | int(11)                   | NO   | PRI | NULL                | auto_increment                |
| product_code    | varchar(50)               | NO   | UNI | NULL                |                               |
| name            | varchar(100)              | NO   |     | NULL                |                               |
| category_id     | int(11)                   | YES  | MUL | NULL                |                               |
| brand_id        | int(11)                   | YES  | MUL | NULL                |                               |
| unit            | varchar(20)               | NO   |     | NULL                |                               |
| current_stock   | decimal(10,2)             | YES  |     | 0.00                |                               |
| min_stock_level | decimal(10,2)             | YES  |     | 0.00                |                               |
| production_cost | decimal(10,2)             | YES  |     | 0.00                |                               |
| selling_price   | decimal(10,2)             | YES  |     | 0.00                |                               |
| description     | text                      | YES  |     | NULL                |                               |
| status          | enum('active','inactive') | YES  |     | active              |                               |
| created_at      | timestamp                 | NO   |     | current_timestamp() |                               |
| updated_at      | timestamp                 | NO   |     | current_timestamp() | on update current_timestamp() |
| created_by      | int(11)                   | YES  | MUL | NULL                |                               |
+-----------------+---------------------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march>




PS C:\xampp\htdocs\pistocklnt1march> 
PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE accounts;"
+------------------------+--------------+------+-----+---------------------+-------------------------------+
| Field                  | Type         | Null | Key | Default             | Extra                         |
+------------------------+--------------+------+-----+---------------------+-------------------------------+
| id                     | int(11)      | NO   | PRI | NULL                | auto_increment                |
| account_owner          | varchar(255) | NO   | MUL | NULL                |                               |
| account_platform       | varchar(100) | NO   |     | NULL                |                               |
| Currency               | varchar(20)  | YES  |     | NULL                |                               |
| number_of_transactions | int(11)      | YES  |     | 0                   |                               |
| created_at             | timestamp    | NO   |     | current_timestamp() |                               |
| updated_at             | timestamp    | NO   |     | current_timestamp() | on update current_timestamp() |
| status                 | int(11)      | NO   |     | 1                   |                               |
+------------------------+--------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE digitalswap;"
+------------------+----------------------------+------+-----+---------------------+-------------------------------+
| Field            | Type                       | Null | Key | Default             | Extra                         |
+------------------+----------------------------+------+-----+---------------------+-------------------------------+
| id               | int(11)                    | NO   | PRI | NULL                | auto_increment                |
| account_id       | int(11)                    | YES  | MUL | NULL                |                               |
| transaction_date | date                       | YES  |     | NULL                |                               |
| type             | enum('deposit','withdraw') | NO   |     | NULL                |                               |
| type_id          | int(11)                    | YES  | MUL | NULL                |                               |
| name             | varchar(255)               | NO   |     | NULL                |                               |
| platform         | varchar(100)               | NO   |     | NULL                |                               |
| platform_id      | int(11)                    | YES  | MUL | NULL                |                               |
| amount           | decimal(10,2)              | NO   |     | NULL                |                               |
| image            | varchar(255)               | YES  |     | NULL                |                               |
| comment          | text                       | YES  |     | NULL                |                               |
| status           | tinyint(1)                 | NO   |     | 1                   |                               |
| created_at       | timestamp                  | NO   |     | current_timestamp() |                               |
| updated_at       | timestamp                  | NO   |     | current_timestamp() | on update current_timestamp() |
+------------------+----------------------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE digital_categories;"
+---------------+-------------+------+-----+---------------------+-------------------------------+
| Field         | Type        | Null | Key | Default             | Extra                         |
+---------------+-------------+------+-----+---------------------+-------------------------------+
| category_id   | int(11)     | NO   | PRI | NULL                | auto_increment                |
| category_name | varchar(50) | NO   |     | NULL                |                               |
| description   | text        | YES  |     | NULL                |                               |
| created_at    | timestamp   | NO   |     | current_timestamp() |                               |
| updated_at    | timestamp   | NO   |     | current_timestamp() | on update current_timestamp() |
+---------------+-------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march> 



PS C:\xampp\htdocs\pistocklnt1march> C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE platforms;"         
+-------------+--------------+------+-----+---------------------+-------------------------------+
| Field       | Type         | Null | Key | Default             | Extra                         |
+-------------+--------------+------+-----+---------------------+-------------------------------+
| id          | int(11)      | NO   | PRI | NULL                | auto_increment                |
| name        | varchar(100) | NO   |     | NULL                |                               |
| description | text         | YES  |     | NULL                |                               |
| status      | tinyint(1)   | YES  |     | 1                   |                               |
| created_at  | datetime     | YES  |     | current_timestamp() |                               |
| updated_at  | datetime     | YES  |     | current_timestamp() | on update current_timestamp() |
+-------------+--------------+------+-----+---------------------+-------------------------------+
PS C:\xampp\htdocs\pistocklnt1march>



PS C:\xampp\htdocs\pistocklnt1march>  C:/xampp/mysql/bin/mysql -u root -e "USE pistocklntmarch; DESCRIBE reports;"  
+-------------+--------------+------+-----+---------------------+----------------+
| Field       | Type         | Null | Key | Default             | Extra          |
+-------------+--------------+------+-----+---------------------+----------------+
| id          | int(11)      | NO   | PRI | NULL                | auto_increment |
| report_name | varchar(255) | NO   |     | NULL                |                |
| report_type | varchar(50)  | NO   |     | NULL                |                |
| report_url  | varchar(255) | NO   |     | NULL                |                |
| created_by  | int(11)      | NO   | MUL | NULL                |                |
| created_at  | datetime     | YES  |     | current_timestamp() |                |
+-------------+--------------+------+-----+---------------------+----------------+
PS C:\xampp\htdocs\pistocklnt1march> 



