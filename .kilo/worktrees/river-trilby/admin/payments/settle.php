<?php
/**
 * Admin - Payment Settlement
 * - Search kiosk orders by order number
 * - View order details before settling
 * - Mark as Paid (records sale + updates inventory + sends to kitchen)
 * - Once paid, admin can print the official Ferry's Restobar receipt
 */
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$pdo = db();

// Auto-migrate: ensure orders table has payment_status + paid_at columns
try {
    db()->exec("ALTER TABLE orders ADD COLUMN payment_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid'");
} catch (Exception $e) { /* column already exists */ }
try {
    db()->exec("ALTER TABLE orders ADD COLUMN paid_at DATETIME NULL");
} catch (Exception $e) { /* column already exists */ }

// Handle mark-as-paid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $orderId      = (int)$_POST['order_id'];
    $cashReceived = (float)($_POST['cash_received'] ?? 0);

    $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if ($order) {
        $total = (float)$order['total_amount'];

        if ($cashReceived < $total) {
            $_SESSION['error'] = 'Insufficient payment for Order #' . $order['order_number'] . '. Required: ' . format_currency($total) . ', Received: ' . format_currency($cashReceived) . '.';
            header('Location: ' . BASE_URL . 'admin/payments/settle.php');
            exit;
        }

        $change = max(0, $cashReceived - $total);

        $pdo->beginTransaction();
        try {
            // 1) Update order: paid + sent to kitchen as PENDING
            $stmt = $pdo->prepare(
                "UPDATE orders SET payment_status = 'paid', paid_at = NOW(), status = 'pending' WHERE order_id = ?");
            $stmt->execute([$orderId]);

            // 2) Auto-record the SALE (transaction) right away so it appears in today's sales
            $invoiceNo = 'INV-' . $order['order_number'];
            $exists = $pdo->prepare('SELECT COUNT(*) AS c FROM sales WHERE invoice_number = ?');
            $exists->execute([$invoiceNo]);

            if ((int)$exists->fetch()['c'] === 0) {
                $total = (float)$order['total_amount'];
                $cashier = $_SESSION['fullname'] ?? 'Admin';
                $stmt = $pdo->prepare(
                    'INSERT INTO sales (invoice_number, total_amount, cash, change_amount, cashier, payment_method, order_type, sale_date)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$invoiceNo, $total, $cashReceived, $change, $cashier, $order['payment_method'], $order['order_type']]);
                $saleId = $pdo->lastInsertId();

                // Check if stock was already reserved at kiosk
                $reserved = $pdo->prepare('SELECT COUNT(*) AS c FROM stock_movements WHERE order_id = ? AND movement_type = "reserved"');
                $reserved->execute([$orderId]);
                $hasReserved = (int)$reserved->fetch()['c'] > 0;

                // Insert sale items + handle inventory
                $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
                $items->execute([$orderId]);
                foreach ($items->fetchAll() as $oi) {
                    $pid = (int)$oi['product_id'];
                    $qty = (int)$oi['quantity'];
                    $price = (float)$oi['price'];
                    $subtotal = (float)$oi['subtotal'];

                    $stmt = $pdo->prepare('INSERT INTO sales_items (sale_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$saleId, $pid, $qty, $price, $subtotal]);

                    if ($hasReserved) {
                        // Stock already deducted at kiosk; just mark the movement as sale
                        $stmt = $pdo->prepare('UPDATE stock_movements SET movement_type = "sale", remarks = ? WHERE order_id = ? AND product_id = ? AND movement_type = "reserved"');
                        $stmt->execute([$invoiceNo, $orderId, $pid]);
                    } else {
                        // Deduct inventory now
                        $stmt = $pdo->prepare('UPDATE inventory SET quantity = quantity - ? WHERE product_id = ?');
                        $stmt->execute([$qty, $pid]);

                        $stmt = $pdo->prepare('INSERT INTO stock_movements (product_id, movement_type, quantity, remarks) VALUES (?, "sale", ?, ?)');
                        $stmt->execute([$pid, $qty, $invoiceNo]);
                    }
                }
            } else {
                // Sale already recorded; get its ID for possible reprint
                $s = $pdo->prepare('SELECT sale_id, cash, change_amount FROM sales WHERE invoice_number = ?');
                $s->execute([$invoiceNo]);
                $saleRow = $s->fetch();
                $saleId = $saleRow ? (int)$saleRow['sale_id'] : null;
                if ($saleRow && $cashReceived != (float)$saleRow['cash']) {
                    $upd = $pdo->prepare('UPDATE sales SET cash = ?, change_amount = ? WHERE sale_id = ?');
                    $upd->execute([$cashReceived, $change, $saleId]);
                }
            }

            // 3) Notification
            try {
                $user_id = $_SESSION['user_id'] ?? null;
                $stmt = $pdo->prepare('INSERT INTO notifications (type, message, user_id) VALUES ("info", ?, ?)');
                $stmt->execute(['Payment settled for order #' . $order['order_number'] . ' — now sent to the kitchen. Sale recorded as ' . $invoiceNo, $user_id]);
            } catch (Exception $e) { /* ignore */ }

            log_activity('Payment Settled', 'Marked order #' . $order['order_number'] . ' as paid, sale recorded');

            $pdo->commit();
            $_SESSION['success'] = 'Order #' . $order['order_number'] . ' marked as PAID. Sale recorded & order sent to the kitchen.';

            // Print the receipt immediately after settling
            header('Location: ' . BASE_URL . 'admin/payments/receipt.php?order_id=' . $orderId . '&cash=' . $cashReceived . '&change=' . $change);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Failed to settle order #' . $order['order_number'] . ': ' . $e->getMessage();
        }
    }

    header('Location: ' . BASE_URL . 'admin/payments/settle.php');
    exit;
}

// Handle cancel order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $orderId = (int)$_POST['order_id'];

    $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if ($order && $order['status'] !== 'cancelled') {
        $pdo->beginTransaction();
        try {
            // If this order was ALREADY PAID, a sale was recorded -> reverse it
            if ($order['payment_status'] === 'paid') {
                // Find the associated sale
                $invoiceNo = 'INV-' . $order['order_number'];
                $s = $pdo->prepare('SELECT * FROM sales WHERE invoice_number = ?');
                $s->execute([$invoiceNo]);
                $sale = $s->fetch();

                // Restore inventory for each item (quantity added back)
                $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
                $items->execute([$orderId]);
                foreach ($items->fetchAll() as $oi) {
                    $pid  = (int)$oi['product_id'];
                    $qty  = (int)$oi['quantity'];

                    // Add the quantity back to inventory
                    $stmt = $pdo->prepare('UPDATE inventory SET quantity = quantity + ? WHERE product_id = ?');
                    $stmt->execute([$qty, $pid]);

                    // Record the reversal in stock movements
                    $stmt = $pdo->prepare(
                        'INSERT INTO stock_movements (product_id, movement_type, quantity, remarks) VALUES (?, "adjustment", ?, ?)');
                    $stmt->execute([$pid, $qty, 'Restock - cancelled order #' . $order['order_number']]);
                }

                // Delete the sale (sales_items cascade-delete via FK)
                if ($sale) {
                    $stmt = $pdo->prepare('DELETE FROM sales WHERE sale_id = ?');
                    $stmt->execute([$sale['sale_id']]);
                }
            } else {
                // Unpaid order - restore reserved stock if it was deducted at kiosk
                $reservedMovements = $pdo->prepare('SELECT sm.*, oi.quantity FROM stock_movements sm JOIN order_items oi ON oi.product_id = sm.product_id AND oi.order_id = sm.order_id WHERE sm.order_id = ? AND sm.movement_type = "reserved"');
                $reservedMovements->execute([$orderId]);
                foreach ($reservedMovements->fetchAll() as $rm) {
                    $pid = (int)$rm['product_id'];
                    $qty = (int)$rm['quantity'];

                    // Add the reserved quantity back to inventory
                    $stmt = $pdo->prepare('UPDATE inventory SET quantity = quantity + ? WHERE product_id = ?');
                    $stmt->execute([$qty, $pid]);

                    // Record the restock
                    $stmt = $pdo->prepare(
                        'INSERT INTO stock_movements (product_id, movement_type, quantity, remarks) VALUES (?, "adjustment", ?, ?)');
                    $stmt->execute([$pid, $qty, 'Restock - cancelled kiosk order #' . $order['order_number']]);
                }

                // Delete the reserved movements
                $stmt = $pdo->prepare('DELETE FROM stock_movements WHERE order_id = ? AND movement_type = "reserved"');
                $stmt->execute([$orderId]);
            }

            // Mark the order as cancelled
            $stmt = $pdo->prepare(
                "UPDATE orders SET status = 'cancelled', payment_status = 'unpaid' WHERE order_id = ?");
            $stmt->execute([$orderId]);

            // Notification + audit log
            try {
                $user_id = $_SESSION['user_id'] ?? null;
                $stmt = $pdo->prepare('INSERT INTO notifications (type, message, user_id) VALUES ("warning", ?, ?)');
                $stmt->execute(['Order #' . $order['order_number'] . ' has been CANCELLED. Inventory restored.', $user_id]);
            } catch (Exception $e) { /* ignore */ }

            log_activity('Cancel Order', 'Cancelled order #' . $order['order_number'] . ($order['payment_status'] === 'paid' ? ' - sale reversed & inventory restored' : ''));

            $pdo->commit();
            $_SESSION['warning'] = 'Order #' . $order['order_number'] . ' has been cancelled. Sale & inventory were updated.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Failed to cancel order #' . $order['order_number'] . ': ' . $e->getMessage();
        }
    }

    header('Location: ' . BASE_URL . 'admin/payments/settle.php');
    exit;
}

