<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$pageTitle = 'Categories';
$currentPage = 'categories';

$delete_id = (int)($_GET['delete'] ?? 0);
if ($delete_id) {
    $cat = db()->prepare('SELECT * FROM categories WHERE category_id=?');
    $cat->execute([$delete_id]);
    if ($cat->fetch()) {
        // Check if category still has products
        $cnt = db()->prepare('SELECT COUNT(*) AS c FROM products WHERE category_id=?');
        $cnt->execute([$delete_id]);
        $productCount = (int)$cnt->fetch()['c'];

        if ($productCount > 0) {
            $_SESSION['error'] = 'Cannot delete this category because it still has ' . $productCount . ' product(s). Move or delete those products first.';
        } else {
            db()->prepare('DELETE FROM categories WHERE category_id=?')->execute([$delete_id]);
            log_activity('Category', 'Deleted category ID ' . $delete_id);
            $_SESSION['success'] = 'Category deleted.';
        }
    }
    header('Location: ' . BASE_URL . 'admin/categories/list.php');
    exit;
}

$categories = db()->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.category_id) AS product_count FROM categories c ORDER BY category_id')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-tags me-2"></i>Product Categories</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Add Category</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Icon</th><th>Name</th><th class="text-center">Products</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= (int)$cat['category_id'] ?></td>
                        <td><i class="bi <?= e($cat['icon']) ?> fs-5 text-primary"></i></td>
                        <td class="fw-bold"><?= e($cat['category_name']) ?></td>
                        <td class="text-center"><span class="badge bg-soft-info"><?= (int)$cat['product_count'] ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-soft edit-cat" data-id="<?= (int)$cat['category_id'] ?>" data-name="<?= e($cat['category_name']) ?>" data-icon="<?= e($cat['icon']) ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="?delete=<?= (int)$cat['category_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:420px;margin-left:auto;margin-right:auto;">
        <div class="modal-content">
            <form method="POST" action="add.php">
                <div class="modal-header"><h5 class="modal-title">Add Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="text" class="form-control" name="icon" value="bi-tag" placeholder="bi-cup">
                        <div class="form-text">Bootstrap icon class, e.g. bi-cup, bi-tag, bi-grid</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:420px;margin-left:auto;margin-right:auto;">
        <div class="modal-content">
            <form method="POST" action="edit.php">
                <input type="hidden" name="category_id" id="edit-id">
                <div class="modal-header"><h5 class="modal-title">Edit Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="category_name" id="edit-name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="text" class="form-control" name="icon" id="edit-icon">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-cat').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit-id').value = btn.dataset.id;
        document.getElementById('edit-name').value = btn.dataset.name;
        document.getElementById('edit-icon').value = btn.dataset.icon;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
