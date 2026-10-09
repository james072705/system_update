<?php
/**
 * Inventory API
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'levels':
        $rows = db()->query(
            "SELECT p.product_name, i.quantity, p.reorder_level
             FROM inventory i JOIN products p ON p.product_id = i.product_id
             ORDER BY i.quantity ASC LIMIT 10")->fetchAll();
        echo json_encode($rows);
        break;

    case 'low_stock':
        $rows = db()->query(
            "SELECT p.product_name, i.quantity, p.reorder_level
             FROM inventory i JOIN products p ON p.product_id = i.product_id
             WHERE i.quantity <= p.reorder_level
             ORDER BY i.quantity ASC")->fetchAll();
        echo json_encode($rows);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
