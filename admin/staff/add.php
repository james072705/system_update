<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'kitchen';

    // Only kitchen role allowed here
    $allowedRoles = ['kitchen'];
    if (!in_array($role, $allowedRoles)) {
        $role = 'kitchen';
    }

    $errors = [];
    if ($fullname === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($username === '') $errors[] = 'Username is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    $passwordErrors = validate_password($password);
    $errors = array_merge($errors, $passwordErrors);

    // Check unique username / email
    $check = db()->prepare('SELECT COUNT(*) c FROM users WHERE username=? OR email=?');
    $check->execute([$username, $email]);
    if ((int)$check->fetch()['c'] > 0) $errors[] = 'Username or email already exists.';

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = db()->prepare('INSERT INTO users (fullname, email, username, password, role, status, is_verified) VALUES (?, ?, ?, ?, ?, ?, 1)');
        $stmt->execute([$fullname, $email, $username, $hash, $role, 'active']);
        log_activity('Staff', 'Added staff: ' . $username);
        $_SESSION['success'] = 'Staff account created.';
    } else {
        $_SESSION['error'] = implode(' ', $errors);
    }
}
header('Location: ' . BASE_URL . 'admin/staff/accounts.php');
exit;
