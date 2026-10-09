<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$pageTitle = 'Expiring Products';
$currentPage = 'inventory';

$expiring = db()->query(
    "SELECT p.*, c.category_name, i.quantity AS stock
     FROM products p LEFT JOIN categories c ON c.category_id=p.category_id
     LEFT JOIN inventory i ON i.product_id=p.product_id
     WHERE p.expiration_date IS NOT NULL
       AND p.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY p.expiration_date ASC")->fetchAll();

$expired = db()->query(
    "SELECT p.*, c.category_name, i.quantity AS stock
     FROM products p LEFT JOIN categories c ON c.category_id=p.category_id
     LEFT JOIN inventory i ON i.product_id=p.product_id
     WHERE p.expiration_date IS NOT NULL AND p.expiration_date < CURDATE()
     ORDER BY p.expiration_date ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-calendar-x me-2"></i>Expiring Soon (30 days)</div>
            <div class="card-body">
                <?php if (empty($expiring)): ?>
                    <div class="empty-state"><i class="bi bi-check2-circle"></i><p class="mb-0">No products expiring soon.</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Product</th><th class="text-center">Stock</th><th>Expires</th><th>Days Left</th></tr></thead>
                            <tbody>
                            <?php foreach ($expiring as $p):
                                $daysLeft = days_until_expiry($p['expiration_date']);
                            ?>
                                <tr>
                                    <td class="fw-bold"><?= e($p['product_name']) ?></td>
                                    <td class="text-center"><?= (int)$p['stock'] ?></td>
                                    <td><span class="badge bg-soft-warning"><?= format_date($p['expiration_date']) ?></span></td>
                                    <td>
                                        <?php if ($daysLeft <= 3): ?>
                                            <span class="badge bg-soft-danger fw-bold"><i class="bi bi-exclamation-triangle"></i> <?= e(expiry_label($p['expiration_date'])) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-soft-warning fw-bold"><?= e(expiry_label($p['expiration_date'])) ?></span>
                                        <?php endif; ?>
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
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-x-circle me-2"></i>Expired Products</div>
            <div class="card-body">
                <?php if (empty($expired)): ?>
                    <div class="empty-state"><i class="bi bi-check2-circle"></i><p class="mb-0">No expired products.</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Product</th><th class="text-center">Stock</th><th>Expired</th></tr></thead>
                            <tbody>
                            <?php foreach ($expired as $p): ?>
                                <tr>
                                    <td class="fw-bold"><?= e($p['product_name']) ?></td>
                                    <td class="text-center"><?= (int)$p['stock'] ?></td>
                                    <td><span class="badge bg-soft-danger"><?= format_date($p['expiration_date']) ?></span></td>
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
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
