<?php
/**
 * Kitchen - Order details (kitchen role)
 */
require_once __DIR__ . '/../includes/kitchen_header.php';

$id = (int)($_GET['id'] ?? 0);
$order = db()->prepare('SELECT * FROM orders WHERE order_id = ? AND payment_status = "paid"');
$order->execute([$id]);
$order = $order->fetch();

if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    header('Location: ' . BASE_URL . 'kitchen/index.php');
    exit;
}

$items = db()->prepare(
    "SELECT oi.*, p.product_name FROM order_items oi
     JOIN products p ON p.product_id = oi.product_id WHERE oi.order_id = ?");
$items->execute([$id]);
$orderItems = $items->fetchAll();

$pageTitle = 'Order Details';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt me-2"></i>Order <?= e($order['order_number']) ?></span>
                <a href="<?= BASE_URL ?>kitchen/index.php" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="k-detail-item">
                            <div class="label">Customer</div>
                            <div class="value"><?= e($order['customer_name']) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="k-detail-item">
                            <div class="label">Order Type</div>
                            <div class="value"><?= ucfirst(str_replace('_',' ',$order['order_type'])) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="k-detail-item">
                            <div class="label">Status</div>
                            <div class="value">
                                <span class="badge-k <?= match($order['status']) {
                                    'pending' => 'st-pending', 'preparing' => 'st-preparing',
                                    'ready' => 'st-ready', 'completed' => 'st-completed', default => 'st-cancelled'
                                } ?>"><?= ucfirst($order['status']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-uppercase text-muted mb-3">Items</h6>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>
                        <?php foreach ($orderItems as $oi): ?>
                            <tr>
                                <td><?= e($oi['product_name']) ?></td>
                                <td class="text-center"><?= (int)$oi['quantity'] ?></td>
                                <td class="text-end"><?= format_currency($oi['price']) ?></td>
                                <td class="text-end fw-bold"><?= format_currency($oi['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr><th colspan="3" class="text-end">Total</th><th class="text-end"><?= format_currency($order['total_amount']) ?></th></tr>
                        </tfoot>
                    </table>
                </div>

                <?php if ($order['notes']): ?>
                    <div class="alert alert-info"><i class="bi bi-chat-left-text me-2"></i><?= e($order['notes']) ?></div>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-4">
                    <?php if ($order['status'] === 'pending'): ?>
                        <button class="btn btn-info order-status" data-id="<?= (int)$order['order_id'] ?>" data-status="preparing"><i class="bi bi-fire"></i> Start Preparing</button>
                    <?php elseif ($order['status'] === 'preparing'): ?>
                        <button class="btn btn-success order-status" data-id="<?= (int)$order['order_id'] ?>" data-status="ready"><i class="bi bi-bell"></i> Ready to Serve</button>
                    <?php elseif ($order['status'] === 'ready'): ?>
                        <button class="btn btn-secondary order-status" data-id="<?= (int)$order['order_id'] ?>" data-status="completed"><i class="bi bi-check2"></i> Mark Completed</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.order-status').forEach(btn => {
    btn.addEventListener('click', () => {
        fetch(`${BASE_URL}api/orders.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update_status&order_id=${btn.dataset.id}&status=${btn.dataset.status}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { showToast('Order updated', 'success'); setTimeout(() => location.reload(), 600); }
            else showToast(data.message || 'Error', 'error');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/kitchen_footer.php'; ?>
</content>
