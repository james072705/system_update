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

// Fetch ready orders with announcement counts (if table exists)
try {
    $readyStmt = db()->query(
        "SELECT o.order_number, COALESCE(oa.announcement_count, 0) AS announcement_count
         FROM orders o
         LEFT JOIN order_announcements oa ON o.order_id = oa.order_id
         WHERE o.status = 'ready' AND o.payment_status = 'paid'
         ORDER BY o.order_date ASC"
    );
    $readyRows = $readyStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $readyRows = [];
}

$ready = array_column($readyRows, 'order_number');
$readyAnnouncements = $readyRows;

// Handle increment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'increment_announcement') {
    $orderNumber = $_POST['order_number'] ?? '';
    if ($orderNumber !== '') {
        $orderIdStmt = db()->prepare("SELECT order_id FROM orders WHERE order_number = ?");
        $orderIdStmt->execute([$orderNumber]);
        $orderId = $orderIdStmt->fetchColumn();

        if ($orderId) {
            try {
                $updateStmt = db()->prepare("UPDATE order_announcements SET announcement_count = announcement_count + 1, last_announced_at = NOW() WHERE order_id = ?");
                $updateStmt->execute([$orderId]);

                if ($updateStmt->rowCount() === 0) {
                    $insertStmt = db()->prepare("INSERT INTO order_announcements (order_id, announcement_type, announcement_count, last_announced_at) VALUES (?, 'ready', 1, NOW())");
                    $insertStmt->execute([$orderId]);
                }
            } catch (Exception $e) {
                // Table may not exist yet; ignore silently
            }
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode([
    'preparing' => $preparing,
    'ready' => $ready,
    'ready_announcements' => $readyAnnouncements
]);
exit;
