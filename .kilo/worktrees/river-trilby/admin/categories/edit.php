<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['category_name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-tag');

    if ($name !== '') {
        $stmt = db()->prepare('UPDATE categories SET category_name=?, icon=? WHERE category_id=?');
        $stmt->execute([$name, $icon, $id]);
        log_activity('Category', 'Updated category ID ' . $id);
        $_SESSION['success'] = 'Category updated.';
    }
}
header('Location: ' . BASE_URL . 'admin/categories/list.php');
exit;
