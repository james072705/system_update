<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$pageTitle = 'Stock Recommendations';
$currentPage = 'forecast';

// Recommendations based on forecast + current stock
$forecasts = db()->query(
    'SELECT f.*, p.product_name, p.reorder_level, i.quantity AS stock
     FROM forecasts f
     JOIN products p ON p.product_id=f.product_id
     LEFT JOIN inventory i ON i.product_id=p.product_id
     ORDER BY f.forecast_date DESC')->fetchAll();

$recs = [];
foreach ($forecasts as $f) {
    $stock = (int)$f['stock'];
    $predicted = (int)$f['predicted_sales'];
    $reorder = (int)$f['reorder_quantity'];
    $need = max(0, $predicted - $stock);
    $recs[] = [
        'product' => $f['product_name'],
        'stock' => $stock,
        'predicted' => $predicted,
        'reorder' => $reorder,
        'need' => $need,
        'priority' => $need > 0 ? ($need > $predicted * 0.5 ? 'high' : 'medium') : 'low'
    ];
}
usort($recs, fn($a, $b) => $b['need'] <=> $a['need']);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Products to Reorder</div><div class="stat-value text-grad-primary"><?= count(array_filter($recs, fn($r) => $r['need'] > 0)) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">High Priority</div><div class="stat-value"><?= count(array_filter($recs, fn($r) => $r['priority']==='high')) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Total Units to Order</div><div class="stat-value"><?= array_sum(array_column($recs, 'need')) ?></div></div></div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-basket me-2"></i>Stock Recommendations</div>
    <div class="card-body">
        <?php if (empty($recs)): ?>
            <div class="empty-state"><i class="bi bi-robot"></i><p>Run the forecast first to generate recommendations.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Product</th><th class="text-center">Current Stock</th><th class="text-center">Predicted Demand</th><th class="text-center">Suggested Reorder</th><th class="text-center">Units to Order</th><th>Priority</th></tr></thead>
                    <tbody>
                    <?php foreach ($recs as $r): ?>
                        <tr>
                            <td class="fw-bold"><?= e($r['product']) ?></td>
                            <td class="text-center"><?= $r['stock'] ?></td>
                            <td class="text-center"><?= $r['predicted'] ?></td>
                            <td class="text-center"><?= $r['reorder'] ?></td>
                            <td class="text-center"><span class="badge bg-soft-primary fs-6"><?= (int)$r['need'] ?></span></td>
                            <td>
                                <?php if ($r['priority'] === 'high'): ?>
                                    <span class="badge bg-soft-danger">High</span>
                                <?php elseif ($r['priority'] === 'medium'): ?>
                                    <span class="badge bg-soft-warning">Medium</span>
                                <?php else: ?>
                                    <span class="badge bg-soft-success">Low</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
