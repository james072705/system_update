-- ============================================================
--  MIGRATION: Login Lockout Feature
--  Adds failed_attempts and locked_until columns to the users
--  table so accounts can be locked after 3 failed login
--  attempts for 3 minutes.
--
--  Run ONCE in phpMyAdmin on the kiosk_system database:
--    1. Open phpMyAdmin
--    2. Select the `kiosk_system` database
--    3. Click the SQL tab
--    4. Paste & run this script
-- ============================================================

ALTER TABLE users
  ADD COLUMN failed_attempts INT NOT NULL DEFAULT 0 AFTER status,
  ADD COLUMN locked_until    DATETIME NULL AFTER failed_attempts;

