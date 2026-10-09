<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role(['admin', 'staff']);

$pageTitle = 'Expiry Report';
$currentPage = 'reports';

$products = db()->query(
    "SELECT p.*, c.category_name, i.quantity AS stock
     FROM products p LEFT JOIN categories c ON c.category_id=p.category_id
     LEFT JOIN inventory i ON i.product_id=p.product_id
     WHERE p.expiration_date IS NOT NULL
     ORDER BY p.expiration_date ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar-x me-2"></i>Expiry Report</span>
        <a href="export_pdf.php?type=expiry" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Product</th><th>Category</th><th class="text-center">Stock</th><th>Expiration</th><th>Days Left</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($products as $p):
                    $expired = $p['expiration_date'] < date('Y-m-d');
                    $soon = !$expired && $p['expiration_date'] <= date('Y-m-d', strtotime('+7 days'));
                    $daysLeft = days_until_expiry($p['expiration_date']);
                ?>
                    <tr>
                        <td class="fw-bold"><?= e($p['product_name']) ?></td>
                        <td><span class="badge bg-soft-primary"><?= e($p['category_name']) ?></span></td>
                        <td class="text-center"><?= (int)$p['stock'] ?></td>
                        <td><?= format_date($p['expiration_date']) ?></td>
                        <td>
                            <?php if ($expired): ?>
                                <span class="badge bg-soft-danger"><i class="bi bi-x-circle"></i> <?= e(expiry_label($p['expiration_date'])) ?></span>
                            <?php elseif ($daysLeft <= 3): ?>
                                <span class="badge bg-soft-danger fw-bold"><i class="bi bi-exclamation-triangle"></i> <?= e(expiry_label($p['expiration_date'])) ?></span>
                            <?php elseif ($daysLeft <= 7): ?>
                                <span class="badge bg-soft-warning fw-bold"><i class="bi bi-alarm"></i> <?= e(expiry_label($p['expiration_date'])) ?></span>
                            <?php else: ?>
                                <span class="badge bg-soft-info"><?= e(expiry_label($p['expiration_date'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($expired): ?>
                                <span class="badge bg-soft-danger">Expired</span>
                            <?php elseif ($soon): ?>
                                <span class="badge bg-soft-warning">Expiring Soon</span>
                            <?php else: ?>
                                <span class="badge bg-soft-success">Valid</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
