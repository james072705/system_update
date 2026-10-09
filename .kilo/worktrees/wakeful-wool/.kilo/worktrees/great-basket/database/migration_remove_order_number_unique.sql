-- Remove UNIQUE constraint from order_number to allow same short numbers on different days
-- Run this once in phpMyAdmin or MySQL console
ALTER TABLE orders DROP INDEX order_number;
