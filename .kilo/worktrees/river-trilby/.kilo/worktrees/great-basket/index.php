<?php
/**
 * Landing / redirect page
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'authentication/login.php');
    exit;
}

// Redirect based on role
if (isset($_SESSION['role']) && $_SESSION['role'] === 'kitchen') {
    header('Location: ' . BASE_URL . 'kitchen/index.php');
} else {
    header('Location: ' . BASE_URL . 'admin/dashboard/dashboard.php');
}
exit;
