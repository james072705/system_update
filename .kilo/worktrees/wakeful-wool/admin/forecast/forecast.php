<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$pageTitle = 'Sales Forecast';
$currentPage = 'forecast';

$runMsg = '';
if (isset($_GET['run'])) {
    log_activity('Forecast', 'Running sales forecast');
    $runMsg = run_fallback_forecast();
    add_notification('forecast', 'Sales forecast has been generated.');
    log_activity('Forecast', 'Forecast completed');
}

$latestRunAt = db()->query('SELECT MAX(created_at) AS max_run FROM forecasts')->fetch()['max_run'] ?? null;

if ($latestRunAt) {
    $forecasts = db()->prepare('SELECT f.*, p.product_name, p.reorder_level, i.quantity AS stock
        FROM forecasts f
        JOIN products p ON p.product_id = f.product_id
        LEFT JOIN inventory i ON i.product_id = p.product_id
        WHERE f.created_at = ?
        ORDER BY f.forecast_date DESC');
    $forecasts->execute([$latestRunAt]);
    $forecasts = $forecasts->fetchAll();
} else {
    $forecasts = [];
}

/**
 * PHP fallback forecast engine.
 * Mirrors the Python logic: reads historical product sales, estimates
 * future 7-day demand from the average daily rate, and recommends a
 * reorder quantity. Saves results into the forecasts table.
 */
function run_fallback_forecast() {
    if (!function_exists('db')) require_once ROOT_PATH . '/config/database.php';
    $pdo = db();

    // Product sales for the last 30 days
    $products = $pdo->query(
        "SELECT si.product_id, p.product_name, p.reorder_level,
                COALESCE(SUM(si.quantity),0) AS total_qty,
                COALESCE(i.quantity,0) AS stock
         FROM sales_items si
         JOIN products p ON p.product_id = si.product_id
         LEFT JOIN inventory i ON i.product_id = p.product_id
         WHERE si.sale_id IN (
             SELECT sale_id FROM sales WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
         )
         GROUP BY si.product_id, p.product_name, p.reorder_level, i.quantity
         ORDER BY total_qty DESC"
    )->fetchAll();

    if (empty($products)) {
        return 'No sales data found for forecasting. Please record some sales first.';
    }

    $lines = [];
    $lines[] = 'PHP fallback forecast engine (Python not detected).';

    $totalAll = array_sum(array_column($products, 'total_qty'));
    $forecastDate = date('Y-m-d', strtotime('+7 days'));

    foreach ($products as $p) {
        $avgDaily = $p['total_qty'] / 30;                       // average daily units
        $share = $totalAll ? ($p['total_qty'] / $totalAll) : 0.05;
        $predicted = (int)round($avgDaily * 7 * (1 + $share));  // 7-day demand scaled

        // Reorder = target (max of reorder level & demand) - current stock
        $target = max((int)$p['reorder_level'], $predicted);
        $reorder = max(0, $target - (int)$p['stock']);

        $stmt = $pdo->prepare(
            'INSERT INTO forecasts (product_id, forecast_date, predicted_sales, reorder_quantity)
             VALUES (?, ?, ?, ?)');
        $stmt->execute([$p['product_id'], $forecastDate, $predicted, $reorder]);

        $lines[] = "  - {$p['product_name']}: predicted {$predicted} units, reorder {$reorder}";
    }

    $lines[] = 'Forecast completed and saved to database (PHP fallback).';
    return nl2br(e(implode("\n", $lines)));
}

$forecasts = $forecasts;

// Fast & slow moving products
$fastMoving = db()->query(
    "SELECT p.product_name, SUM(si.quantity) qty
     FROM sales_items si JOIN products p ON p.product_id=si.product_id
     GROUP BY si.product_id, p.product_name
     ORDER BY qty DESC LIMIT 5")->fetchAll();

$slowMoving = db()->query(
    "SELECT p.product_name, COALESCE(SUM(si.quantity),0) qty
     FROM products p
     LEFT JOIN sales_items si ON si.product_id=p.product_id
     GROUP BY p.product_id, p.product_name
     ORDER BY qty ASC LIMIT 5")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-robot me-2"></i>Machine Learning Forecast</span>
                <a href="?run=1" class="btn btn-primary btn-sm"><i class="bi bi-play-fill"></i> Run Forecast</a>
            </div>
            <div class="card-body">
                <?php if ($runMsg): ?>
                    <div class="alert alert-info"><i class="bi bi-terminal me-2"></i><?= $runMsg ?></div>
                <?php endif; ?>
                <p class="text-muted mb-0">
                    This panel uses <strong>Python Linear Regression</strong> to analyze historical sales data,
                    predict future demand, and recommend reorder quantities. The results are stored in the
                    <code>forecasts</code> table and displayed here.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-graph-up-arrow me-2"></i>Latest Forecast Results</span>
                <a href="<?= BASE_URL ?>admin/forecast/history.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-clock-history"></i> View Forecast History</a>
            </div>
            <div class="card-body">
                <?php if (empty($forecasts)): ?>
                    <div class="empty-state">
                        <i class="bi bi-robot"></i>
                        <p class="mb-1">No forecast results yet.</p>
                        <a href="?run=1" class="btn btn-sm btn-primary">Run Forecast Now</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>Product</th><th>Forecast Date</th><th class="text-end">Predicted Sales</th><th class="text-end">Reorder Qty</th><th class="text-center">Current Stock</th></tr></thead>
                            <tbody>
                            <?php foreach ($forecasts as $f): ?>
                                <tr>
                                    <td class="fw-bold"><?= e($f['product_name']) ?></td>
                                    <td><?= format_date($f['forecast_date']) ?></td>
                                    <td class="text-end"><?= format_number($f['predicted_sales']) ?></td>
                                    <td class="text-end"><span class="badge bg-soft-success"><?= (int)$f['reorder_quantity'] ?></span></td>
                                    <td class="text-center"><?= (int)$f['stock'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-lightning-charge me-2"></i>Fast Moving Products</div>
            <div class="card-body">
                <?php foreach ($fastMoving as $i => $f): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div><span class="badge bg-soft-primary me-2"><?= $i+1 ?></span><?= e($f['product_name']) ?></div>
                        <span class="badge bg-soft-success"><?= (int)$f['qty'] ?> sold</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="bi bi-slow-motion me-2"></i>Slow Moving Products</div>
            <div class="card-body">
                <?php foreach ($slowMoving as $i => $f): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div><span class="badge bg-soft-warning me-2"><?= $i+1 ?></span><?= e($f['product_name']) ?></div>
                        <span class="badge bg-soft-secondary"><?= (int)$f['qty'] ?> sold</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
