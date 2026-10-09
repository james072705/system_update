<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_role(['admin', 'staff']);

$pageTitle = 'Transactions';
$currentPage = 'pos';

$date = $_GET['date'] ?? date('Y-m-d');
$sales = db()->prepare('SELECT * FROM sales WHERE DATE(sale_date)=? ORDER BY sale_date DESC');
$sales->execute([$date]);
$sales = $sales->fetchAll();

$total = array_sum(array_column($sales, 'total_amount'));

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-receipt me-2"></i>Transactions</span>
        <form method="GET" class="d-flex gap-2">
            <input type="date" name="date" value="<?= e($date) ?>" class="form-control form-control-sm">
            <button class="btn btn-sm btn-soft"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body">
        <?php if (empty($sales)): ?>
            <div class="empty-state"><i class="bi bi-inbox"></i><p>No transactions on this date.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Invoice</th><th>Date</th><th>Cashier</th><th>Type</th><th class="text-end">Total</th><th>Receipt</th></tr></thead>
                    <tbody>
                    <?php foreach ($sales as $s): ?>
                        <tr>
                            <td><strong><?= e($s['invoice_number']) ?></strong></td>
                            <td><?= format_datetime($s['sale_date']) ?></td>
                            <td><?= e($s['cashier']) ?></td>
                            <td><span class="badge bg-soft-info"><?= ucfirst(str_replace('_',' ',$s['order_type'])) ?></span></td>
                            <td class="text-end fw-bold"><?= format_currency($s['total_amount']) ?></td>
                            <td><a href="receipt.php?sale_id=<?= (int)$s['sale_id'] ?>" class="btn btn-sm btn-soft"><i class="bi bi-receipt"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-primary"><td colspan="4" class="text-end fw-bold">Total</td><td class="text-end fw-bold"><?= format_currency($total) ?></td><td></td></tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
