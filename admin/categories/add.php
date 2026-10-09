
<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['category_name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-tag');

    if ($name === '') {
        $_SESSION['error'] = 'Category name is required.';
    } else {
        $stmt = db()->prepare('INSERT INTO categories (category_name, icon) VALUES (?, ?)');
        $stmt->execute([$name, $icon]);
        log_activity('Category', 'Added category: ' . $name);
        $_SESSION['success'] = 'Category added successfully.';
    }
}
header('Location: ' . BASE_URL . 'admin/categories/list.php');
exit;
