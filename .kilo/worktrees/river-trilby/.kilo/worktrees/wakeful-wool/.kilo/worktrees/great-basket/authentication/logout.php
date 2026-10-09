<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

if (isset($_SESSION['user_id'])) {
    log_activity('Logout', 'User logged out: ' . ($_SESSION['username'] ?? ''));
}

// Clear session
$_SESSION = [];
session_destroy();
header('Location: ' . BASE_URL . 'authentication/login.php');
exit;
