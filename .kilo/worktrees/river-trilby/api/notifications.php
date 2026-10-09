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
        $user = current_user();
        if ($user) {
            $stmt = db()->prepare("SELECT COUNT(*) AS c FROM notifications WHERE status = 'unread' AND (user_id = ? OR user_id IS NULL)");
            $stmt->execute([$user['user_id']]);
        } else {
            $stmt = db()->query("SELECT COUNT(*) AS c FROM notifications WHERE status = 'unread' AND user_id IS NULL");
        }
        echo json_encode(['count' => (int)$stmt->fetch()['c']]);
        break;

    case 'list':
        $user = current_user();
        if ($user) {
            $stmt = db()->prepare('SELECT * FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY created_at DESC LIMIT 20');
            $stmt->execute([$user['user_id']]);
        } else {
            $stmt = db()->query('SELECT * FROM notifications WHERE user_id IS NULL ORDER BY created_at DESC LIMIT 20');
        }
        echo json_encode($stmt->fetchAll());
        break;

    case 'mark_read':
        $user = current_user();
        if ($user) {
            db()->prepare("UPDATE notifications SET status = 'read' WHERE status = 'unread' AND (user_id = ? OR user_id IS NULL)")->execute([$user['user_id']]);
        } else {
            db()->exec("UPDATE notifications SET status = 'read' WHERE status = 'unread' AND user_id IS NULL");
        }
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
