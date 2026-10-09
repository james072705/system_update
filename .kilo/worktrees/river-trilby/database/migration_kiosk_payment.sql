-- ============================================================
--  Migration: Add payment settlement columns to orders table
--  Run this once on an EXISTING database (kiosk_system)
--  so kiosk orders can be settled by admin before going to kitchen.
--  Fresh installs from kiosk_system.sql already include these columns.
-- ============================================================

USE kiosk_system;

-- Add payment_status to orders if it does not exist yet
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'kiosk_system'
      AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'payment_status'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE orders ADD COLUMN payment_status ENUM("unpaid","paid") NOT NULL DEFAULT "unpaid"',
    'SELECT "payment_status column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add paid_at to orders if it does not exist yet
SET @col_exists2 = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'kiosk_system'
      AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'paid_at'
);

SET @sql2 = IF(@col_exists2 = 0,
    'ALTER TABLE orders ADD COLUMN paid_at DATETIME NULL',
    'SELECT "paid_at column already exists"');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Backfill existing pending orders as paid so they still flow to kitchen
-- (existing orders were created before this feature)
UPDATE orders SET payment_status = 'paid' WHERE payment_status IS NULL OR payment_status = '';

</content>
