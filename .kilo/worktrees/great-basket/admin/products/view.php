<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
$product = db()->prepare(
    'SELECT p.*, c.category_name, i.quantity AS stock FROM products p
     LEFT JOIN categories c ON c.category_id=p.category_id
     LEFT JOIN inventory i ON i.product_id=p.product_id
     WHERE p.product_id=?');
$product->execute([$id]);
$product = $product->fetch();

if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    header('Location: ' . BASE_URL . 'admin/products/list.php');
    exit;
}

$movements = db()->prepare(
    'SELECT * FROM stock_movements WHERE product_id=? ORDER BY date DESC LIMIT 10');
$movements->execute([$id]);
$movements = $movements->fetchAll();

$pageTitle = 'Product Details';
$currentPage = 'products';
require_once __DIR__ . '/../../includes/header.php';
$daysLeft = $product['expiration_date'] ? days_until_expiry($product['expiration_date']) : null;
?>
<?php if ($product['status'] === 'inactive' && $daysLeft !== null && $daysLeft <= 5): ?>
    <div class="alert alert-danger d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-octagon fs-5"></i>
        <div>
            <strong>This product was automatically deactivated.</strong>
            <div class="small">It has only <?= max($daysLeft, 0) ?> day(s) left before its expiration date (<?= format_date($product['expiration_date']) ?>).
            To sell it again, restock with fresh inventory and re-activate it in the product settings.</div>
        </div>
    </div>
<?php endif; ?>
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if ($product['image'] && file_exists(__DIR__ . '/../../assets/uploads/products/' . $product['image'])): ?>
                    <img src="<?= BASE_URL ?>assets/uploads/products/<?= e($product['image']) ?>" class="rounded-4 mb-3" style="width:100%;max-height:260px;object-fit:cover">
                <?php else: ?>
                    <div class="bg-soft rounded-4 d-grid place-items-center mb-3" style="height:220px"><i class="bi bi-cup-straw fs-1 text-muted"></i></div>
                <?php endif; ?>
                <h4 class="fw-bold"><?= e($product['product_name']) ?></h4>
                <p class="text-muted"><?= e($product['description'] ?? '') ?></p>
                <span class="badge bg-soft-primary"><?= e($product['category_name']) ?></span>
                <span class="badge <?= $product['status'] === 'active' ? 'bg-soft-success' : 'bg-soft-secondary' ?>"><?= ucfirst($product['status']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">Product Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Price</div><div class="stat-value fs-5"><?= format_currency($product['price']) ?></div></div></div>
                    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Current Stock</div><div class="stat-value fs-5"><?= (int)$product['stock'] ?></div></div></div>
                    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Reorder Level</div><div class="stat-value fs-5"><?= (int)$product['reorder_level'] ?></div></div></div>
                    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Expiration</div><div class="stat-value fs-5"><?= $product['expiration_date'] ? format_date($product['expiration_date']) : '—' ?></div></div></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Recent Stock Movements</div>
            <div class="card-body">
                <?php if (empty($movements)): ?>
                    <div class="empty-state"><i class="bi bi-inbox"></i><p class="mb-0">No movements.</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Type</th><th class="text-center">Qty</th><th>Remarks</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php foreach ($movements as $m): ?>
                                <tr>
                                    <td><span class="badge <?= $m['movement_type'] === 'in' ? 'bg-soft-success' : ($m['movement_type'] === 'sale' ? 'bg-soft-info' : 'bg-soft-warning') ?>"><?= ucfirst($m['movement_type']) ?></span></td>
                                    <td class="text-center"><?= (int)$m['quantity'] ?></td>
                                    <td><?= e($m['remarks']) ?></td>
                                    <td><?= format_datetime($m['date']) ?></td>
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
