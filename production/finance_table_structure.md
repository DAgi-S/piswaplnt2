# Finance Management System Database Structure for Production

## Production Cost Accounting Tables

### `finance_production_costs`
```sql
CREATE TABLE `finance_production_costs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `production_order_id` int(11) NOT NULL,
  `material_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `labor_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `overhead_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `target_cost` decimal(15,2) DEFAULT NULL,
  `cost_variance` decimal(15,2) DEFAULT NULL,
  `status` enum('estimated','in_progress','finalized') NOT NULL DEFAULT 'estimated',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `production_order_id` (`production_order_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_production_cost_details`
```sql
CREATE TABLE `finance_production_cost_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `production_cost_id` int(11) NOT NULL,
  `cost_type` enum('material','labor','overhead') NOT NULL,
  `cost_category` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `production_cost_id` (`production_cost_id`),
  KEY `cost_type` (`cost_type`),
  KEY `cost_category` (`cost_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_overhead_allocation`
```sql
CREATE TABLE `finance_overhead_allocation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fiscal_period_id` int(11) NOT NULL,
  `overhead_account_id` int(11) NOT NULL,
  `allocation_method` enum('direct','labor_hours','machine_hours','equal') NOT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','allocated','posted') NOT NULL DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fiscal_period_id` (`fiscal_period_id`),
  KEY `overhead_account_id` (`overhead_account_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_overhead_allocation_details`
```sql
CREATE TABLE `finance_overhead_allocation_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `allocation_id` int(11) NOT NULL,
  `production_order_id` int(11) NOT NULL,
  `allocation_basis` decimal(15,2) NOT NULL,
  `allocation_percentage` decimal(5,2) NOT NULL,
  `allocated_amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `allocation_id` (`allocation_id`),
  KEY `production_order_id` (`production_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_labor_rates`
```sql
CREATE TABLE `finance_labor_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `labor_category` varchar(50) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `overhead_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `labor_category` (`labor_category`),
  KEY `effective_date` (`effective_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_wip_accounts`
```sql
CREATE TABLE `finance_wip_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_category_id` int(11) NOT NULL,
  `material_wip_account_id` int(11) NOT NULL,
  `labor_wip_account_id` int(11) NOT NULL,
  `overhead_wip_account_id` int(11) NOT NULL,
  `finished_goods_account_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_category_id` (`product_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_product_profitability`
```sql
CREATE TABLE `finance_product_profitability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `production_volume` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_production_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_sales_revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `gross_profit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `profit_margin_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_period` (`product_id`,`period_start`,`period_end`),
  KEY `period` (`period_start`,`period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

# Core Financial Tables

### `finance_general_ledger`
```sql
CREATE TABLE `finance_general_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_date` date NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `reference_number` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `entry_source` varchar(50) NOT NULL COMMENT 'manual, sales, purchase, etc.',
  `batch_number` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  KEY `transaction_date` (`transaction_date`),
  KEY `entry_source` (`entry_source`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_chart_of_accounts`
```sql
CREATE TABLE `finance_chart_of_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('asset','liability','equity','revenue','expense') NOT NULL,
  `account_subtype` varchar(50) DEFAULT NULL,
  `parent_account_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_code` (`account_code`),
  KEY `parent_account_id` (`parent_account_id`),
  KEY `account_type` (`account_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_fiscal_periods`
```sql
CREATE TABLE `finance_fiscal_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `closed_by` int(11) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `period_date_range` (`start_date`,`end_date`),
  KEY `is_closed` (`is_closed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Accounts Receivable Tables

### `finance_invoices`
```sql
CREATE TABLE `finance_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','sent','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`),
  KEY `invoice_date` (`invoice_date`),
  KEY `due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_invoice_items`
```sql
CREATE TABLE `finance_invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `account_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `product_id` (`product_id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_payments_received`
```sql
CREATE TABLE `finance_payments_received` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_number` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_number` (`payment_number`),
  KEY `client_id` (`client_id`),
  KEY `payment_date` (`payment_date`),
  KEY `payment_method` (`payment_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_payment_allocations`
```sql
CREATE TABLE `finance_payment_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount_applied` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_invoice` (`payment_id`,`invoice_id`),
  KEY `invoice_id` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Accounts Payable Tables

### `finance_vendor_bills`
```sql
CREATE TABLE `finance_vendor_bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_number` varchar(50) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `bill_date` date NOT NULL,
  `due_date` date NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','received','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `bill_date` (`bill_date`),
  KEY `due_date` (`due_date`),
  KEY `status` (`status`),
  KEY `reference_number` (`reference_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_bill_items`
```sql
CREATE TABLE `finance_bill_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `account_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  KEY `product_id` (`product_id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_payments_made`
```sql
CREATE TABLE `finance_payments_made` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_number` varchar(50) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_number` (`payment_number`),
  KEY `vendor_id` (`vendor_id`),
  KEY `payment_date` (`payment_date`),
  KEY `payment_method` (`payment_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_bill_payments`
```sql
CREATE TABLE `finance_bill_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `amount_applied` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_bill` (`payment_id`,`bill_id`),
  KEY `bill_id` (`bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Tax Management Tables

### `finance_tax_rates`
```sql
CREATE TABLE `finance_tax_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `is_compound` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_tax_payments`
```sql
CREATE TABLE `finance_tax_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tax_period_start` date NOT NULL,
  `tax_period_end` date NOT NULL,
  `tax_authority` varchar(100) NOT NULL,
  `tax_type` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tax_period` (`tax_period_start`,`tax_period_end`),
  KEY `tax_type` (`tax_type`),
  KEY `payment_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Budget Management Tables

### `finance_budgets`
```sql
CREATE TABLE `finance_budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `fiscal_year` year(4) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('draft','active','closed') NOT NULL DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_year` (`name`,`fiscal_year`),
  KEY `fiscal_year` (`fiscal_year`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_budget_items`
```sql
CREATE TABLE `finance_budget_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `period` varchar(20) NOT NULL COMMENT 'month or quarter number',
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_account_period` (`budget_id`,`account_id`,`period`),
  KEY `account_id` (`account_id`),
  KEY `period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Asset Management Tables

### `finance_assets`
```sql
CREATE TABLE `finance_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_name` varchar(100) NOT NULL,
  `asset_number` varchar(50) NOT NULL,
  `asset_type` varchar(50) NOT NULL,
  `purchase_date` date NOT NULL,
  `purchase_cost` decimal(15,2) NOT NULL,
  `salvage_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `useful_life_years` int(11) NOT NULL,
  `depreciation_method` enum('straight_line','declining_balance','units_of_production') NOT NULL DEFAULT 'straight_line',
  `asset_account_id` int(11) NOT NULL,
  `accumulated_depreciation_account_id` int(11) NOT NULL,
  `depreciation_expense_account_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','disposed','inactive') NOT NULL DEFAULT 'active',
  `disposal_date` date DEFAULT NULL,
  `disposal_amount` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_number` (`asset_number`),
  KEY `asset_type` (`asset_type`),
  KEY `purchase_date` (`purchase_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_asset_depreciation`
```sql
CREATE TABLE `finance_asset_depreciation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `depreciation_date` date NOT NULL,
  `depreciation_amount` decimal(15,2) NOT NULL,
  `accumulated_depreciation` decimal(15,2) NOT NULL,
  `book_value` decimal(15,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `depreciation_date` (`depreciation_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Bank and Cash Management

### `finance_bank_accounts`
```sql
CREATE TABLE `finance_bank_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `account_type` varchar(50) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `ledger_account_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `account_name` (`account_name`),
  KEY `ledger_account_id` (`ledger_account_id`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_bank_transactions`
```sql
CREATE TABLE `finance_bank_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_account_id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `description` varchar(255) NOT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `transaction_type` enum('deposit','withdrawal','transfer','interest','fee','adjustment') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `running_balance` decimal(15,2) NOT NULL,
  `reconciled` tinyint(1) NOT NULL DEFAULT 0,
  `related_id` int(11) DEFAULT NULL COMMENT 'ID from payment/receipt/transfer tables',
  `related_type` varchar(50) DEFAULT NULL COMMENT 'payment_received, payment_made, transfer, etc.',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `transaction_date` (`transaction_date`),
  KEY `transaction_type` (`transaction_type`),
  KEY `reconciled` (`reconciled`),
  KEY `related` (`related_id`,`related_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `finance_bank_reconciliations`
```sql
CREATE TABLE `finance_bank_reconciliations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_account_id` int(11) NOT NULL,
  `statement_date` date NOT NULL,
  `statement_balance` decimal(15,2) NOT NULL,
  `reconciled_balance` decimal(15,2) NOT NULL,
  `is_complete` tinyint(1) NOT NULL DEFAULT 0,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `statement_date` (`statement_date`),
  KEY `is_complete` (`is_complete`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Foreign Key Relationships

```sql
-- Core Financial Tables References
ALTER TABLE `finance_general_ledger`
  ADD CONSTRAINT `fk_gl_account` FOREIGN KEY (`account_id`) REFERENCES `finance_chart_of_accounts` (`id`),
  ADD CONSTRAINT `fk_gl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `finance_chart_of_accounts`
  ADD CONSTRAINT `fk_account_parent` FOREIGN KEY (`parent_account_id`) REFERENCES `finance_chart_of_accounts` (`id`) ON DELETE SET NULL;

ALTER TABLE `finance_fiscal_periods`
  ADD CONSTRAINT `fk_closed_by_user` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Accounts Receivable References
ALTER TABLE `finance_invoices`
  ADD CONSTRAINT `fk_invoice_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `fk_invoice_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `finance_invoice_items`
  ADD CONSTRAINT `fk_invoice_item_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `finance_invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invoice_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_invoice_item_account` FOREIGN KEY (`account_id`) REFERENCES `finance_chart_of_accounts` (`id`);

ALTER TABLE `finance_payments_received`
  ADD CONSTRAINT `fk_payment_received_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `fk_payment_received_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `finance_payment_allocations`
  ADD CONSTRAINT `fk_payment_allocation_payment` FOREIGN KEY (`payment_id`) REFERENCES `finance_payments_received` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payment_allocation_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `finance_invoices` (`id`) ON DELETE CASCADE;

-- Accounts Payable References
ALTER TABLE `finance_vendor_bills`
  ADD CONSTRAINT `fk_bill_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `fk_bill_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `finance_bill_items`
  ADD CONSTRAINT `fk_bill_item_bill` FOREIGN KEY (`bill_id`) REFERENCES `finance_vendor_bills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bill_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_bill_item_account` FOREIGN KEY (`account_id`) REFERENCES `finance_chart_of_accounts` (`id`);

ALTER TABLE `finance_payments_made`
  ADD CONSTRAINT `fk_payment_made_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `fk_payment_made_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `finance_bill_payments`
  ADD CONSTRAINT `fk_bill_payment_payment` FOREIGN KEY (`payment_id`) REFERENCES `finance_payments_made` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bill_payment_bill` FOREIGN KEY (`bill_id`) REFERENCES `finance_vendor_bills` (`id`) ON DELETE CASCADE;

-- Budget Management References
ALTER TABLE `finance_budgets`
  ADD CONSTRAINT `fk_budget_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `finance_budget_items`
  ADD CONSTRAINT `fk_budget_item_budget` FOREIGN KEY (`budget_id`) REFERENCES `finance_budgets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_budget_item_account` FOREIGN KEY (`account_id`) REFERENCES `finance_chart_of_accounts` (`id`);

-- Asset Management References
ALTER TABLE `finance_assets`
  ADD CONSTRAINT `fk_asset_asset_account` FOREIGN KEY (`asset_account_id`) REFERENCES `finance_chart_of_accounts` (`id`),
  ADD CONSTRAINT `fk_asset_accum_dep_account` FOREIGN KEY (`accumulated_depreciation_account_id`) REFERENCES `finance_chart_of_accounts` (`id`),
  ADD CONSTRAINT `fk_asset_dep_expense_account` FOREIGN KEY (`depreciation_expense_account_id`) REFERENCES `finance_chart_of_accounts` (`id`);

ALTER TABLE `finance_asset_depreciation`
  ADD CONSTRAINT `fk_asset_depreciation_asset` FOREIGN KEY (`asset_id`) REFERENCES `finance_assets` (`id`) ON DELETE CASCADE;

-- Bank and Cash Management References
ALTER TABLE `finance_bank_accounts`
  ADD CONSTRAINT `fk_bank_account_ledger` FOREIGN KEY (`ledger_account_id`) REFERENCES `finance_chart_of_accounts` (`id`);

ALTER TABLE `finance_bank_transactions`
  ADD CONSTRAINT `fk_bank_transaction_account` FOREIGN KEY (`bank_account_id`) REFERENCES `finance_bank_accounts` (`id`) ON DELETE CASCADE;

ALTER TABLE `finance_bank_reconciliations`
  ADD CONSTRAINT `fk_bank_reconciliation_account` FOREIGN KEY (`bank_account_id`) REFERENCES `finance_bank_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bank_reconciliation_user` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Production Cost Accounting References
ALTER TABLE `finance_production_costs`
  ADD CONSTRAINT `fk_production_costs_order` FOREIGN KEY (`production_order_id`) REFERENCES `production_orders` (`id`) ON DELETE CASCADE;

ALTER TABLE `finance_production_cost_details`
  ADD CONSTRAINT `fk_cost_detail_production_cost` FOREIGN KEY (`production_cost_id`) REFERENCES `finance_production_costs` (`id`) ON DELETE CASCADE;

ALTER TABLE `finance_overhead_allocation`
  ADD CONSTRAINT `fk_overhead_fiscal_period` FOREIGN KEY (`fiscal_period_id`) REFERENCES `finance_fiscal_periods` (`id`),
  ADD CONSTRAINT `fk_overhead_account` FOREIGN KEY (`overhead_account_id`) REFERENCES `finance_chart_of_accounts` (`id`),
  ADD CONSTRAINT `fk_overhead_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `finance_overhead_allocation_details`
  ADD CONSTRAINT `fk_allocation_detail_allocation` FOREIGN KEY (`allocation_id`) REFERENCES `finance_overhead_allocation` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_allocation_detail_production_order` FOREIGN KEY (`production_order_id`) REFERENCES `production_orders` (`id`);

ALTER TABLE `finance_wip_accounts`
  ADD CONSTRAINT `fk_wip_product_category` FOREIGN KEY (`product_category_id`) REFERENCES `production_categories` (`id`),
  ADD CONSTRAINT `fk_wip_material_account` FOREIGN KEY (`material_wip_account_id`) REFERENCES `finance_chart_of_accounts` (`id`),
  ADD CONSTRAINT `fk_wip_labor_account` FOREIGN KEY (`labor_wip_account_id`) REFERENCES `finance_chart_of_accounts` (`id`),
  ADD CONSTRAINT `fk_wip_overhead_account` FOREIGN KEY (`overhead_wip_account_id`) REFERENCES `finance_chart_of_accounts` (`id`),
  ADD CONSTRAINT `fk_wip_finished_goods_account` FOREIGN KEY (`finished_goods_account_id`) REFERENCES `finance_chart_of_accounts` (`id`);

ALTER TABLE `finance_product_profitability`
  ADD CONSTRAINT `fk_profitability_product` FOREIGN KEY (`product_id`) REFERENCES `production_products` (`id`);
```
```