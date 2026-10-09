<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    // Check if category still has products
    $cnt = db()->prepare('SELECT COUNT(*) AS c FROM products WHERE category_id=?');
    $cnt->execute([$id]);
    $productCount = (int)$cnt->fetch()['c'];

    if ($productCount > 0) {
        $_SESSION['error'] = 'Cannot delete this category because it still has ' . $productCount . ' product(s). Move or delete those products first.';
    } else {
        db()->prepare('DELETE FROM categories WHERE category_id=?')->execute([$id]);
        log_activity('Category', 'Deleted category ID ' . $id);
        $_SESSION['success'] = 'Category deleted.';
    }
}
header('Location: ' . BASE_URL . 'admin/categories/list.php');
exit;
