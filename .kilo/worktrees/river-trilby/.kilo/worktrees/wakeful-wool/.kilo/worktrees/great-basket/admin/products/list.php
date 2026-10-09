<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$pageTitle = 'Products';
$currentPage = 'products';

$search = trim($_GET['q'] ?? '');
$cat = (int)($_GET['category'] ?? 0);

$sql = "SELECT p.*, c.category_name, i.quantity AS stock
        FROM products p
        LEFT JOIN categories c ON c.category_id = p.category_id
        LEFT JOIN inventory i ON i.product_id = p.product_id
        WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND p.product_name LIKE ?";
    $params[] = '%' . $search . '%';
}
if ($cat > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $cat;
}
$sql .= " ORDER BY p.product_id DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = db()->query('SELECT * FROM categories ORDER BY category_name')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-box-seam me-2"></i>Products</span>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="q" value="<?= e($search) ?>" class="form-control form-control-sm" placeholder="Search products...">
                <select name="category" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['category_id'] ?>" <?= $cat === (int)$c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-soft"><i class="bi bi-search"></i></button>
            </form>
            <a href="add.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Product</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($products)): ?>
            <div class="empty-state"><i class="bi bi-inbox"></i><p>No products found.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Product</th><th>Category</th><th class="text-center">Price</th><th class="text-center">Stock</th><th class="text-center">Expiration</th><th class="text-center">Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $p):
                        $stock = (int)$p['stock'];
                        $low = $stock <= $p['reorder_level'];
                        $expired = $p['expiration_date'] && $p['expiration_date'] < date('Y-m-d');
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($p['image'] && file_exists(__DIR__ . '/../../assets/uploads/products/' . $p['image'])): ?>
                                        <img src="<?= BASE_URL ?>assets/uploads/products/<?= e($p['image']) ?>" width="40" height="40" class="rounded-3" style="object-fit:cover">
                                    <?php else: ?>
                                        <div class="bg-soft rounded-3 d-grid place-items-center" style="width:40px;height:40px"><i class="bi bi-cup-straw text-muted"></i></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?= e($p['product_name']) ?></div>
                                        <small class="text-muted"><?= e($p['description'] ?? '') ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-soft-primary"><?= e($p['category_name']) ?></span></td>
                            <td class="text-center fw-bold"><?= format_currency($p['price']) ?></td>
                            <td class="text-center">
                                <?php if ($stock <= 0): ?>
                                    <span class="badge bg-soft-danger">Out of Stock</span>
                                <?php elseif ($low): ?>
                                    <span class="badge bg-soft-warning"><?= (int)$stock ?></span>
                                <?php else: ?>
                                    <span class="badge bg-soft-success"><?= (int)$stock ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($p['expiration_date']):
                                    $daysLeft = days_until_expiry($p['expiration_date']);
                                    $label = expiry_label($p['expiration_date']);
                                ?>
                                    <?php if ($daysLeft < 0): ?>
                                        <span class="badge bg-soft-danger d-inline-block mb-1"><?= format_date($p['expiration_date']) ?></span>
                                        <div><small class="text-danger fw-bold"><?= e($label) ?></small></div>
                                    <?php elseif ($daysLeft <= 3): ?>
                                        <span class="badge bg-soft-danger d-inline-block mb-1"><?= format_date($p['expiration_date']) ?></span>
                                        <div><small class="text-danger fw-bold"><i class="bi bi-exclamation-triangle"></i> <?= e($label) ?></small></div>
                                    <?php elseif ($daysLeft <= 7): ?>
                                        <span class="badge bg-soft-warning d-inline-block mb-1"><?= format_date($p['expiration_date']) ?></span>
                                        <div><small class="text-warning fw-bold"><i class="bi bi-alarm"></i> <?= e($label) ?></small></div>
                                    <?php else: ?>
                                        <span class="badge bg-soft-info d-inline-block mb-1"><?= format_date($p['expiration_date']) ?></span>
                                        <div><small class="text-muted"><?= e($label) ?></small></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $isInactive = $p['status'] === 'inactive';
                                    $nearExpiry = $p['expiration_date']
                                        && ($dl = days_until_expiry($p['expiration_date'])) !== null
                                        && $dl <= 5;
                                ?>
                                <span class="badge <?= $p['status'] === 'active' ? 'bg-soft-success' : 'bg-soft-secondary' ?>"><?= ucfirst($p['status']) ?></span>
                                <?php if ($isInactive && $nearExpiry): ?>
                                    <div><small class="text-danger fw-bold mt-1 d-inline-block"><i class="bi bi-exclamation-octagon"></i> Auto-deactivated (expiring soon)</small></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-soft view-product" data-id="<?= (int)$p['product_id'] ?>" title="View"><i class="bi bi-eye"></i></button>
                                    <button class="btn btn-sm btn-soft edit-product" data-id="<?= (int)$p['product_id'] ?>" data-name="<?= e($p['product_name']) ?>" data-price="<?= (float)$p['price'] ?>" data-status="<?= e($p['status']) ?>" title="Edit"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-outline-danger delete-product" data-id="<?= (int)$p['product_id'] ?>" data-name="<?= e($p['product_name']) ?>" title="Delete"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Product Modal -->
