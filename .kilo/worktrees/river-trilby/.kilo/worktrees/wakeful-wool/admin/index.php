<?php
/**
 * Admin landing - redirect to dashboard
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_login();
header('Location: ' . BASE_URL . 'admin/dashboard/dashboard.php');
exit;
