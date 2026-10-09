<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? 'Stock out');

    if ($product_id > 0 && $quantity > 0) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $check = $pdo->prepare('SELECT quantity FROM inventory WHERE product_id=?');
            $check->execute([$product_id]);
            $current = $check->fetch()['quantity'] ?? 0;

            if ($quantity > $current) {
                throw new Exception('Cannot remove more than current stock.');
            }

            $pdo->prepare('UPDATE inventory SET quantity = quantity - ? WHERE product_id=?')->execute([$quantity, $product_id]);
            $pdo->prepare('INSERT INTO stock_movements (product_id, movement_type, quantity, remarks, user_id) VALUES (?, "out", ?, ?, ?)')
                ->execute([$product_id, $quantity, $remarks, $_SESSION['user_id']]);
            $pdo->commit();
            log_activity('Inventory', 'Stock out ' . $quantity . ' for product ' . $product_id);
            $_SESSION['success'] = 'Stock removed successfully.';
            scan_inventory_alerts();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
    }
}
header('Location: ' . BASE_URL . 'admin/inventory/inventory.php');
exit;
