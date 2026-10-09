<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$pageTitle = 'Forecast Charts';
$currentPage = 'forecast';

$forecasts = db()->query(
    'SELECT f.*, p.product_name FROM forecasts f
     JOIN products p ON p.product_id=f.product_id
     ORDER BY f.forecast_date ASC')->fetchAll();

$labels = array_map(fn($f) => $f['product_name'] . ' (' . $f['forecast_date'] . ')', $forecasts);
$predicted = array_map(fn($f) => (float)$f['predicted_sales'], $forecasts);
$reorder = array_map(fn($f) => (int)$f['reorder_quantity'], $forecasts);

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-graph-up-arrow me-2"></i>Predicted Sales</div>
            <div class="card-body">
                <div class="chart-box"><canvas id="predictedChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-basket me-2"></i>Suggested Reorder Quantity</div>
            <div class="card-body">
                <div class="chart-box"><canvas id="reorderChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<script>
const fLabels = <?= json_encode($labels) ?>;
const fPredicted = <?= json_encode($predicted) ?>;
const fReorder = <?= json_encode($reorder) ?>;

document.addEventListener('DOMContentLoaded', () => {
    createBarChart('predictedChart', fLabels, [{
        label: 'Predicted Sales',
        data: fPredicted,
        backgroundColor: '#e63946',
        borderRadius: 8
    }]);
    createBarChart('reorderChart', fLabels, [{
        label: 'Reorder Qty',
        data: fReorder,
        backgroundColor: '#2a9d8f',
        borderRadius: 8
    }]);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
