<?php
/**
 * Orders API - for kitchen order management
 * Access: kitchen role required
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_role(['kitchen', 'admin', 'staff']);

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
case 'view':
        $order_id = (int)($_GET['order_id'] ?? 0);
        $stmt = db()->prepare('SELECT * FROM orders WHERE order_id = ?');
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
        $items = db()->prepare(
            'SELECT oi.*, p.product_name FROM order_items oi
             LEFT JOIN products p ON p.product_id = oi.product_id
             WHERE oi.order_id = ? ORDER BY oi.item_id ASC');
        $items->execute([$order_id]);
        echo json_encode(['success' => true, 'order' => $order, 'items' => $items->fetchAll()]);
        break;

case 'list':
        $status = $_GET['status'] ?? '';
        $sql = "SELECT o.*, 
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS item_count
                FROM orders o";
        $params = [];
        $where = ["o.payment_status = 'paid'"];
        if ($status !== '') {
            $where[] = "o.status = ?";
            $params[] = $status;
        }
        $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY o.order_date DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        break;

case 'update_status':
        $order_id = (int)($_POST['order_id'] ?? 0);
        $status   = $_POST['status'] ?? '';
        $allowed = ['pending','preparing','ready','completed','cancelled'];
        if (!in_array($status, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            // Fetch the current order
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_id = ?');
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            if (!$order) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }

            // Only PAID orders can be processed by the kitchen
            if ($order['payment_status'] !== 'paid') {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'This order is not yet paid and cannot be prepared.']);
                exit;
            }

            // The sale is ALREADY recorded at payment settlement (admin/payments/settle.php),
            // so here we only update the kitchen workflow status:
            // pending -> preparing -> ready -> completed
            $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
            $stmt->execute([$status, $order_id]);

            // Notification for key transitions
            try {
                $user_id = $_SESSION['user_id'] ?? null;
                $stmt = $pdo->prepare('INSERT INTO notifications (type, message, user_id) VALUES ("info", ?, ?)');
                $stmt->execute(['Order ' . $order['order_number'] . ' is now: ' . ucfirst($status), $user_id]);
            } catch (Exception $e) { /* ignore */ }

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
