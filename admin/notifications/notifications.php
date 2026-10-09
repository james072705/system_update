<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role(['admin', 'staff']);

// Mark all as read on view (current user's notifications only)
$user = current_user();
if ($user) {
    db()->prepare("UPDATE notifications SET status='read' WHERE status='unread' AND (user_id = ? OR user_id IS NULL)")->execute([$user['user_id']]);
} else {
    db()->exec("UPDATE notifications SET status='read' WHERE status='unread' AND user_id IS NULL");
}

$pageTitle = 'Notifications';
$currentPage = 'notifications';

$type = $_GET['type'] ?? '';
$sql = 'SELECT * FROM notifications WHERE (user_id = :user_id OR user_id IS NULL)';
$params = [':user_id' => $user ? $user['user_id'] : 0];
if ($type !== '') {
    $sql .= ' AND type = :type';
    $params[':type'] = $type;
}
$sql .= ' ORDER BY created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$notifications = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-bell me-2"></i>Notifications</span>
        <div class="d-flex gap-2">
            <a href="?type=low_stock" class="btn btn-sm btn-outline-warning">Low Stock</a>
            <a href="?type=expiry" class="btn btn-sm btn-outline-info">Expiry</a>
            <a href="?type=expired" class="btn btn-sm btn-outline-danger">Expired</a>
            <a href="?type=forecast" class="btn btn-sm btn-outline-success">Forecast</a>
            <a href="notifications.php" class="btn btn-sm btn-soft">All</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($notifications)): ?>
            <div class="empty-state"><i class="bi bi-bell-slash"></i><p class="mb-0">No notifications.</p></div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($notifications as $n):
                    $icon = match($n['type']) {
                        'low_stock' => 'bi-exclamation-triangle text-warning',
                        'expiry'    => 'bi-calendar-x text-info',
                        'expired'   => 'bi-x-circle text-danger',
                        'forecast'  => 'bi-graph-up text-success',
                        default     => 'bi-info-circle text-primary'
                    };
                ?>
                <div class="list-group-item d-flex align-items-start gap-3">
                    <div class="fs-4"><i class="bi <?= $icon ?>"></i></div>
                    <div class="flex-grow-1">
                        <div class="fw-bold"><?= ucfirst(str_replace('_',' ',$n['type'])) ?></div>
                        <div><?= e($n['message']) ?></div>
                        <small class="text-muted"><i class="bi bi-clock"></i> <?= format_datetime($n['created_at']) ?></small>
                    </div>
                    <span class="badge <?= $n['status'] === 'unread' ? 'bg-soft-primary' : 'bg-soft-secondary' ?>"><?= $n['status'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
