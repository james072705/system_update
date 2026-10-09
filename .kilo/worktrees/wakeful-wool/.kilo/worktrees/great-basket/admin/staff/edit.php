<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['user_id'] ?? 0);
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? 'staff';
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';

    // Only staff/kitchen roles allowed — admin role is protected (only ONE admin manages)
    // If the target user is already an admin, preserve their admin role.
    $target = db()->prepare('SELECT role FROM users WHERE user_id = ?');
    $target->execute([$id]);
    $targetRole = $target->fetchColumn();
    if ($targetRole === 'admin') {
        $role = 'admin'; // keep the admin role as it is
    } else {
        $allowedRoles = ['staff', 'kitchen'];
        if (!in_array($role, $allowedRoles)) {
            $role = 'staff';
        }
    }

    $errors = [];
    if ($fullname === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($username === '') $errors[] = 'Username is required.';

    $check = db()->prepare('SELECT COUNT(*) c FROM users WHERE (username=? OR email=?) AND user_id != ?');
    $check->execute([$username, $email, $id]);
    if ((int)$check->fetch()['c'] > 0) $errors[] = 'Username or email already taken.';

    if (empty($errors)) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare('UPDATE users SET fullname=?, email=?, username=?, role=?, status=?, password=? WHERE user_id=?');
            $stmt->execute([$fullname, $email, $username, $role, $status, $hash, $id]);
        } else {
            $stmt = db()->prepare('UPDATE users SET fullname=?, email=?, username=?, role=?, status=? WHERE user_id=?');
            $stmt->execute([$fullname, $email, $username, $role, $status, $id]);
        }
        log_activity('Staff', 'Updated staff account ID ' . $id);
        $_SESSION['success'] = 'Staff account updated.';
    } else {
        $_SESSION['error'] = implode(' ', $errors);
    }
}
header('Location: ' . BASE_URL . 'admin/staff/accounts.php');
exit;
