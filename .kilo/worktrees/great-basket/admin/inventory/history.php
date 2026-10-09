<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$pageTitle = 'Stock Movement History';
$currentPage = 'inventory';

$movements = db()->query(
    'SELECT sm.*, p.product_name, u.username
     FROM stock_movements sm
     LEFT JOIN products p ON p.product_id = sm.product_id
     LEFT JOIN users u ON u.user_id = sm.user_id
     ORDER BY sm.date DESC LIMIT 200')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header"><i class="bi bi-clock-history me-2"></i>Stock Movement History</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Date</th><th>Product</th><th>Type</th><th class="text-center">Qty</th><th>Remarks</th><th>User</th></tr></thead>
                <tbody>
                <?php foreach ($movements as $m): ?>
                    <tr>
                        <td><?= format_datetime($m['date']) ?></td>
                        <td class="fw-bold"><?= e($m['product_name'] ?? '—') ?></td>
                        <td>
                            <span class="badge <?= $m['movement_type'] === 'in' ? 'bg-soft-success' : ($m['movement_type'] === 'sale' ? 'bg-soft-info' : ($m['movement_type'] === 'out' ? 'bg-soft-warning' : 'bg-soft-secondary')) ?>">
                                <?= ucfirst($m['movement_type']) ?>
                            </span>
                        </td>
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
