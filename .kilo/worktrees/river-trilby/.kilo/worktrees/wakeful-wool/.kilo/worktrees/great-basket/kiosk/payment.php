<?php
/**
 * Kiosk - Multi-step payment flow
 * Step 1: Show "Cash Only" payment option
 * Step 2: Click Cash -> "Proceed to Counter to Pay" button
 * Step 3: Click it -> create unpaid order -> "Wait for your receipt to print"
 * The order is NOT sent to the kitchen until the admin marks it as PAID.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Auto-migrate: ensure orders table has payment_status + paid_at columns
try {
    db()->exec("ALTER TABLE orders ADD COLUMN payment_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid'");
} catch (Exception $e) { /* column already exists */ }
try {
    db()->exec("ALTER TABLE orders ADD COLUMN paid_at DATETIME NULL");
} catch (Exception $e) { /* column already exists */ }
try {
    db()->exec("ALTER TABLE stock_movements ADD COLUMN order_id INT NULL AFTER product_id");
} catch (Exception $e) { /* column already exists */ }

$items = cart_items();
if (empty($items)) {
    header('Location: ' . BASE_URL . 'kiosk/index.php');
    exit;
}
$total = array_sum(array_column($items, 'subtotal'));

/* ============================================================
   STEP 3: Customer clicked "Proceed to Counter to Pay"
   -> Create the unpaid order now
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === 'confirm') {
    $customerName = trim($_POST['customer_name'] ?? $_SESSION['checkout_draft']['customer_name'] ?? 'Guest');
    $orderType    = $_POST['order_type'] ?? $_SESSION['checkout_draft']['order_type'] ?? 'dine_in';
    $notes        = trim($_POST['notes'] ?? $_SESSION['checkout_draft']['notes'] ?? '');
    $paymentMethod = 'Cash'; // Cash only

    if (!in_array($orderType, ['dine_in','take_out'])) $orderType = 'dine_in';

    // Validate stock & expiry
    $valid = true;
    foreach ($items as $item) {
        if ((int)$item['stock'] < (int)$item['cart_qty']) {
            $valid = false;
            $_SESSION['error'] = 'Insufficient stock for ' . $item['product_name'] . '.';
            break;
        }
        if ($item['expiration_date'] && $item['expiration_date'] < date('Y-m-d')) {
            $valid = false;
            $_SESSION['error'] = $item['product_name'] . ' is expired and cannot be sold.';
            break;
        }
    }

    if ($valid) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            // Create order with 6-digit number, status pending, payment UNPAID
            $orderNo = generate_short_order_number();
            $stmt = $pdo->prepare(
                'INSERT INTO orders (order_number, customer_name, status, total_amount, order_type, payment_method, payment_status, notes)
                 VALUES (?, ?, "pending", ?, ?, ?, "unpaid", ?)');
            $stmt->execute([$orderNo, $customerName, $total, $orderType, $paymentMethod, $notes]);
            $orderId = $pdo->lastInsertId();

            // Insert order items + deduct stock immediately
            foreach ($items as $item) {
                $pid = (int)$item['product_id'];
                $qty = (int)$item['cart_qty'];
                $price = (float)$item['price'];
                $subtotal = $price * $qty;

                $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$orderId, $pid, $qty, $price, $subtotal]);

                $stmt = $pdo->prepare('UPDATE inventory SET quantity = quantity - ? WHERE product_id = ?');
                $stmt->execute([$qty, $pid]);

                $stmt = $pdo->prepare('INSERT INTO stock_movements (product_id, order_id, movement_type, quantity, remarks) VALUES (?, ?, "reserved", ?, ?)');
                $stmt->execute([$pid, $orderId, $qty, 'Kiosk order #' . $orderNo]);
            }

            $pdo->commit();

            // Store order info for receipt (only 6-digit number shown)
            $_SESSION['last_order'] = [
                'order_number' => $orderNo,
                'customer_name' => $customerName,
                'total' => $total,
                'order_type' => $orderType,
                'payment_method' => $paymentMethod,
                'payment_status' => 'unpaid',
                'date' => date('Y-m-d H:i:s'),
                'items' => $items
            ];

            // Clear draft + cart
            unset($_SESSION['checkout_draft']);
            cart_clear();
            header('Location: ' . BASE_URL . 'kiosk/receipt.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Transaction failed: ' . $e->getMessage();
            header('Location: ' . BASE_URL . 'kiosk/payment.php');
            exit;
        }
    }
    header('Location: ' . BASE_URL . 'kiosk/checkout.php');
    exit;
}

/* ============================================================
   STEP 1 & 2: Show payment method (Cash Only) + "Proceed to Counter"
   The order is NOT created yet - only draft details are kept.
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save checkout draft from the checkout form
    $_SESSION['checkout_draft'] = [
        'customer_name' => trim($_POST['customer_name'] ?? 'Guest'),
        'order_type'    => $_POST['order_type'] ?? 'dine_in',
        'notes'         => trim($_POST['notes'] ?? ''),
    ];
}

$draft = $_SESSION['checkout_draft'] ?? null;
if (!$draft) {
    header('Location: ' . BASE_URL . 'kiosk/checkout.php');
    exit;
}

$pageTitle = 'Payment';
require_once __DIR__ . '/../includes/kiosk_header.php';
$flashError = flash('error');
?>
<div class="kiosk-topbar">
    <div class="k-brand">
        <div class="logo"><i class="bi bi-cash-coin"></i></div>
        <div>
            <h1>Payment</h1>
            <small>Choose your payment method</small>
        </div>
    </div>
    <a href="<?= BASE_URL ?>kiosk/checkout.php" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="checkout-page">
    <div class="text-center mb-4">
        <h2 class="fw-bold">Total: <?= format_currency($total) ?></h2>
        <p class="text-muted">Your order is ready. Please select how you will pay.</p>
    </div>

    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= e($flashError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <!-- Step 1: Cash Only option -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-credit-card me-2"></i>Payment Method</div>
                <div class="card-body">
                    <label class="pay-method w-100 active" id="cash-option" style="cursor:pointer;">
                        <input type="radio" name="payment_option" id="cash-radio" class="d-none" value="Cash" checked>
                        <i class="bi bi-cash-stack"></i>
                        <div class="pay-label mt-2">Cash Only</div>
                        <div class="text-muted small">Pay at the counter</div>
                    </label>
                </div>
            </div>

            <!-- Step 2: Proceed to Counter to Pay -->
            <button class="btn btn-success btn-lg w-100 d-none" id="proceed-counter-btn">
                <i class="bi bi-cash-coin"></i> Proceed to Counter to Pay
            </button>

            <!-- Step 3: Wait for receipt -->
            <div class="text-center mt-4 d-none" id="wait-receipt-box">
                <div class="success-check"><i class="bi bi-printer"></i></div>
                <h3 class="fw-bold">Wait for Your Receipt to Print</h3>
                <p class="text-muted">Please wait while we generate your receipt.</p>
                <div class="spinner-border text-primary" role="status"></div>
            </div>

            <!-- Hidden order confirmation form -->
            <form method="POST" action="<?= BASE_URL ?>kiosk/payment.php" id="order-form" class="d-none">
                <input type="hidden" name="step" value="confirm">
                <input type="hidden" name="customer_name" value="<?= e($draft['customer_name']) ?>">
                <input type="hidden" name="order_type" value="<?= e($draft['order_type']) ?>">
                <input type="hidden" name="notes" value="<?= e($draft['notes']) ?>">
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cashOption = document.getElementById('cash-option');
    const proceedBtn = document.getElementById('proceed-counter-btn');
    const waitBox = document.getElementById('wait-receipt-box');
    const orderForm = document.getElementById('order-form');

    if (cashOption) {
        // Show "Proceed to Counter" when Cash is selected
        cashOption.addEventListener('click', () => {
            document.querySelectorAll('.pay-method').forEach(m => m.classList.remove('active'));
            cashOption.classList.add('active');
            document.getElementById('cash-radio').checked = true;
            proceedBtn.classList.remove('d-none');
        });
    }

    if (proceedBtn) {
        proceedBtn.addEventListener('click', () => {
            proceedBtn.classList.add('d-none');
            waitBox.classList.remove('d-none');
            // Short delay to show "waiting" then submit the order
            setTimeout(() => {
                orderForm.submit();
            }, 1500);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/kiosk_footer.php'; ?>
