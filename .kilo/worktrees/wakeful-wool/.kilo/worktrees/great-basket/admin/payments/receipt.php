<?php
/**
 * Admin - Paid Order Official Receipt
 * Shows & prints the Ferry's Restobar official receipt.
 * Access after marking an order as PAID.
 */
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$orderId = (int)($_GET['order_id'] ?? 0);
$cashOverride = (float)($_GET['cash'] ?? 0);
$changeOverride = (float)($_GET['change'] ?? 0);

$stmt = db()->prepare('SELECT * FROM orders WHERE order_id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    header('Location: ' . BASE_URL . 'admin/payments/settle.php');
    exit;
}

// Fetch order items with product names
$items = db()->prepare(
    'SELECT oi.*, p.product_name FROM order_items oi
     LEFT JOIN products p ON p.product_id = oi.product_id
     WHERE oi.order_id = ? ORDER BY oi.item_id ASC');
$items->execute([$orderId]);
$items = $items->fetchAll();

// Determine cashier + paid_at
$receipt_cashier = $_SESSION['fullname'] ?? 'Cashier';
$receipt_paid_at = $order['paid_at'] ?: date('Y-m-d H:i:s');

// Determine cash received & change
if ($cashOverride > 0) {
    $receipt_cash_received = $cashOverride;
    $receipt_change = $changeOverride;
} else {
    $receipt_cash_received = (float)$order['total_amount'];
    $receipt_change = 0;
}

$pageTitle = 'Order Receipt #' . $order['order_number'];
$currentPage = 'payments';
$receipt_redirect_url = BASE_URL . 'admin/payments/settle.php';
require_once __DIR__ . '/../../includes/header.php';
?>
<style>
body * { visibility: hidden !important; }
#receipt-print-area, #receipt-print-area * { visibility: visible !important; }
#receipt-print-area { position: absolute !important; left: 0 !important; top: 0 !important; width: 58mm !important; }
</style>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="text-center mb-3">
            <h4 class="fw-bold"><i class="bi bi-check-circle-fill text-success"></i> Payment Successful</h4>
            <p class="text-muted">Order #<?= e($order['order_number']) ?> has been marked as PAID and sent to the kitchen.</p>
            <div class="d-flex gap-2 justify-content-center">
                <button class="btn btn-primary" onclick="printReceipt()">
                    <i class="bi bi-printer"></i> Print Receipt
                </button>
                <a href="<?= BASE_URL ?>admin/payments/settle.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to Settlement
                </a>
            </div>
        </div>

        <?php
        $receipt_order = $order;
        $receipt_items = $items;
        require __DIR__ . '/../../includes/receipt_print.php';
        ?>
    </div>
</div>

<script>
window.addEventListener('load', function() {
    setTimeout(function() {
        window.print();
    }, 200);
});
window.onafterprint = function() {
    window.location.href = '<?= BASE_URL ?>admin/payments/settle.php';
};
setTimeout(function() {
    window.location.href = '<?= BASE_URL ?>admin/payments/settle.php';
}, 25000);
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
