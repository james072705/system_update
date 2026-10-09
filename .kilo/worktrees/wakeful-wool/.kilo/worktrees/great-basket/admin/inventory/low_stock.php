<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$pageTitle = 'Low Stock Products';
$currentPage = 'inventory';

$products = db()->query(
    "SELECT p.*, c.category_name, i.quantity AS stock
     FROM inventory i JOIN products p ON p.product_id=i.product_id
     LEFT JOIN categories c ON c.category_id=p.category_id
     WHERE i.quantity <= p.reorder_level AND p.status='active'
     ORDER BY i.quantity ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header"><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Products</div>
    <div class="card-body">
        <?php if (empty($products)): ?>
            <div class="empty-state"><i class="bi bi-check2-circle"></i><p class="mb-0">All products are above their reorder level. Great job!</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Product</th><th>Category</th><th class="text-center">Current Stock</th><th class="text-center">Reorder Level</th><th>Need to Order</th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $p):
                        $need = max(0, $p['reorder_level'] - $p['stock'] * 2);
                    ?>
                        <tr>
                            <td class="fw-bold"><?= e($p['product_name']) ?></td>
                            <td><span class="badge bg-soft-primary"><?= e($p['category_name']) ?></span></td>
                            <td class="text-center"><span class="badge bg-soft-danger fs-6"><?= (int)$p['stock'] ?></span></td>
                            <td class="text-center"><?= (int)$p['reorder_level'] ?></td>
                            <td><span class="badge bg-soft-warning"><?= max(0, (int)$p['reorder_level'] - (int)$p['stock']) ?> pcs</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
