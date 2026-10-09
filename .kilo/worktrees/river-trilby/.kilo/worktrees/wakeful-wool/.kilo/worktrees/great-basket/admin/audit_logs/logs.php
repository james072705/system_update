<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$pageTitle = 'Audit Logs';
$currentPage = 'logs';

$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$where = '1=1';
$params = [];

if ($dateFrom !== '') {
    $where .= ' AND DATE(log_date) >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where .= ' AND DATE(log_date) <= :date_to';
    $params[':date_to'] = $dateTo;
}

$logs = db()->prepare(
    'SELECT al.*, u.username FROM audit_logs al
     LEFT JOIN users u ON u.user_id = al.user_id
     WHERE ' . $where . '
     ORDER BY al.log_date DESC LIMIT 200'
);
$logs->execute($params);
$logs = $logs->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-journal-text me-2"></i>Audit Logs</span>
        <form method="GET" class="d-flex gap-1 flex-wrap align-items-center">
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="form-control form-control-sm" style="width:auto;" placeholder="From">
            <span class="text-muted">to</span>
            <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="form-control form-control-sm" style="width:auto;" placeholder="To">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
            <?php if ($dateFrom !== '' || $dateTo !== ''): ?>
                <a href="?" class="btn btn-sm btn-soft"><i class="bi bi-x"></i></a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>admin/audit_logs/export_pdf.php?date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Date</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td class="text-nowrap"><?= format_datetime($l['log_date']) ?></td>
                        <td><span class="badge bg-soft-info"><?= e($l['username'] ?? 'System') ?></span></td>
                        <td class="fw-bold"><?= e($l['action']) ?></td>
                        <td><?= e($l['details']) ?></td>
                        <td class="text-muted"><?= e($l['ip_address']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
