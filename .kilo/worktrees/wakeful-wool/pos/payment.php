<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_role(['admin', 'staff']);

$items = cart_items('pos');
if (empty($items)) {
    header('Location: ' . BASE_URL . 'pos/index.php');
    exit;
}
$total = array_sum(array_column($items, 'subtotal'));

$pageTitle = 'POS Payment';
$currentPage = 'pos';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-credit-card me-2"></i>Payment</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Order Type</label>
                    <select class="form-select" id="order-type">
                        <option value="dine_in">Dine In</option>
                        <option value="take_out">Take Out</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Customer Name</label>
                    <input type="text" class="form-control" id="customer-name" value="Guest">
                </div>
<div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <input type="text" class="form-control" id="pay-method" value="Cash" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Amount</label>
                    <div class="fs-3 fw-bold text-grad-primary"><?= format_currency($total) ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cash Received</label>
                    <input type="text" class="form-control form-control-lg" id="cash-received" data-type="number" placeholder="0.00">
                </div>
                <div class="mb-4">
                    <label class="form-label">Change</label>
                    <div class="fs-3 fw-bold" id="change-display">₱0.00</div>
                </div>
                <button class="btn btn-success btn-lg w-100" id="complete-sale">
                    <i class="bi bi-check-lg"></i> Complete Sale
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const totalAmount = <?= json_encode($total) ?>;
const cashInput = document.getElementById('cash-received');
const changeDisplay = document.getElementById('change-display');

cashInput.addEventListener('input', () => {
    const cash = parseFloat(cashInput.value) || 0;
    const change = cash - totalAmount;
    changeDisplay.textContent = change >= 0 ? formatPeso(change) : 'Insufficient';
    changeDisplay.classList.toggle('text-danger', change < 0);
});

document.getElementById('complete-sale').addEventListener('click', () => {
    const cash = parseFloat(cashInput.value) || 0;
    if (cash < totalAmount) {
        showToast('Cash received is insufficient.', 'error');
        return;
    }
    const orderType = document.getElementById('order-type').value;
    const customerName = document.getElementById('customer-name').value;
    const payMethod = document.getElementById('pay-method').value;

    fetch(`${BASE_URL}api/pos.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=complete&cash=${cash}&order_type=${orderType}&customer_name=${encodeURIComponent(customerName)}&payment_method=${payMethod}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Sale completed!', 'success');
            setTimeout(() => location.href = data.receipt_url, 800);
        } else {
            showToast(data.message || 'Error processing sale', 'error');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
