<?php
/**
 * Kiosk - Receipt page (after customer places order, payment PENDING at counter)
 * Shows only the 6-digit order number for the admin to settle payment.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$order = $_SESSION['last_order'] ?? null;
if (!$order) {
    header('Location: ' . BASE_URL . 'kiosk/index.php');
    exit;
}

$pageTitle = 'Payment Slip';
require_once __DIR__ . '/../includes/kiosk_header.php';
?>
<style media="print">
    body * { visibility: hidden !important; }
    .receipt, .receipt * { visibility: visible !important; }
    .receipt { position: absolute !important; left: 0 !important; top: 0 !important; width: 58mm !important; }
    @page { size: 58mm auto; margin: 1mm; }
</style>
<div class="checkout-page">
    <div class="text-center mb-4">
        <div class="success-check"><i class="bi bi-printer"></i></div>
        <h2 class="fw-bold">Wait for Your Receipt</h2>
        <p class="text-muted">Your receipt is being printed.</p>
    </div>

    <!-- Payment Slip / Receipt - only order number -->
    <div class="receipt">
        <div class="r-head">
            <h3 class="mb-0"><?= e(SITE_NAME) ?></h3>
            <small>Caloocan City, Philippines</small><br>
            <small><?= date('M d, Y h:i A', strtotime($order['date'])) ?></small>
        </div>

        <div class="text-center my-3">
            <div class="small text-muted">YOUR ORDER NUMBER</div>
            <div style="font-size:2.6rem;font-weight:800;letter-spacing:3px;color:#e63946;">
                <?= e($order['order_number']) ?>
            </div>
        </div>

        <div class="r-row"><span>Total</span><span><?= format_currency($order['total']) ?></span></div>
        <div class="r-row"><span>Payment</span><span>Cash (Pending)</span></div>
        <div class="r-row"><span>Status</span><span>UNPAID</span></div>

        <div class="r-dashed"></div>
        <div class="text-center small">
            <p>
                Please proceed to the <strong>counter</strong> to pay.<br>
                Present this <strong>order number</strong> to the cashier.<br>
                Your order will be processed once payment is confirmed.
            </p>
        </div>
    </div>

    </div>
</div>

<script>
window.addEventListener('load', function() {
    setTimeout(function() {
        window.print();
    }, 300);
});
window.onafterprint = function() {
    window.location.href = '<?= BASE_URL ?>kiosk/index.php';
};
setTimeout(function() {
    window.location.href = '<?= BASE_URL ?>kiosk/index.php';
}, 25000);
</script>

<?php require_once __DIR__ . '/../includes/kiosk_footer.php'; ?>
