<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$pageTitle = 'Forecast History';
$currentPage = 'forecast_history';

$runAt = $_GET['run_at'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$where = '1=1';
$params = [];

if ($dateFrom !== '') {
    $where .= ' AND created_at >= :date_from';
    $params[':date_from'] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where .= ' AND created_at <= :date_to';
    $params[':date_to'] = $dateTo . ' 23:59:59';
}

$runs = db()->prepare("SELECT created_at, forecast_date, COUNT(*) AS products, SUM(predicted_sales) AS total_predicted, SUM(reorder_quantity) AS total_reorder FROM forecasts WHERE {$where} GROUP BY created_at ORDER BY created_at DESC");
$runs->execute($params);
$runs = $runs->fetchAll();

$selectedRun = [];
if ($runAt !== '') {
    $selectedRun = db()->prepare('SELECT f.*, p.product_name, p.reorder_level, i.quantity AS stock FROM forecasts f JOIN products p ON p.product_id = f.product_id LEFT JOIN inventory i ON i.product_id = p.product_id WHERE f.created_at = ? ORDER BY p.product_name');
    $selectedRun->execute([$runAt]);
    $selectedRun = $selectedRun->fetchAll();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-clock-history me-2"></i>Forecast History</span>
                <form method="GET" class="d-flex gap-1 flex-wrap align-items-center">
                    <?php if ($runAt !== ''): ?>
                        <input type="hidden" name="run_at" value="<?= e($runAt) ?>">
                    <?php endif; ?>
                    <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="form-control form-control-sm" style="width:auto;" placeholder="From">
                    <span class="text-muted">to</span>
                    <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="form-control form-control-sm" style="width:auto;" placeholder="To">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
                    <?php if ($dateFrom !== '' || $dateTo !== ''): ?>
                        <a href="<?= $runAt !== '' ? '?run_at=' . urlencode($runAt) : '?' ?>" class="btn btn-sm btn-soft"><i class="bi bi-x"></i></a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>admin/forecast/forecast.php" class="btn btn-sm btn-success"><i class="bi bi-play-fill"></i> Run New Forecast</a>
                </form>
            </div>
            <div class="card-body">
                <?php if (empty($runs)): ?>
                    <div class="empty-state">
                        <i class="bi bi-robot"></i>
                        <p class="mb-1">No forecast history yet.</p>
                        <a href="<?= BASE_URL ?>admin/forecast/forecast.php" class="btn btn-sm btn-primary">Run Your First Forecast</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>Date Run</th><th>Forecast For</th><th class="text-center">Products</th><th class="text-end">Total Predicted</th><th class="text-end">Total Reorder</th><th class="text-center">Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($runs as $r): 
                                $runTime = new DateTime($r['created_at']);
                                $forecastFor = new DateTime($r['forecast_date']);
                            ?>
                                <tr style="<?= $runAt === $r['created_at'] ? 'background-color: rgba(42,157,143,.08);' : '' ?>">
                                    <td class="fw-bold"><?= $runTime->format('M d, Y h:i A') ?></td>
                                    <td><?= $forecastFor->format('M d, Y') ?></td>
                                    <td class="text-center"><?= (int)$r['products'] ?></td>
                                    <td class="text-end"><?= format_number($r['total_predicted']) ?></td>
                                    <td class="text-end"><span class="badge bg-soft-success"><?= (int)$r['total_reorder'] ?></span></td>
                                    <td class="text-center">
                                        <a href="?run_at=<?= urlencode($r['created_at']) ?><?= $dateFrom !== '' ? '&date_from=' . e($dateFrom) : '' ?><?= $dateTo !== '' ? '&date_to=' . e($dateTo) : '' ?>" class="btn btn-sm btn-soft" title="View details"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($runAt !== '' && !empty($selectedRun)): 
    $runTime = new DateTime($selectedRun[0]['created_at']);
?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-list-ul me-2"></i>Forecast Details</span>
                <small class="text-muted">Run on <?= $runTime->format('M d, Y h:i A') ?></small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>Product</th><th class="text-end">Predicted Sales</th><th class="text-end">Reorder Qty</th><th class="text-center">Current Stock</th></tr></thead>
                        <tbody>
                        <?php foreach ($selectedRun as $f): ?>
                            <tr>
                                <td class="fw-bold"><?= e($f['product_name']) ?></td>
                                <td class="text-end"><?= format_number($f['predicted_sales']) ?></td>
                                <td class="text-end"><span class="badge bg-soft-success"><?= (int)$f['reorder_quantity'] ?></span></td>
                                <td class="text-center"><?= (int)$f['stock'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