<div class="modal fade" id="viewProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:420px;margin-left:auto;margin-right:auto;">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-eye me-2"></i>Product Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="view-product-body">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:520px;margin-left:auto;margin-right:auto;">
        <div class="modal-content">
            <form method="POST" action="edit.php" id="edit-product-form" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="edit-product-id">
                <input type="hidden" name="category_id" id="edit-product-category">
                <input type="hidden" name="reorder_level" id="edit-product-reorder">
                <input type="hidden" name="expiration_date" id="edit-product-expiration">
                <input type="hidden" name="description" id="edit-product-description">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="product_name" id="edit-product-name" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" id="edit-product-category-select" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['category_id'] ?>"><?= e($c['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Price (₱)</label>
                            <input type="text" class="form-control" name="price" id="edit-product-price" data-type="price" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Reorder Level</label>
                            <input type="text" class="form-control" name="reorder_level" id="edit-product-reorder-input" data-type="number">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Expiration Date</label>
                            <input type="date" class="form-control" name="expiration_date" id="edit-product-expiration-input">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit-product-status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Product Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*" id="edit-product-image-input">
                            <div class="form-text">Leave blank to keep current image.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit-product-description-input" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Product Modal -->
<div class="modal fade" id="deleteProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:380px;margin-left:auto;margin-right:auto;">
        <div class="modal-content">
            <form method="POST" action="delete.php" id="delete-product-form">
                <input type="hidden" name="id" id="delete-product-id">
                <div class="modal-header"><h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Delete Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="delete-product-name"></strong>?</p>
                    <div class="alert alert-warning small mb-0"><i class="bi bi-exclamation-triangle me-1"></i> This action cannot be undone.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg"></i> Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.view-product').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const body = document.getElementById('view-product-body');
        body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
        new bootstrap.Modal(document.getElementById('viewProductModal')).show();
        fetch(`${BASE_URL}api/products.php?action=view&product_id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) { body.innerHTML = '<div class="alert alert-warning">' + (data.message || 'Product not found') + '</div>'; return; }
                let html = '<div class="row g-2 mb-2">';
                html += '<div class="col-5"><strong>Product:</strong></div><div class="col-7 text-end">' + data.product.product_name + '</div>';
                html += '<div class="col-5"><strong>Category:</strong></div><div class="col-7 text-end">' + (data.product.category_name || '-') + '</div>';
                html += '<div class="col-5"><strong>Price:</strong></div><div class="col-7 text-end fw-bold">' + formatPeso(data.product.price) + '</div>';
                html += '<div class="col-5"><strong>Stock:</strong></div><div class="col-7 text-end">' + (data.product.stock ?? 0) + '</div>';
                html += '<div class="col-5"><strong>Status:</strong></div><div class="col-7 text-end">' + (data.product.status || '-') + '</div>';
                html += '<div class="col-5"><strong>Reorder Level:</strong></div><div class="col-7 text-end">' + (data.product.reorder_level || 0) + '</div>';
                html += '<div class="col-5"><strong>Expiration:</strong></div><div class="col-7 text-end">' + (data.product.expiration_date ? formatDate(data.product.expiration_date) : '—') + '</div>';
                html += '</div>';
                if (data.product.description) { html += '<p class="small text-muted mb-0">' + data.product.description + '</p>'; }
                body.innerHTML = html;
            })
            .catch(() => { body.innerHTML = '<div class="alert alert-danger">Failed to load product.</div>'; });
    });
});

document.querySelectorAll('.edit-product').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        fetch(`${BASE_URL}api/products.php?action=view&product_id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) { alert(data.message || 'Product not found'); return; }
                const p = data.product;
                document.getElementById('edit-product-id').value = p.product_id;
                document.getElementById('edit-product-name').value = p.product_name;
                document.getElementById('edit-product-price').value = p.price;
                document.getElementById('edit-product-status').value = p.status;
                document.getElementById('edit-product-category-select').value = p.category_id || '';
                document.getElementById('edit-product-reorder-input').value = p.reorder_level || 5;
                document.getElementById('edit-product-expiration-input').value = p.expiration_date || '';
                document.getElementById('edit-product-description-input').value = p.description || '';
                new bootstrap.Modal(document.getElementById('editProductModal')).show();
            })
            .catch(() => alert('Failed to load product data.'));
    });
});

document.querySelectorAll('.delete-product').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('delete-product-id').value = btn.dataset.id;
        document.getElementById('delete-product-name').textContent = btn.dataset.name;
        new bootstrap.Modal(document.getElementById('deleteProductModal')).show();
    });
});

function formatPeso(value) { return '₱ ' + parseFloat(value).toFixed(2); }
function formatDate(dateStr) { const d = new Date(dateStr); return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }); }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