// Counts for tabs
$counts = [
    'unpaid' => (int)$pdo->query("SELECT COUNT(*) c FROM orders WHERE payment_status = 'unpaid' AND status NOT IN ('cancelled')")->fetch()['c'],
    'paid'   => (int)$pdo->query("SELECT COUNT(*) c FROM orders WHERE payment_status = 'paid'")->fetch()['c'],
];

$tab = $_GET['tab'] ?? 'unpaid';
$tab = in_array($tab, ['unpaid','paid']) ? $tab : 'unpaid';

// Search
$search = trim($_GET['q'] ?? '');
$showingSearch = $search !== '';

if ($tab === 'unpaid') {
    $sql = "SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS item_count
            FROM orders o
            WHERE o.payment_status = 'unpaid' AND o.status != 'cancelled'";
    if ($showingSearch) {
        $sql .= " AND o.order_number LIKE " . $pdo->quote('%' . $search . '%');
    }
    $sql .= " ORDER BY o.order_date ASC";
    $rows = $pdo->query($sql)->fetchAll();
} else {
    $sql = "SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS item_count
            FROM orders o
            WHERE o.payment_status = 'paid'";
    if ($showingSearch) {
        $sql .= " AND o.order_number LIKE " . $pdo->quote('%' . $search . '%');
    }
    $sql .= " ORDER BY o.paid_at DESC LIMIT 50";
    $rows = $pdo->query($sql)->fetchAll();
}

