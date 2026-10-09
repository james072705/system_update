<?php
/**
 * Kitchen - Pending orders (kitchen role)
 */
require_once __DIR__ . '/../includes/kitchen_header.php';

$orders = db()->query(
    "SELECT o.* FROM orders o WHERE o.status='pending' AND o.payment_status='paid'
     ORDER BY o.order_date DESC LIMIT 50")->fetchAll();
?>
<div class="card">
    <div class="card-header"><i class="bi bi-hourglass-split me-2"></i>Pending Orders</div>
    <div class="card-body">
        <?php if (empty($orders)): ?>
            <div class="empty-state"><i class="bi bi-inbox"></i><p>No pending orders.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Order #</th><th>Customer</th><th>Type</th><th>Date</th><th>Total</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong><?= e($o['order_number']) ?></strong></td>
                            <td><?= e($o['customer_name']) ?></td>
                            <td><span class="badge-k st-pending"><?= ucfirst(str_replace('_',' ',$o['order_type'])) ?></span></td>
                            <td><?= format_datetime($o['order_date']) ?></td>
                            <td><?= format_currency($o['total_amount']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>kitchen/order_details.php?id=<?= (int)$o['order_id'] ?>" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/kitchen_footer.php'; ?>
</content>
