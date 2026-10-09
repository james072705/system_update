<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role(['admin', 'staff']);

$pageTitle = 'Yearly Sales Report';
$currentPage = 'reports';

$year = (int)($_GET['year'] ?? date('Y'));
$sales = db()->prepare('SELECT * FROM sales WHERE YEAR(sale_date) = ? ORDER BY sale_date DESC');
$sales->execute([$year]);
$sales = $sales->fetchAll();

$total = array_sum(array_column($sales, 'total_amount'));
$count = count($sales);

// Monthly breakdown
$monthly = array_fill(1, 12, 0);
foreach ($sales as $s) {
    $m = (int)date('n', strtotime($s['sale_date']));
    $monthly[$m] += $s['total_amount'];
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Yearly Revenue</div><div class="stat-value text-grad-primary"><?= format_currency($total) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Transactions</div><div class="stat-value"><?= format_number($count) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Best Month</div><div class="stat-value fs-5"><?= array_sum($monthly) ? date('M', mktime(0,0,0,array_search(max($monthly),$monthly),1)) : '—' ?></div></div></div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-calendar3 me-2"></i>Yearly Sales (<?= $year ?>)</span>
        <form method="GET" class="d-flex gap-2">
            <input type="number" name="year" value="<?= $year ?>" class="form-control form-control-sm" style="width:120px" min="2000" max="2100">
            <button class="btn btn-sm btn-soft"><i class="bi bi-search"></i></button>
            <a href="export_pdf.php?type=yearly&year=<?= $year ?>" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        </form>
    </div>
    <div class="card-body">
        <div class="chart-box mb-4">
            <canvas id="yearlyChart"></canvas>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Month</th><th class="text-end">Sales</th></tr></thead>
                <tbody>
                <?php foreach ($monthly as $m => $amt): ?>
                    <tr>
                        <td><?= date('F', mktime(0,0,0,$m,1)) ?></td>
                        <td class="text-end fw-bold"><?= format_currency($amt) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const yearlyLabels = <?= json_encode(array_map(fn($m) => date('M', mktime(0,0,0,$m,1)), range(1,12))) ?>;
const yearlyData = <?= json_encode(array_values($monthly)) ?>;
document.addEventListener('DOMContentLoaded', () => {
    createBarChart('yearlyChart', yearlyLabels, [{
        label: 'Sales (₱)',
        data: yearlyData,
        backgroundColor: '#f4a261',
        borderRadius: 8
    }]);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
