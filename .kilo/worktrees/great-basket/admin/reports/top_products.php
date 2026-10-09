<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role(['admin', 'staff']);

$pageTitle = 'Top Selling Products';
$currentPage = 'reports';

$period = $_GET['period'] ?? 'month';
$dateCond = match($period) {
    'today'   => 'DATE(s.sale_date)=CURDATE()',
    'week'    => 'YEARWEEK(s.sale_date,1)=YEARWEEK(CURDATE(),1)',
    'month'   => 'MONTH(s.sale_date)=MONTH(CURDATE()) AND YEAR(s.sale_date)=YEAR(CURDATE())',
    'year'    => 'YEAR(s.sale_date)=YEAR(CURDATE())',
    default   => 'MONTH(s.sale_date)=MONTH(CURDATE()) AND YEAR(s.sale_date)=YEAR(CURDATE())'
};

$products = db()->query(
    "SELECT p.product_name, c.category_name, SUM(si.quantity) qty, SUM(si.subtotal) revenue
     FROM sales_items si
     JOIN sales s ON s.sale_id = si.sale_id
     JOIN products p ON p.product_id = si.product_id
     LEFT JOIN categories c ON c.category_id = p.category_id
     WHERE $dateCond
     GROUP BY si.product_id, p.product_name, c.category_name
     ORDER BY qty DESC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-trophy me-2"></i>Top Selling Products</span>
        <form method="GET" class="d-flex gap-2">
            <select name="period" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="today" <?= $period==='today'?'selected':'' ?>>Today</option>
                <option value="week" <?= $period==='week'?'selected':'' ?>>This Week</option>
                <option value="month" <?= $period==='month'?'selected':'' ?>>This Month</option>
                <option value="year" <?= $period==='year'?'selected':'' ?>>This Year</option>
            </select>
            <a href="export_pdf.php?type=top&period=<?= e($period) ?>" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        </form>
    </div>
    <div class="card-body">
        <?php if (empty($products)): ?>
            <div class="empty-state"><i class="bi bi-inbox"></i><p>No sales data.</p></div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="chart-box"><canvas id="topChart"></canvas></div>
                </div>
                <div class="col-lg-7">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>#</th><th>Product</th><th>Category</th><th class="text-center">Qty Sold</th><th class="text-end">Revenue</th></tr></thead>
                            <tbody>
                            <?php foreach ($products as $i => $p): ?>
                                <tr>
                                    <td><span class="badge bg-soft-primary"><?= $i+1 ?></span></td>
                                    <td class="fw-bold"><?= e($p['product_name']) ?></td>
                                    <td><span class="badge bg-soft-info"><?= e($p['category_name']) ?></span></td>
                                    <td class="text-center"><?= (int)$p['qty'] ?></td>
                                    <td class="text-end fw-bold"><?= format_currency($p['revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const topLabels = <?= json_encode(array_column($products, 'product_name')) ?>;
const topData = <?= json_encode(array_map('intval', array_column($products, 'qty'))) ?>;
document.addEventListener('DOMContentLoaded', () => {
    createDoughnutChart('topChart', topLabels, topData);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
