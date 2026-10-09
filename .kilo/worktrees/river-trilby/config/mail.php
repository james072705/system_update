<?php
/**
 * SMTP Mail Configuration
 * Used for sending OTP / verification emails via Gmail SMTP.
 *
 * For Gmail: enable 2-Step Verification, then create an "App Password"
 * (Google Account -> Security -> 2-Step Verification -> App passwords).
 */

define('SMTP_HOST',   'smtp.gmail.com');
define('SMTP_PORT',   587);
define('SMTP_USER',   'jamescorresparas9@gmail.com');
define('SMTP_PASS',   'rjqn cpey kmia wixt');
define('SMTP_FROM',   'jamescorresparas9@gmail.com');
define('SMTP_FROM_NAME', 'Ferry\'s Restobar');

// OTP settings
define('OTP_LENGTH', 6);          // number of digits
define('OTP_EXPIRY_MINUTES', 10); // OTP valid for X minutes
