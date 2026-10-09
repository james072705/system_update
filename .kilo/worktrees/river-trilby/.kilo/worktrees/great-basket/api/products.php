<?php
/**
 * Products API - returns active products optionally filtered by category
 * Supports action=view&product_id= for single product detail
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'view') {
    $id = (int)($_GET['product_id'] ?? 0);
    $stmt = db()->prepare('SELECT p.*, c.category_name, i.quantity AS stock FROM products p LEFT JOIN categories c ON c.category_id=p.category_id LEFT JOIN inventory i ON i.product_id=p.product_id WHERE p.product_id=?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) { echo json_encode(['success' => false, 'message' => 'Product not found']); exit; }
    echo json_encode(['success' => true, 'product' => $product]);
    exit;
}

$catId = (int)($_GET['category_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

$sql = "SELECT p.*, c.category_name, i.quantity AS stock
        FROM products p
        LEFT JOIN categories c ON c.category_id = p.category_id
        LEFT JOIN inventory i ON i.product_id = p.product_id
        WHERE p.status = 'active'";
$params = [];

if ($catId > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $catId;
}
if ($search !== '') {
    $sql .= " AND p.product_name LIKE ?";
    $params[] = '%' . $search . '%';
}
$sql .= " ORDER BY p.product_name ASC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll());
