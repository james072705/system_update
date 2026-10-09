<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_role(['admin', 'staff']);

$pageTitle = 'New Sale';
$currentPage = 'pos';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header"><i class="bi bi-cart-plus me-2"></i>New Sale</div>
    <div class="card-body">
        <p>Use the <a href="index.php">Point of Sale</a> to add products to the cart and complete the sale.</p>
        <a href="index.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Go to POS</a>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
