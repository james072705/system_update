<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role(['admin', 'staff']);

$pageTitle = 'Sales Forecast Report';
$currentPage = 'reports';

$forecasts = db()->query(
    'SELECT f.*, p.product_name FROM forecasts f
     JOIN products p ON p.product_id=f.product_id
     ORDER BY f.forecast_date DESC')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-graph-up-arrow me-2"></i>Sales Forecast Report</span>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>admin/forecast/forecast.php" class="btn btn-sm btn-primary"><i class="bi bi-robot"></i> Run Forecast</a>
            <a href="export_pdf.php?type=forecast" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($forecasts)): ?>
            <div class="empty-state"><i class="bi bi-robot"></i><p>No forecast data yet. Run the Python forecast module.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Product</th><th>Forecast Date</th><th class="text-end">Predicted Sales</th><th class="text-end">Reorder Qty</th></tr></thead>
                    <tbody>
                    <?php foreach ($forecasts as $f): ?>
                        <tr>
                            <td class="fw-bold"><?= e($f['product_name']) ?></td>
                            <td><?= format_date($f['forecast_date']) ?></td>
                            <td class="text-end"><?= format_number($f['predicted_sales']) ?></td>
                            <td class="text-end"><span class="badge bg-soft-success"><?= (int)$f['reorder_quantity'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
