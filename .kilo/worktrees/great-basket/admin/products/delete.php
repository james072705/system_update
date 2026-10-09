<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id) {
    $p = db()->prepare('SELECT product_name FROM products WHERE product_id=?');
    $p->execute([$id]);
    $row = $p->fetch();
    $name = $row['product_name'] ?? 'Product';

    // Check if product has sales/order history — if so, soft-delete (set inactive)
    $hasHistory = db()->prepare(
        'SELECT COUNT(*) AS c FROM sales_items WHERE product_id=? UNION SELECT COUNT(*) AS c FROM order_items WHERE product_id=?');
    $hasHistory->execute([$id, $id]);
    $historyCount = (int)$hasHistory->fetch()['c'];

    if ($historyCount > 0) {
        // Soft-delete: just set status to inactive
        db()->prepare("UPDATE products SET status='inactive' WHERE product_id=?")->execute([$id]);
        log_activity('Product', 'Soft-deleted (set inactive): ' . $name);
        $_SESSION['warning'] = 'Product "' . $name . '" has sales history and was set to inactive instead of deleted.';
    } else {
        // No history — safe to hard-delete
        db()->prepare('DELETE FROM products WHERE product_id=?')->execute([$id]);
        log_activity('Product', 'Deleted product: ' . $name);
        $_SESSION['success'] = 'Product "' . $name . '" deleted.';
    }
}
header('Location: ' . BASE_URL . 'admin/products/list.php');
exit;
