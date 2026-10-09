<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$pageTitle = 'Inventory';
$currentPage = 'inventory';

$search = trim($_GET['q'] ?? '');
$sql = "SELECT p.*, c.category_name, i.quantity AS stock, i.inventory_id
        FROM products p
        LEFT JOIN categories c ON c.category_id = p.category_id
        LEFT JOIN inventory i ON i.product_id = p.product_id
        WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND p.product_name LIKE ?";
    $params[] = '%' . $search . '%';
}
$sql .= " ORDER BY i.quantity ASC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-clipboard-data me-2"></i>Inventory</span>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="q" value="<?= e($search) ?>" class="form-control form-control-sm" placeholder="Search...">
                <button class="btn btn-sm btn-soft"><i class="bi bi-search"></i></button>
            </form>
            <a href="history.php" class="btn btn-sm btn-soft"><i class="bi bi-clock-history"></i> History</a>
            <a href="low_stock.php" class="btn btn-sm btn-warning"><i class="bi bi-exclamation-triangle"></i> Low Stock</a>
            <a href="expiry.php" class="btn btn-sm btn-info text-white"><i class="bi bi-calendar-x"></i> Expiry</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Product</th><th>Category</th><th class="text-center">Stock</th><th class="text-center">Reorder</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($products as $p):
                    $stock = (int)$p['stock'];
                    $status = $stock <= 0 ? 'out' : ($stock <= $p['reorder_level'] ? 'low' : 'ok');
                ?>
                    <tr>
                        <td class="fw-bold"><?= e($p['product_name']) ?></td>
                        <td><span class="badge bg-soft-primary"><?= e($p['category_name']) ?></span></td>
                        <td class="text-center">
                            <span class="badge <?= $status === 'out' ? 'bg-soft-danger' : ($status === 'low' ? 'bg-soft-warning' : 'bg-soft-success') ?> fs-6"><?= (int)$stock ?></span>
                        </td>
                        <td class="text-center"><?= (int)$p['reorder_level'] ?></td>
                        <td>
                            <?php if ($status === 'out'): ?>
                                <span class="badge bg-soft-danger">Out of Stock</span>
                            <?php elseif ($status === 'low'): ?>
                                <span class="badge bg-soft-warning">Low Stock</span>
                            <?php else: ?>
                                <span class="badge bg-soft-success">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-success stock-in" data-id="<?= (int)$p['product_id'] ?>" data-name="<?= e($p['product_name']) ?>"><i class="bi bi-arrow-down-circle"></i> Stock In</button>
                                <button class="btn btn-sm btn-warning stock-out" data-id="<?= (int)$p['product_id'] ?>" data-name="<?= e($p['product_name']) ?>"><i class="bi bi-arrow-up-circle"></i> Stock Out</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Stock In Modal -->
<div class="modal fade" id="stockInModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:400px;margin-left:auto;margin-right:auto;">
        <div class="modal-content">
            <form method="POST" action="stock_in.php">
                <input type="hidden" name="product_id" id="in-id">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-arrow-down-circle text-success"></i> Stock In</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="mb-3">Product: <strong id="in-name"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="text" class="form-control" name="quantity" data-type="number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <input type="text" class="form-control" name="remarks" placeholder="e.g. New delivery">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stock Out Modal -->
<div class="modal fade" id="stockOutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:400px;margin-left:auto;margin-right:auto;">
        <div class="modal-content">
            <form method="POST" action="stock_out.php">
                <input type="hidden" name="product_id" id="out-id">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-arrow-up-circle text-warning"></i> Stock Out</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="mb-3">Product: <strong id="out-name"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="text" class="form-control" name="quantity" data-type="number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <input type="text" class="form-control" name="remarks" placeholder="e.g. Damaged, spoilage">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Remove Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.stock-in').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('in-id').value = btn.dataset.id;
        document.getElementById('in-name').textContent = btn.dataset.name;
        new bootstrap.Modal(document.getElementById('stockInModal')).show();
    });
});
document.querySelectorAll('.stock-out').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('out-id').value = btn.dataset.id;
        document.getElementById('out-name').textContent = btn.dataset.name;
        new bootstrap.Modal(document.getElementById('stockOutModal')).show();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
