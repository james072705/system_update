<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role(['admin', 'staff']);

$pageTitle = 'Weekly Sales Report';
$currentPage = 'reports';

$week = $_GET['week'] ?? date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week', strtotime($week)));
$weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($week)));

$sales = db()->prepare('SELECT * FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? ORDER BY sale_date DESC');
$sales->execute([$weekStart, $weekEnd]);
$sales = $sales->fetchAll();

$total = array_sum(array_column($sales, 'total_amount'));
$count = count($sales);

// Daily breakdown
$daily = [];
for ($i = 0; $i < 7; $i++) {
    $d = date('Y-m-d', strtotime("$weekStart +$i days"));
    $daily[$d] = 0;
}
foreach ($sales as $s) {
    $d = date('Y-m-d', strtotime($s['sale_date']));
    if (isset($daily[$d])) $daily[$d] += $s['total_amount'];
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Weekly Revenue</div><div class="stat-value text-grad-primary"><?= format_currency($total) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Transactions</div><div class="stat-value"><?= format_number($count) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Peak Day</div><div class="stat-value fs-5"><?= $daily ? date('D', strtotime(array_search(max($daily), $daily))) : '—' ?></div></div></div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-calendar-week me-2"></i>Weekly Sales (<?= format_date($weekStart) ?> - <?= format_date($weekEnd) ?>)</span>
        <form method="GET" class="d-flex gap-2">
            <input type="date" name="week" value="<?= e($week) ?>" class="form-control form-control-sm">
            <button class="btn btn-sm btn-soft"><i class="bi bi-search"></i></button>
            <a href="export_pdf.php?week=<?= e($week) ?>" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        </form>
    </div>
    <div class="card-body">
        <div class="chart-box mb-4">
            <canvas id="weeklyChart"></canvas>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Day</th><th class="text-end">Sales</th></tr></thead>
                <tbody>
                <?php foreach ($daily as $d => $amt): ?>
                    <tr>
                        <td><?= date('D, M d', strtotime($d)) ?></td>
                        <td class="text-end fw-bold"><?= format_currency($amt) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const weeklyLabels = <?= json_encode(array_map(fn($d) => date('D', strtotime($d)), array_keys($daily))) ?>;
const weeklyData = <?= json_encode(array_values($daily)) ?>;
document.addEventListener('DOMContentLoaded', () => {
    createBarChart('weeklyChart', weeklyLabels, [{
        label: 'Sales (₱)',
        data: weeklyData,
        backgroundColor: '#2a9d8f',
        borderRadius: 8
    }]);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
