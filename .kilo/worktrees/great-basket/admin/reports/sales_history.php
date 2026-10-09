<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role(['admin', 'staff']);

$pageTitle = 'Sales History';
$currentPage = 'sales_history';

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-t');

$sales = db()->prepare('SELECT * FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? ORDER BY sale_date DESC');
$sales->execute([$from, $to]);
$sales = $sales->fetchAll();

$total = array_sum(array_column($sales, 'total_amount'));
$count = count($sales);

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Total Revenue</div><div class="stat-value text-grad-primary"><?= format_currency($total) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Transactions</div><div class="stat-value"><?= format_number($count) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Average Sale</div><div class="stat-value"><?= $count ? format_currency($total / $count) : '₱0.00' ?></div></div></div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-clock-history me-2"></i>Historical Sales</span>
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <input type="date" name="from" value="<?= e($from) ?>" class="form-control form-control-sm" required>
            <input type="date" name="to" value="<?= e($to) ?>" class="form-control form-control-sm" required>
            <button class="btn btn-sm btn-soft"><i class="bi bi-search"></i></button>
            <a href="export_pdf.php?type=report&from=<?= e($from) ?>&to=<?= e($to) ?>" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
            <a href="export_excel.php?type=report&from=<?= e($from) ?>&to=<?= e($to) ?>" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
        </form>
    </div>
    <div class="card-body">
        <?php if (empty($sales)): ?>
            <div class="empty-state"><i class="bi bi-inbox"></i><p>No sales found for this date range.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Invoice</th><th>Date</th><th>Cashier</th><th>Payment</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($sales as $s): ?>
                        <tr>
                            <td><strong><?= e($s['invoice_number']) ?></strong></td>
                            <td><?= format_datetime($s['sale_date']) ?></td>
                            <td><?= e($s['cashier']) ?></td>
                            <td><?= e($s['payment_method'] ?? 'Cash') ?></td>
                            <td class="text-end fw-bold"><?= format_currency($s['total_amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-primary"><td colspan="4" class="text-end fw-bold">Total</td><td class="text-end fw-bold"><?= format_currency($total) ?></td></tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
