# ProductionManager Deployment Guide

## Pre-Deployment Steps

1. **Backup Original Files**

   ```bash
   # Create a backup directory
   mkdir -p production/php_action/backups
   
   # Backup original files
   cp production/php_action/createProductionOrder.php production/php_action/backups/
   cp production/php_action/updateOrderStatus.php production/php_action/backups/
   cp production/php_action/recordProductionProgress.php production/php_action/backups/
   cp production/php_action/getProductionOrderDetails.php production/php_action/backups/
   ```

2. **Backup Database**

   ```sql
   -- Create a backup of production-related tables
   CREATE TABLE production_orders_bak AS SELECT * FROM production_orders;
   CREATE TABLE production_order_materials_bak AS SELECT * FROM production_order_materials;
   CREATE TABLE production_progress_bak AS SELECT * FROM production_progress;
   ```

## Deployment Process

Follow these steps to deploy the new controllers one by one:

### Step 1: Deploy the ProductionManager Class

1. Ensure the `classes` directory exists:

   ```bash
   mkdir -p production/php_action/classes
   ```

2. Copy the ProductionManager class:

   ```bash
   cp production/php_action/classes/ProductionManager.php production/php_action/classes/
   ```

### Step 2: Replace Controllers One by One

Replace each controller individually and test before moving to the next one:

1. **Replace Create Production Order Controller**:

   ```bash
   cp production/php_action/createProductionOrder_new.php production/php_action/createProductionOrder.php
   ```

   Test by creating a new production order.

2. **Replace Status Update Controller**:

   ```bash
   cp production/php_action/updateOrderStatus_new.php production/php_action/updateOrderStatus.php
   ```

   Test by changing the status of an existing order.

3. **Replace Progress Recording Controller**:

   ```bash
   cp production/php_action/recordProductionProgress_new.php production/php_action/recordProductionProgress.php
   ```

   Test by recording progress for an existing order.

4. **Replace Order Details Controller**:

   ```bash
   cp production/php_action/getProductionOrderDetails_new.php production/php_action/getProductionOrderDetails.php
   ```

   Test by viewing the details of an existing order.

## Testing Checklist

For each controller, perform the following tests:

### Create Production Order
- [ ] Create order with valid data
- [ ] Verify material requirements are calculated correctly
- [ ] Test with invalid product ID
- [ ] Test with zero quantity

### Update Order Status
- [ ] Change status from draft to planned
- [ ] Change status from planned to in_progress
- [ ] Change status from in_progress to completed
- [ ] Test with invalid status

### Record Production Progress
- [ ] Record partial progress
- [ ] Record progress that completes the order
- [ ] Test with negative quantity
- [ ] Test with excessive quantity (more than target)

### Order Details
- [ ] View order details
- [ ] Verify materials are displayed correctly
- [ ] Verify progress history is displayed correctly

## Rollback Procedure

If issues occur, restore from backups:

```bash
# Restore controllers
cp production/php_action/backups/createProductionOrder.php production/php_action/
cp production/php_action/backups/updateOrderStatus.php production/php_action/
cp production/php_action/backups/recordProductionProgress.php production/php_action/
cp production/php_action/backups/getProductionOrderDetails.php production/php_action/

# Restore database if needed
DROP TABLE production_orders;
ALTER TABLE production_orders_bak RENAME TO production_orders;

DROP TABLE production_order_materials;
ALTER TABLE production_order_materials_bak RENAME TO production_order_materials;

DROP TABLE production_progress;
ALTER TABLE production_progress_bak RENAME TO production_progress;
```

## Post-Deployment Verification

After deploying all controllers, perform an end-to-end test:

1. Create a new production order
2. View the order details
3. Update the order status
4. Record progress
5. Complete the order
6. Verify inventory updates

## Troubleshooting

If issues occur:

1. **Check error logs**:
   ```bash
   tail -n 100 /path/to/php/error.log
   ```

2. **Verify column names**:
   If database errors occur, check that column names in SQL queries match your actual database structure.

3. **Session issues**:
   If user session variables are not accessible, check session configuration. 