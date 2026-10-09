<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id && $id !== $_SESSION['user_id']) {
    db()->prepare('DELETE FROM users WHERE user_id=?')->execute([$id]);
    log_activity('Staff', 'Deleted staff account ID ' . $id);
    $_SESSION['success'] = 'Staff account deleted.';
}
header('Location: ' . BASE_URL . 'admin/staff/accounts.php');
exit;
