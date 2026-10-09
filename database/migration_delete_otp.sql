-- ============================================================
-- MIGRATION: Add delete-account OTP verification columns
-- Used by authentication/change_password.php (Danger Zone)
-- Run this ONLY if you already had an existing kiosk_system DB.
-- ============================================================

USE kiosk_system;

-- Store the 6-digit OTP sent to the user's email before deleting the account
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS delete_otp VARCHAR(6) NULL AFTER otp_expiry;

-- Expiry timestamp for the delete OTP
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS delete_otp_expiry DATETIME NULL AFTER delete_otp;

