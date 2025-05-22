# Production Management System - Table Structure

## Core Production Tables

### `production_orders`
```sql
CREATE TABLE `production_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `target_quantity` decimal(10,2) NOT NULL,
  `completed_quantity` decimal(10,2) DEFAULT 0.00,
  `start_date` date NOT NULL,
  `expected_completion_date` date NOT NULL,
  `actual_completion_date` date DEFAULT NULL,
  `status` enum('draft','confirmed','inprogress','completed','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `created_by` (`created_by`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_status` (`status`),
  KEY `idx_product_id` (`product_id`),
  KEY `fk_production_warehouse` (`warehouse_id`),
  CONSTRAINT `fk_production_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Main table for production orders tracking manufacturing instructions.

### `production_products`
```sql
CREATE TABLE `production_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `unit` varchar(20) NOT NULL,
  `current_stock` decimal(10,2) DEFAULT 0.00,
  `min_stock_level` decimal(10,2) DEFAULT 0.00,
  `production_cost` decimal(10,2) DEFAULT 0.00,
  `selling_price` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `category_id` (`category_id`),
  KEY `brand_id` (`brand_id`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `production_categories` (`id`),
  CONSTRAINT `fk_product_brand` FOREIGN KEY (`brand_id`) REFERENCES `production_brands` (`id`),
  CONSTRAINT `fk_product_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Stores information about products that can be manufactured.

### `product_bom` (Bill of Materials)
```sql
CREATE TABLE `product_bom` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `quantity_required` decimal(10,2) NOT NULL,
  `wastage_percent` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_material` (`product_id`, `material_id`),
  KEY `material_id` (`material_id`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  CONSTRAINT `fk_bom_product` FOREIGN KEY (`product_id`) REFERENCES `production_products` (`id`),
  CONSTRAINT `fk_bom_material` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`id`),
  CONSTRAINT `fk_bom_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Defines the materials and quantities required to produce each product.

### `production_order_materials`
```sql
CREATE TABLE `production_order_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `production_order_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `required_quantity` decimal(10,2) NOT NULL,
  `consumed_quantity` decimal(10,2) DEFAULT 0.00,
  `reserved_quantity` decimal(10,2) DEFAULT 0.00,
  `reservation_status` enum('pending','reserved','released','consumed') DEFAULT 'pending',
  `status` enum('pending','partially_consumed','fully_consumed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_material` (`production_order_id`, `material_id`),
  KEY `material_id` (`material_id`),
  KEY `reservation_status` (`reservation_status`),
  KEY `status` (`status`),
  CONSTRAINT `fk_pom_order` FOREIGN KEY (`production_order_id`) REFERENCES `production_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pom_material` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Tracks materials required for specific production orders and their consumption status.

### `production_progress`
```sql
CREATE TABLE `production_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `production_order_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `progress_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `production_order_id` (`production_order_id`),
  KEY `progress_date` (`progress_date`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_progress_order` FOREIGN KEY (`production_order_id`) REFERENCES `production_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Records incremental progress updates for production orders.

## Raw Materials and Inventory

### `raw_materials`
```sql
CREATE TABLE `raw_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `unit` varchar(20) NOT NULL,
  `current_stock` decimal(10,2) DEFAULT 0.00,
  `reserved_quantity` decimal(10,2) DEFAULT 0.00,
  `min_stock_level` decimal(10,2) DEFAULT 0.00,
  `cost_per_unit` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_code` (`material_code`),
  KEY `category_id` (`category_id`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  CONSTRAINT `fk_material_category` FOREIGN KEY (`category_id`) REFERENCES `raw_material_categories` (`id`),
  CONSTRAINT `fk_material_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Stores information about raw materials used in manufacturing.

### `production_categories`
```sql
CREATE TABLE `production_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Categories for classifying production products.

### `raw_material_categories`
```sql
CREATE TABLE `raw_material_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Categories for classifying raw materials.

### `warehouses`
```sql
CREATE TABLE `warehouses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `type` enum('raw_material','finished_good','both') NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_warehouse_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Stores information about warehouses where materials and products are stored.

## Quality Management

### `production_quality_checks`
```sql
CREATE TABLE `production_quality_checks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `production_order_id` int(11) NOT NULL,
  `inspection_date` date NOT NULL,
  `quantity_checked` decimal(10,2) NOT NULL,
  `status` enum('passed','failed','partially_passed') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `production_order_id` (`production_order_id`),
  KEY `inspection_date` (`inspection_date`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_quality_order` FOREIGN KEY (`production_order_id`) REFERENCES `production_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quality_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Records quality control checks performed on production orders.

### `production_waste_logs`
```sql
CREATE TABLE `production_waste_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `production_order_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `recorded_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `production_order_id` (`production_order_id`),
  KEY `material_id` (`material_id`),
  KEY `recorded_date` (`recorded_date`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_waste_order` FOREIGN KEY (`production_order_id`) REFERENCES `production_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_waste_material` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`id`),
  CONSTRAINT `fk_waste_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Tracks material waste during production.

## Supporting Tables

### `material_reservations`
```sql
CREATE TABLE `material_reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `source_type` enum('production_order','purchase_order','other') NOT NULL,
  `source_id` int(11) NOT NULL,
  `status` enum('active','released','consumed') DEFAULT 'active',
  `reservation_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `source` (`source_type`, `source_id`),
  KEY `status` (`status`),
  KEY `reservation_date` (`reservation_date`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_reservation_material` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`id`),
  CONSTRAINT `fk_reservation_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Tracks material reservations for production orders and other purposes.

### `warehouse_stock_movements`
```sql
CREATE TABLE `warehouse_stock_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` enum('raw_material','finished_good') NOT NULL,
  `movement_type` enum('in','out') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reference_type` varchar(50) NOT NULL COMMENT 'production, purchase, transfer, etc.',
  `reference_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `item` (`item_id`, `item_type`),
  KEY `movement_type` (`movement_type`),
  KEY `reference` (`reference_type`, `reference_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_movement_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `fk_movement_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Tracks inventory movements between warehouses and production.

### `production_brands`
```sql
CREATE TABLE `production_brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Brands associated with production products.

## Entity Relationships

### Production Order Relationships
- `production_orders` → `production_products` (product_id)
- `production_orders` → `warehouses` (warehouse_id)
- `production_orders` → `users` (created_by)

### Material Relationships
- `production_order_materials` → `production_orders` (production_order_id)
- `production_order_materials` → `raw_materials` (material_id)
- `product_bom` → `production_products` (product_id)
- `product_bom` → `raw_materials` (material_id)

### Progress and Quality Relationships
- `production_progress` → `production_orders` (production_order_id)
- `production_quality_checks` → `production_orders` (production_order_id)
- `production_waste_logs` → `production_orders` (production_order_id)
- `production_waste_logs` → `raw_materials` (material_id)

## Database Schema Diagram

```
                                                   ┌─────────────┐
                                        ┌─────────►│   users     │
                                        │          └─────────────┘
                                        │                ▲
                                        │                │
┌───────────────────┐     ┌────────────┴───────┐        │
│                   │     │                    │        │
│ production_orders ├────►│ production_progress├────────┘
│                   │     │                    │
└───┬───────────────┘     └────────────────────┘
    │                           ▲
    │                           │
    │    ┌───────────────────┐  │
    │    │                   │  │
    └───►│production_products│  │
         │                   │  │
         └───┬───────────────┘  │
             │                  │
             │                  │
┌────────────▼──┐   ┌──────────┴────────────┐    ┌─────────────────┐
│               │   │                       │    │                 │
│  product_bom  │   │production_order_materials│◄──┤  raw_materials  │
│               │   │                       │    │                 │
└───────────────┘   └───────────────────────┘    └─────────────────┘
``` 