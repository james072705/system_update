<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_role(['admin', 'staff']);

$saleId = (int)($_GET['sale_id'] ?? 0);
$sale = db()->prepare('SELECT * FROM sales WHERE sale_id=?');
$sale->execute([$saleId]);
$sale = $sale->fetch();

if (!$sale) {
    $_SESSION['error'] = 'Sale not found.';
    header('Location: ' . BASE_URL . 'pos/transactions.php');
    exit;
}

$items = db()->prepare(
    'SELECT si.*, p.product_name FROM sales_items si JOIN products p ON p.product_id=si.product_id WHERE si.sale_id=?');
$items->execute([$saleId]);
$saleItems = $items->fetchAll();

// --- Map sale record to the shared receipt template variables ---
$receipt_order = [
    'order_number'   => $sale['invoice_number'],
    'customer_name'  => 'Walk-in',
    'order_type'     => $sale['order_type'],
    'total_amount'   => $sale['total_amount'],
    'payment_method' => $sale['payment_method'],
];
$receipt_items = $saleItems;
$receipt_cashier = $sale['cashier'] ?: ($_SESSION['fullname'] ?? 'Cashier');
$receipt_paid_at = $sale['sale_date'];
$receipt_cash_received = (float)$sale['cash'];
$receipt_change = (float)$sale['change_amount'];

$pageTitle = 'Receipt';
$currentPage = 'pos';
require_once __DIR__ . '/../includes/header.php';
?>
<style media="print">
    body * { visibility: hidden !important; }
    #receipt-print-area, #receipt-print-area * { visibility: visible !important; }
    #receipt-print-area { position: absolute !important; left: 0 !important; top: 0 !important; width: 58mm !important; }
    @page { size: 58mm auto; margin: 1mm; }
</style>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="text-center mb-3">
            <h4 class="fw-bold"><i class="bi bi-check-circle-fill text-success"></i> Payment Successful</h4>
            <p class="text-muted">Invoice #<?= e($sale['invoice_number']) ?></p>
            <div class="d-flex gap-2 justify-content-center">
                <button class="btn btn-primary" onclick="printReceipt()">
                    <i class="bi bi-printer"></i> Print Receipt
                </button>
                <a href="transactions.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/receipt_print.php'; ?>
    </div>
</div>
<script>
window.addEventListener('load', function() {
    setTimeout(function() {
        window.print();
    }, 200);
});
window.onafterprint = function() {
    window.location.href = '<?= BASE_URL ?>pos/transactions.php';
};
setTimeout(function() {
    window.location.href = '<?= BASE_URL ?>pos/transactions.php';
}, 25000);
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</content>
