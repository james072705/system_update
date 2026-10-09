<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role(['admin', 'staff']);

$pageTitle = 'Stock Movement Report';
$currentPage = 'reports';

$movements = db()->query(
    'SELECT sm.*, p.product_name, u.username
     FROM stock_movements sm
     LEFT JOIN products p ON p.product_id = sm.product_id
     LEFT JOIN users u ON u.user_id = sm.user_id
     ORDER BY sm.date DESC')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2"></i>Stock Movement Report</span>
        <a href="export_pdf.php?type=stock" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Date</th><th>Product</th><th>Type</th><th class="text-center">Qty</th><th>Remarks</th><th>User</th></tr></thead>
                <tbody>
                <?php foreach ($movements as $m): ?>
                    <tr>
                        <td><?= format_datetime($m['date']) ?></td>
                        <td class="fw-bold"><?= e($m['product_name'] ?? '—') ?></td>
                        <td><span class="badge <?= $m['movement_type']==='in'?'bg-soft-success':($m['movement_type']==='sale'?'bg-soft-info':'bg-soft-warning') ?>"><?= ucfirst($m['movement_type']) ?></span></td>
                        <td class="text-center"><?= (int)$m['quantity'] ?></td>
                        <td><?= e($m['remarks']) ?></td>
                        <td><?= e($m['username'] ?? 'System') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
