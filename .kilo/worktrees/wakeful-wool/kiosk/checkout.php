<?php
/**
 * Kiosk - Checkout page (customer name + order type + payment)
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$items = cart_items();
if (empty($items)) {
    header('Location: ' . BASE_URL . 'kiosk/index.php');
    exit;
}
$total = array_sum(array_column($items, 'subtotal'));

// Validate stock still available
$outOfStock = [];
foreach ($items as $item) {
    if ((int)$item['stock'] < (int)$item['cart_qty']) {
        $outOfStock[] = $item['product_name'];
    }
}

$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/kiosk_header.php';

// Display any error from a previous payment attempt
$flashError = flash('error');
?>
<div class="kiosk-topbar">
    <div class="k-brand">
        <div class="logo"><i class="bi bi-credit-card"></i></div>
        <div>
            <h1>Checkout</h1>
            <small>Complete your order</small>
        </div>
    </div>
    <a href="<?= BASE_URL ?>kiosk/cart.php" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Back to Cart
    </a>
</div>

<div class="checkout-page">
    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= e($flashError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($outOfStock)): ?>
        <div class="alert alert-danger">
            <strong>Some items are out of stock:</strong>
            <ul class="mb-0 mt-2"><?php foreach ($outOfStock as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
            <a href="<?= BASE_URL ?>kiosk/cart.php" class="btn btn-sm btn-warning mt-2">Adjust Cart</a>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-person me-2"></i>Order Details</div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>kiosk/payment.php" data-validate>
                        <div class="mb-3">
                            <label class="form-label">Your Name</label>
                            <input type="text" class="form-control" name="customer_name" placeholder="Enter your name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Order Type</label>
                            <div class="d-flex gap-3">
                                <label class="pay-method flex-fill">
                                    <input type="radio" name="order_type" value="dine_in" class="d-none" checked>
                                    <i class="bi bi-shop"></i>
                                    <div class="pay-label mt-2">Dine In</div>
                                </label>
                                <label class="pay-method flex-fill">
                                    <input type="radio" name="order_type" value="take_out" class="d-none">
                                    <i class="bi bi-bag"></i>
                                    <div class="pay-label mt-2">Take Out</div>
                                </label>
                            </div>
                        </div>
<input type="hidden" name="payment_method" value="Cash">
                        <div class="mb-3">
                            <label class="form-label">Notes (optional)</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Special instructions..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-cash-coin"></i> Proceed to Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-receipt me-2"></i>Order Summary</div>
                <div class="card-body">
                    <?php foreach ($items as $item): ?>
                        <div class="d-flex justify-content-between py-1">
                            <span><?= (int)$item['cart_qty'] ?> × <?= e($item['product_name']) ?></span>
                            <span class="fw-bold"><?= format_currency($item['subtotal']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <hr>
                    <div class="d-flex justify-content-between fs-4 fw-bold">
                        <span>Total</span>
                        <span id="cart-total" data-amount="<?= $total ?>"><?= format_currency($total) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Payment method selection
document.querySelectorAll('.pay-method').forEach(el => {
    el.addEventListener('click', () => {
        document.querySelectorAll('.pay-method').forEach(m => m.classList.remove('active'));
        el.classList.add('active');
        el.querySelector('input[type=radio]').checked = true;
    });
});
</script>

<?php require_once __DIR__ . '/../includes/kiosk_footer.php'; ?>
