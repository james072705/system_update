-- ============================================================
-- MIGRATION: Add email & email-verification columns to users
-- Run this ONLY if you already had an existing kiosk_system DB
-- created before the email/OTP feature was added.
-- ============================================================

USE kiosk_system;

-- Add email column (nullable first so existing rows are not lost)
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL AFTER fullname;

-- Add verification + otp columns
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
  ADD COLUMN IF NOT EXISTS otp VARCHAR(6) NULL AFTER is_verified,
  ADD COLUMN IF NOT EXISTS otp_expiry DATETIME NULL AFTER otp;

-- Backfill email for existing users (if empty) using username@kiosk.local
UPDATE users SET email = CONCAT(username, '@kiosk.local') WHERE email IS NULL OR email = '';

-- Mark all existing users as verified (they were created before OTP feature)
UPDATE users SET is_verified = 1 WHERE is_verified = 0;

-- Now make email NOT NULL and UNIQUE
ALTER TABLE users
  MODIFY email VARCHAR(150) NOT NULL,
  ADD UNIQUE INDEX uq_users_email (email);
