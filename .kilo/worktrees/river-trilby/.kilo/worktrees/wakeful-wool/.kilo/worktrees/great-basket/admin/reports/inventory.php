<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role(['admin', 'staff']);

$pageTitle = 'Inventory Report';
$currentPage = 'reports';

$products = db()->query(
    "SELECT p.*, c.category_name, i.quantity AS stock
     FROM products p LEFT JOIN categories c ON c.category_id=p.category_id
     LEFT JOIN inventory i ON i.product_id=p.product_id
     ORDER BY p.product_name")->fetchAll();

$totalValue = 0;
foreach ($products as $p) {
    $totalValue += $p['price'] * $p['stock'];
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Total Products</div><div class="stat-value"><?= count($products) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Total Stock Units</div><div class="stat-value"><?= array_sum(array_map(fn($p) => (int)$p['stock'], $products)) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Inventory Value</div><div class="stat-value text-grad-primary"><?= format_currency($totalValue) ?></div></div></div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clipboard-data me-2"></i>Inventory Report</span>
        <a href="export_pdf.php?type=inventory" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Product</th><th>Category</th><th class="text-center">Price</th><th class="text-center">Stock</th><th class="text-end">Value</th></tr></thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td class="fw-bold"><?= e($p['product_name']) ?></td>
                        <td><span class="badge bg-soft-primary"><?= e($p['category_name']) ?></span></td>
                        <td class="text-center"><?= format_currency($p['price']) ?></td>
                        <td class="text-center"><?= (int)$p['stock'] ?></td>
                        <td class="text-end fw-bold"><?= format_currency($p['price'] * $p['stock']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
