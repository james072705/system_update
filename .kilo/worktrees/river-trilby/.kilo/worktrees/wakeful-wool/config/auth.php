<?php
/**
 * Authentication helpers (role-based access using PHP sessions)
 */
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/database.php';

/** Check if a user is logged in */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/** Redirect to login if not authenticated */
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . 'authentication/login.php');
        exit;
    }
}

/**
 * Restrict page access to certain roles.
 * Usage: require_role('admin');  OR  require_role(['admin','staff']);
 */
function require_role($roles) {
    require_login();

    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['role'], $roles)) {
        $_SESSION['error'] = 'You do not have permission to access that page.';
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

/** Fetch the currently logged-in user record (fresh from DB) */
function current_user() {
    if (!is_logged_in()) return null;

    $stmt = db()->prepare('SELECT * FROM users WHERE user_id = ? AND status = "active" LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

