# SQL Change Log

## 2024-03-19
```sql
-- Fix guest_account_links table structure
ALTER TABLE guest_account_links MODIFY guest_id varchar(50);

-- Add performance indexes
ALTER TABLE guest_account_links ADD INDEX idx_guest_account (guest_id, account_id);
ALTER TABLE digitalswap ADD INDEX idx_account_date (account_id, transaction_date);
``` 