$pageTitle = 'Payment Settlement';
$currentPage = 'payments';
require_once __DIR__ . '/../../includes/header.php';

function payment_badge($status) {
    return $status === 'paid'
        ? '<span class="badge badge-soft-success">PAID</span>'
        : '<span class="badge badge-soft-danger">UNPAID</span>';
}
?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-grad-danger"><i class="bi bi-clock-history text-white"></i></div>
            <div class="stat-value"><?= $counts['unpaid'] ?></div>
            <div class="stat-label">Unpaid Orders</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-grad-success"><i class="bi bi-check2-circle text-white"></i></div>
            <div class="stat-value"><?= $counts['paid'] ?></div>
            <div class="stat-label">Paid Orders</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="stat-icon bg-grad-info"><i class="bi bi-search text-white"></i></div>
                <div class="stat-label mb-0">Search Order Number</div>
            </div>
            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Enter order number e.g. 000123" autofocus>
                <button class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
                <?php if ($showingSearch): ?>
                    <a href="?tab=<?= e($tab) ?>" class="btn btn-light"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-cash-coin me-2"></i>Kiosk Payment Settlement
            <?php if ($showingSearch): ?> <span class="badge badge-soft-info ms-2">Searching: <?= e($search) ?></span><?php endif; ?>
        </span>
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'unpaid' ? 'active' : '' ?>" href="?tab=unpaid">
                    <i class="bi bi-clock-history"></i> Unpaid (<?= $counts['unpaid'] ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'paid' ? 'active' : '' ?>" href="?tab=paid">
                    <i class="bi bi-check2-circle"></i> Paid (<?= $counts['paid'] ?>)
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <?php if (empty($rows)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5 class="mt-3"><?= $showingSearch ? 'No orders match "' . e($search) . '"' : ('No ' . e($tab) . ' orders') ?></h5>
                <p><?= $showingSearch ? 'Try a different order number.' : ($tab === 'unpaid' ? 'All kiosk orders have been settled.' : 'No paid orders yet.') ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Ordered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $o): ?>
                            <tr>
                                <td class="fw-bold" style="font-size:1.1rem;letter-spacing:1px;"><?= e($o['order_number']) ?></td>
                                <td><?= e($o['customer_name']) ?></td>
                                <td><?= (int)$o['item_count'] ?></td>
                                <td class="fw-bold"><?= format_currency($o['total_amount']) ?></td>
                                <td><span class="badge badge-soft-info"><?= ucfirst(str_replace('_',' ',$o['order_type'])) ?></span></td>
                                <td><?= payment_badge($o['payment_status']) ?></td>
                                <td><?= format_datetime($o['order_date']) ?></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1 flex-wrap">
                                        <!-- View order details -->
                                        <button class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#viewOrderModal"
                                            data-id="<?= (int)$o['order_id'] ?>"
                                            data-number="<?= e($o['order_number']) ?>"
                                            data-customer="<?= e($o['customer_name']) ?>"
                                            data-total="<?= (float)$o['total_amount'] ?>"
                                            data-type="<?= ucfirst(str_replace('_',' ',$o['order_type'])) ?>"
                                            data-status="<?= e(strtoupper($o['payment_status'])) ?>"
                                            data-date="<?= e($o['order_date']) ?>"
                                            data-paid="<?= $o['paid_at'] ? e($o['paid_at']) : '' ?>"
                                            title="View Order">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <?php if ($o['payment_status'] === 'unpaid'): ?>
                                            <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#markPaidModal"
                                                data-id="<?= (int)$o['order_id'] ?>"
                                                data-number="<?= e($o['order_number']) ?>"
                                                data-total="<?= (float)$o['total_amount'] ?>"
                                                title="Mark as Paid">
                                                <i class="bi bi-check-lg"></i> Pay
                                            </button>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>admin/payments/receipt.php?order_id=<?= (int)$o['order_id'] ?>" class="btn btn-info btn-sm" title="Print Receipt">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Cancel order (works for both unpaid & paid) -->
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#cancelOrderModal"
                                            data-id="<?= (int)$o['order_id'] ?>"
                                            data-number="<?= e($o['order_number']) ?>"
                                            data-paid="<?= e(strtoupper($o['payment_status'])) ?>"
                                            title="Cancel Order">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ====== View Order Details Modal ====== -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:420px;margin-left:auto;margin-right:auto;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-receipt me-2"></i>Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="view-order-body"><!-- populated via JS --></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ====== Mark as Paid Modal ====== -->
<div class="modal fade" id="markPaidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:380px;margin-left:auto;margin-right:auto;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2"></i>Mark as Paid</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="order_id" id="mp-order-id">
                <div class="modal-body">
                    <p>Confirm payment for order <strong id="mp-order-number"></strong>?</p>
                    <div class="alert alert-info">Total amount: <strong id="mp-total"></strong></div>
                    <div class="mb-3">
                        <label class="form-label">Cash Received <span class="text-muted">(auto-fills exact amount)</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" min="0" class="form-control" name="cash_received" id="mp-cash" required>
                        </div>
                        <div class="form-text">Change: <strong id="mp-change">₱0.00</strong></div>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle"></i> Marking as paid will: record the sale, deduct inventory, and send the order to the kitchen.
                    </div>
                    <div class="alert alert-warning small mb-0 mt-2" id="mp-insufficient-warning" style="display:none;">
                        <i class="bi bi-exclamation-triangle"></i> Payment is insufficient. Please enter an exact amount to proceed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="mark_paid" value="1" id="mp-submit-btn" class="btn btn-success">
                        <i class="bi bi-check-lg"></i> Confirm &amp; Mark as Paid
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ====== Cancel Order Modal ====== -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:380px;margin-left:auto;margin-right:auto;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-2"></i>Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? This will remove the sale and restore inventory.');">
                <input type="hidden" name="order_id" id="co-order-id">
                <div class="modal-body">
                    <p>Are you sure you want to cancel order <strong id="co-order-number"></strong>?</p>
                    <div class="alert alert-warning small mb-0" id="co-paid-warning">
                        <i class="bi bi-exclamation-triangle"></i> This order is <strong>PAID</strong>. Cancelling it will:
                        <ul class="mb-0 mt-2">
                            <li>Remove the transaction/sale from today's sales</li>
                            <li>Restore the product quantities back to inventory</li>
                            <li>Remove it from the kitchen queue</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="cancel_order" value="1" class="btn btn-danger">
                        <i class="bi bi-x-lg"></i> Yes, Cancel Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const viewModal = document.getElementById('viewOrderModal');
    const markModal = document.getElementById('markPaidModal');
    const cancelModal = document.getElementById('cancelOrderModal');

    // ---- View Order ----
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function (e) {
            const btn   = e.relatedTarget;
            const id     = btn.dataset.id;
            const fetchUrl = '<?= BASE_URL ?>api/orders.php?action=view&order_id=' + id;

            fetch(fetchUrl)
                .then(r => r.json())
                .then(data => {
                    const body = document.getElementById('view-order-body');
                    if (!data.success) {
                        body.innerHTML = '<div class="alert alert-warning">' + (data.message||'Order not found') + '</div>';
                        return;
                    }
                    let html = '';
                    html += '<div class="row g-2 mb-2">';
                    html += '<div class="col-6"><strong>Order #:</strong></div>';
                    html += '<div class="col-6 text-end"><span class="fw-bold" style="letter-spacing:1px;">' + data.order.order_number + '</span></div>';
                    html += '<div class="col-6"><strong>Customer:</strong></div>';
                    html += '<div class="col-6 text-end">' + (data.order.customer_name||'-') + '</div>';
                    html += '<div class="col-6"><strong>Type:</strong></div>';
                    html += '<div class="col-6 text-end">' + ucfirst(data.order.order_type.replace('_',' ')) + '</div>';
                    html += '<div class="col-6"><strong>Status:</strong></div>';
                    html += '<div class="col-6 text-end">' + (data.order.payment_status.toUpperCase()) + '</div>';
                    html += '<div class="col-6"><strong>Total:</strong></div>';
                    html += '<div class="col-6 text-end"><span class="fw-bold">' + formatPeso(data.order.total_amount) + '</span></div>';
                    html += '</div>';

                    html += '<table class="table table-sm mb-0"><thead><tr><th style="width:40%">Item</th><th>Qty</th><th style="width:25%">Price</th><th style="width:25%">Subtotal</th></tr></thead><tbody>';
                    let grand = 0;
                    data.items.forEach(it => {
                        grand += parseFloat(it.subtotal);
                        html += '<tr><td>' + it.product_name + '</td><td>' + it.quantity + '</td><td>' + formatPeso(it.price) + '</td><td>' + formatPeso(it.subtotal) + '</td></tr>';
                    });
                    html += '</tbody><tfoot><tr class="fw-bold"><td colspan="3" class="text-end">GRAND TOTAL</td><td>' + formatPeso(data.order.total_amount) + '</td></tr></tfoot></table>';
                    body.innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('view-order-body').innerHTML = '<div class="alert alert-danger">Failed to load order.</div>';
                });
        });
    }

    // ---- Mark as Paid ----
    if (markModal) {
        markModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            document.getElementById('mp-order-id').value = btn.dataset.id;
            document.getElementById('mp-order-number').textContent = btn.dataset.number;
            const total = parseFloat(btn.dataset.total) || 0;
            document.getElementById('mp-total').textContent = formatPeso(total);
            document.getElementById('mp-cash').value = total.toFixed(2);
            document.getElementById('mp-change').textContent = formatPeso(0);

            const cashInput = document.getElementById('mp-cash');
            cashInput.dispatchEvent(new Event('input'));
        });

        const cashInput = document.getElementById('mp-cash');
        cashInput.addEventListener('input', () => {
            const cash = parseFloat(cashInput.value) || 0;
            const total = parseFloat(document.getElementById('mp-total').textContent.replace(/[^0-9.]/g,'')) || 0;
            const change = cash - total;
            const changeEl = document.getElementById('mp-change');
            const submitBtn = document.getElementById('mp-submit-btn');
            const warning = document.getElementById('mp-insufficient-warning');

            changeEl.textContent = change >= 0 ? formatPeso(change) : 'Insufficient';
            changeEl.classList.toggle('text-danger', change < 0);
            submitBtn.disabled = change < 0;
            warning.style.display = change < 0 ? 'block' : 'none';
        });
    }

    // ---- Cancel Order ----
    if (cancelModal) {
        cancelModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            document.getElementById('co-order-id').value = btn.dataset.id;
            document.getElementById('co-order-number').textContent = btn.dataset.number;

            // Show/hide the PAID warning based on the order's payment status
            const warning = document.getElementById('co-paid-warning');
            const isPaid = btn.dataset.paid === 'PAID';
            warning.style.display = isPaid ? 'block' : 'none';
            if (!isPaid) {
                warning.innerHTML = '<i class="bi bi-info-circle"></i> This order is <strong>UNPAID</strong>. Cancelling it will simply remove it from the settlement list.';
            }
        });
    }
});

function ucfirst(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
</script>
</content>

