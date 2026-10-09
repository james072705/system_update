<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_role(['admin', 'staff']);

$pageTitle = 'POS Cart';
$currentPage = 'pos';

$items = cart_items('pos');
$total = array_sum(array_column($items, 'subtotal'));

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header"><i class="bi bi-cart3 me-2"></i>Cart
        <span class="badge bg-primary ms-1"><?= cart_count('pos') ?> item(s)</span>
    </div>
    <div class="card-body">
        <?php if (empty($items)): ?>
            <div class="empty-state"><i class="bi bi-cart4"></i><p>Cart is empty.</p><a href="index.php" class="btn btn-primary">Browse Products</a></div>
        <?php else: ?>
            <div class="table-responsive mb-3">
                <table class="table">
                    <thead><tr><th>Product</th><th class="text-center">Price</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="fw-bold"><?= e($item['product_name']) ?></td>
                            <td class="text-center"><?= format_currency($item['price']) ?></td>
                            <td class="text-center">
                                <div class="qty-control">
                                    <button class="cart-update" data-id="<?= (int)$item['product_id'] ?>" data-qty="<?= (int)$item['cart_qty']-1 ?>">−</button>
                                    <span><?= (int)$item['cart_qty'] ?></span>
                                    <button class="cart-update" data-id="<?= (int)$item['product_id'] ?>" data-qty="<?= (int)$item['cart_qty']+1 ?>">+</button>
                                </div>
                            </td>
                            <td class="text-end fw-bold"><?= format_currency($item['subtotal']) ?></td>
                            <td class="text-end"><button class="btn btn-sm btn-danger cart-remove" data-id="<?= (int)$item['product_id'] ?>"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="fw-bold">Total: <?= format_currency($total) ?></h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-light" onclick="location.href='index.php'"><i class="bi bi-arrow-left"></i> Add More</button>
                    <a href="payment.php" class="btn btn-success btn-lg"><i class="bi bi-credit-card"></i> Checkout</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
