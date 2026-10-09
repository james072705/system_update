<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role(['admin', 'staff']);

$pageTitle = 'Monthly Sales Report';
$currentPage = 'reports';

$month = $_GET['month'] ?? date('Y-m');
$sales = db()->prepare('SELECT * FROM sales WHERE DATE_FORMAT(sale_date, "%Y-%m") = ? ORDER BY sale_date DESC');
$sales->execute([$month]);
$sales = $sales->fetchAll();

$total = array_sum(array_column($sales, 'total_amount'));
$count = count($sales);

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Monthly Revenue</div><div class="stat-value text-grad-primary"><?= format_currency($total) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Transactions</div><div class="stat-value"><?= format_number($count) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Avg Daily</div><div class="stat-value"><?= count($sales) ? format_currency($total / count($sales)) : '₱0.00' ?></div></div></div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-calendar-month me-2"></i>Monthly Sales</span>
        <form method="GET" class="d-flex gap-2">
            <input type="month" name="month" value="<?= e($month) ?>" class="form-control form-control-sm">
            <button class="btn btn-sm btn-soft"><i class="bi bi-search"></i></button>
            <a href="export_pdf.php?month=<?= e($month) ?>" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        </form>
    </div>
    <div class="card-body">
        <?php if (empty($sales)): ?>
            <div class="empty-state"><i class="bi bi-inbox"></i><p>No sales this month.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Invoice</th><th>Date</th><th>Cashier</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($sales as $s): ?>
                        <tr>
                            <td><strong><?= e($s['invoice_number']) ?></strong></td>
                            <td><?= format_datetime($s['sale_date']) ?></td>
                            <td><?= e($s['cashier']) ?></td>
                            <td class="text-end fw-bold"><?= format_currency($s['total_amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-primary"><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold"><?= format_currency($total) ?></td></tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
