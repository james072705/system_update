<?php
/**
 * Global constants for the Kiosk System
 */

// Application
define('APP_NAME',   "Ferry's Kiosk");
define('SITE_NAME',  "Ferry's Restobar");
define('APP_TAGLINE','Sales & Inventory Management System');

// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kiosk_system');

// Base URL - auto-detected so it works on localhost AND over LAN (tablet/phone)
// Detects the host from the current request so assets always load correctly
$__scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$__host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$__base   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/kiosk/')), '/');

// If SCRIPT_NAME is deep (e.g. /kiosk/kiosk/index.php), ensure we anchor to /kiosk
if (strpos($__base, '/kiosk') !== false) {
    $__base = '/kiosk';
}

define('BASE_URL', $__scheme . '://' . $__host . $__base . '/');

// Currency symbol (Philippine Peso)
define('CURRENCY', '&#8369;');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads/products/');
define('UPLOAD_URL',  BASE_URL . 'assets/uploads/products/');

// Business / Receipt information
define('BUSINESS_NAME',   "Ferry's Restobar");
define('BUSINESS_ADDRESS','51 Palanan, Santa Mesa Heights, Quezon City, 1114 Kalakhang Maynila');
define('BUSINESS_PHONE', '09632768216');
define('BUSINESS_EMAIL', 'ferrysrestobar@gmail.com');

// Date / Timezone (Philippines)
date_default_timezone_set('Asia/Manila');

