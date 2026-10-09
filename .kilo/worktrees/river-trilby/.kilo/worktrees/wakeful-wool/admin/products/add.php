<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$categories = db()->query('SELECT * FROM categories ORDER BY category_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['product_name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $reorder = (int)($_POST['reorder_level'] ?? 5);
    $expiration = $_POST['expiration_date'] ?? null;
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $initial_stock = (int)($_POST['initial_stock'] ?? 0);

    $errors = [];
    if ($name === '') $errors[] = 'Product name is required.';
    if ($category_id === 0) $errors[] = 'Please select a category.';
    if ($price <= 0) $errors[] = 'Price must be greater than 0.';

    // Image upload
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp','gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid image type. Allowed: jpg, jpeg, png, webp, gif.';
        } else {
            $targetDir = __DIR__ . '/../../assets/uploads/products/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $image = 'product_' . time() . '_' . rand(100,999) . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $image)) {
                $errors[] = 'Failed to upload image.';
                $image = null;
            }
        }
    }

    if (empty($errors)) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO products (category_id, product_name, description, image, price, reorder_level, expiration_date, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$category_id, $name, $description, $image, $price, $reorder, $expiration ?: null, $status]);
            $productId = $pdo->lastInsertId();

            // Create inventory record
            $stmt = $pdo->prepare('INSERT INTO inventory (product_id, quantity) VALUES (?, ?)');
            $stmt->execute([$productId, $initial_stock]);

            if ($initial_stock > 0) {
                $stmt = $pdo->prepare('INSERT INTO stock_movements (product_id, movement_type, quantity, remarks) VALUES (?, "in", ?, "Initial stock")');
                $stmt->execute([$productId, $initial_stock]);
            }

            $pdo->commit();
            log_activity('Product', 'Added product: ' . $name);
            $_SESSION['success'] = 'Product added successfully.';
            header('Location: ' . BASE_URL . 'admin/products/list.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add Product';
$currentPage = 'products';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header"><i class="bi bi-plus-lg me-2"></i>Add New Product</div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" data-validate>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Product Name *</label>
                    <input type="text" class="form-control" name="product_name" required value="<?= e($_POST['product_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category *</label>
                    <select class="form-select" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['category_id'] ?>"><?= e($c['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Price (₱) *</label>
                    <input type="text" class="form-control" name="price" data-type="price" required value="0.00">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reorder Level</label>
                    <input type="text" class="form-control" name="reorder_level" data-type="number" value="5">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expiration Date</label>
                    <input type="date" class="form-control" name="expiration_date">
                    <div class="form-text">Leave blank if not applicable.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Initial Stock</label>
                    <input type="text" class="form-control" name="initial_stock" data-type="number" value="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2" placeholder="Short description..."></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Product Image</label>
                    <input type="file" class="form-control" name="image" accept="image/*">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Product</button>
                    <a href="list.php" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
