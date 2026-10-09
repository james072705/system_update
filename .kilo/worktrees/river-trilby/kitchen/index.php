<?php
/**
 * Kitchen - Secure order management (kitchen role login required)
 * Displays only customer orders from the kiosk for preparation.
 */
require_once __DIR__ . '/../includes/kitchen_header.php';

$activeTab = $_GET['tab'] ?? 'pending';
$orders = db()->query(
    "SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS item_count
     FROM orders o
     WHERE o.status = '" . $activeTab . "' AND o.payment_status = 'paid'
     ORDER BY o.order_date DESC LIMIT 40")->fetchAll();
?>
<div class="row g-3">
    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5 class="mt-3">No <?= e($activeTab) ?> orders</h5>
            <p>Waiting for new orders from the customer kiosk...</p>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order):
            $items = db()->prepare(
                "SELECT oi.*, p.product_name FROM order_items oi
                 JOIN products p ON p.product_id = oi.product_id
                 WHERE oi.order_id = ?");
            $items->execute([$order['order_id']]);
            $orderItems = $items->fetchAll();

            $badge = match($order['status']) {
                'pending'   => 'st-pending',
                'preparing' => 'st-preparing',
                'ready'     => 'st-ready',
                'completed' => 'st-completed',
                default     => 'st-cancelled'
            };
        ?>
        <div class="col-md-6 col-xl-4">
            <div class="k-order-card h-100">
                <div class="k-head">
                    <h6><i class="bi bi-receipt me-1"></i><?= e($order['order_number']) ?></h6>
                    <span class="badge-k <?= $badge ?>"><?= ucfirst($order['status']) ?></span>
                </div>
                <div class="k-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small"><i class="bi bi-person"></i> <?= e($order['customer_name']) ?></span>
                        <span class="badge-k st-pending"><i class="bi bi-shop"></i> <?= ucfirst(str_replace('_',' ',$order['order_type'])) ?></span>
                    </div>
                    <div class="k-time mb-2"><i class="bi bi-clock"></i> <?= format_datetime($order['order_date']) ?></div>

                    <hr class="opacity-25 my-2">
                    <?php foreach ($orderItems as $oi): ?>
                        <div class="k-item">
                            <span><strong><?= (int)$oi['quantity'] ?>×</strong> <?= e($oi['product_name']) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($order['notes']): ?>
                        <div class="alert alert-info py-1 px-2 small mt-2 mb-2"><i class="bi bi-chat-left-text"></i> <?= e($order['notes']) ?></div>
                    <?php endif; ?>

                    <div class="k-total">
                        <span>Total</span><span><?= format_currency($order['total_amount']) ?></span>
                    </div>
                </div>
                <div class="k-actions">
                    <?php if ($order['status'] === 'pending'): ?>
                        <button class="btn btn-info btn-sm flex-fill order-status" data-id="<?= (int)$order['order_id'] ?>" data-status="preparing">
                            <i class="bi bi-fire"></i> Start Preparing
                        </button>
                    <?php elseif ($order['status'] === 'preparing'): ?>
                        <button class="btn btn-success btn-sm flex-fill order-status" data-id="<?= (int)$order['order_id'] ?>" data-status="ready">
                            <i class="bi bi-bell"></i> Ready to Serve
                        </button>
                    <?php elseif ($order['status'] === 'ready'): ?>
                        <button class="btn btn-secondary btn-sm flex-fill order-status" data-id="<?= (int)$order['order_id'] ?>" data-status="completed">
                            <i class="bi bi-check2"></i> Completed
                        </button>
                    <?php elseif ($order['status'] !== 'completed'): ?>
                        <a href="<?= BASE_URL ?>kitchen/order_details.php?id=<?= (int)$order['order_id'] ?>" class="btn btn-outline-info btn-sm flex-fill">View</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Update order status via AJAX (no login required)
document.querySelectorAll('.order-status').forEach(btn => {
    btn.addEventListener('click', () => {
        fetch(`${BASE_URL}api/orders.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update_status&order_id=${btn.dataset.id}&status=${btn.dataset.status}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Order updated', 'success');
                setTimeout(() => location.reload(), 600);
            } else {
                showToast(data.message || 'Error', 'error');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/kitchen_footer.php'; ?>
</content>
