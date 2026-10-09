<?php
/**
 * Kiosk category listing - all categories
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$pageTitle = 'Categories';
$categories = db()->query(
    'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.category_id AND p.status="active") AS count
     FROM categories c ORDER BY category_id')->fetchAll();

require_once __DIR__ . '/../includes/kiosk_header.php';
?>
<div class="kiosk-topbar">
    <div class="k-brand">
        <div class="logo"><i class="bi bi-grid"></i></div>
        <div>
            <h1>Categories</h1>
            <small>Browse our menu</small>
        </div>
    </div>
    <a href="<?= BASE_URL ?>kiosk/index.php" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Back to Menu
    </a>
</div>

<div class="container py-4">
    <div class="row g-3">
        <?php foreach ($categories as $cat): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="<?= BASE_URL ?>kiosk/index.php?category=<?= (int)$cat['category_id'] ?>" class="text-decoration-none">
                <div class="card p-4 text-center">
                    <i class="bi <?= e($cat['icon']) ?> fs-1 text-primary"></i>
                    <h5 class="mt-3 mb-1"><?= e($cat['category_name']) ?></h5>
                    <span class="badge bg-soft-primary mx-auto"><?= (int)$cat['count'] ?> items</span>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/kiosk_footer.php'; ?>
