<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$id = (int)($_GET['id'] ?? $_POST['product_id'] ?? 0);
$product = db()->prepare('SELECT * FROM products WHERE product_id = ?');
$product->execute([$id]);
$product = $product->fetch();

if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    header('Location: ' . BASE_URL . 'admin/products/list.php');
    exit;
}

$categories = db()->query('SELECT * FROM categories ORDER BY category_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['product_name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $reorder = (int)($_POST['reorder_level'] ?? 5);
    $expiration = $_POST['expiration_date'] ?? null;
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';

    $errors = [];
    if ($name === '') $errors[] = 'Product name is required.';
    if ($price <= 0) $errors[] = 'Price must be greater than 0.';

    $image = $product['image'];
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp','gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid image type.';
        } else {
            $targetDir = __DIR__ . '/../../assets/uploads/products/';
            $image = 'product_' . time() . '_' . rand(100,999) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $image);
        }
    }

    if (empty($errors)) {
        $stmt = db()->prepare(
            'UPDATE products SET category_id=?, product_name=?, description=?, image=?, price=?, reorder_level=?, expiration_date=?, status=? WHERE product_id=?');
        $stmt->execute([$category_id, $name, $description, $image, $price, $reorder, $expiration ?: null, $status, $id]);
        log_activity('Product', 'Updated product: ' . $name);
        $_SESSION['success'] = 'Product updated successfully.';
        header('Location: ' . BASE_URL . 'admin/products/list.php');
        exit;
    }
}

$pageTitle = 'Edit Product';
$currentPage = 'products';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header"><i class="bi bi-pencil me-2"></i>Edit Product</div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" data-validate>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Product Name *</label>
                    <input type="text" class="form-control" name="product_name" value="<?= e($product['product_name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category *</label>
                    <select class="form-select" name="category_id" required>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['category_id'] ?>" <?= $product['category_id'] == $c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Price (₱) *</label>
                    <input type="text" class="form-control" name="price" data-type="price" value="<?= e($product['price']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reorder Level</label>
                    <input type="text" class="form-control" name="reorder_level" data-type="number" value="<?= (int)$product['reorder_level'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expiration Date</label>
                    <input type="date" class="form-control" name="expiration_date" value="<?= e($product['expiration_date']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2"><?= e($product['description']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Product Image</label>
                    <input type="file" class="form-control" name="image" accept="image/*">
                    <?php if ($product['image']): ?>
                        <div class="mt-2"><img src="<?= BASE_URL ?>assets/uploads/products/<?= e($product['image']) ?>" width="80" height="80" class="rounded-3" style="object-fit:cover"></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Product</button>
                    <a href="list.php" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
