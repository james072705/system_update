-- ============================================================
--  Migration: Add payment_method column to orders table
--  Run this once on an EXISTING database (kiosk_system)
--  so the kitchen completion records the correct payment method.
--  Fresh installs from kiosk_system.sql already include this column.
-- ============================================================

USE kiosk_system;

-- Add payment_method to orders if it does not exist yet
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'kiosk_system'
      AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'payment_method'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT "Cash"',
    'SELECT "payment_method column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill existing orders with 'Cash' default
UPDATE orders SET payment_method = 'Cash' WHERE payment_method IS NULL OR payment_method = '';

