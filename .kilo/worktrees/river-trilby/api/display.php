<?php
/**
 * Display API - returns current preparing and ready orders as JSON
 * Used by the display screen for real-time updates
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

// Auto-complete old ready orders when there are 5 or more
$readyCount = (int)db()->query("SELECT COUNT(*) FROM orders WHERE status = 'ready' AND payment_status = 'paid'")->fetchColumn();
if ($readyCount >= 5) {
    $toComplete = $readyCount - 1;
    $stmt = db()->prepare("UPDATE orders SET status = 'completed' WHERE status = 'ready' AND payment_status = 'paid' ORDER BY order_date ASC LIMIT :limit");
    $stmt->bindValue(':limit', $toComplete, PDO::PARAM_INT);
    $stmt->execute();
}

$preparing = db()->query(
    "SELECT order_number FROM orders WHERE status = 'preparing' AND payment_status = 'paid' ORDER BY order_date ASC"
)->fetchAll(PDO::FETCH_COLUMN);

$ready = db()->query(
    "SELECT order_number FROM orders WHERE status = 'ready' AND payment_status = 'paid' ORDER BY order_date ASC"
)->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'preparing' => $preparing,
    'ready' => $ready
]);
exit;
