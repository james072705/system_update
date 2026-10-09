<?php
/**
 * Notifications API
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'count':
        echo json_encode(['count' => unread_notifications_count()]);
        break;

    case 'list':
        $stmt = db()->query('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20');
        echo json_encode($stmt->fetchAll());
        break;

    case 'mark_read':
        db()->exec("UPDATE notifications SET status = 'read' WHERE status = 'unread'");
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
