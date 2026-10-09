<?php
/**
 * Printable Official Receipt for Ferry's Restobar
 * Used in the admin Payment Settlement screen after marking an order as PAID.
 *
 * Requires passed variables:
 *   $receipt_order  array  - order details
 *   $receipt_items  array  - order_items rows
 *   $receipt_cashier string - the admin/staff who processed payment
 *   $receipt_paid_at string - paid datetime
 *   $receipt_change  float  - change amount
 */
$receipt_change = $receipt_change ?? 0;
$receipt_paid_at = $receipt_paid_at ?? date('Y-m-d H:i:s');
$receipt_cashier = $receipt_cashier ?? 'Cashier';
$receipt_redirect_url = $receipt_redirect_url ?? '';
?>
<div id="receipt-print-area" class="receipt mx-auto" style="font-family:'Courier New',Courier,monospace;">
    <div class="r-head text-center" style="border-bottom:2px dashed #999;padding-bottom:.7rem;margin-bottom:.7rem;">
        <div style="font-size:1.35rem;font-weight:800;letter-spacing:1px;"><?= e(BUSINESS_NAME) ?></div>
        <div style="font-size:.8rem;"><?= e(BUSINESS_ADDRESS) ?></div>
        <div style="font-size:.8rem;">Contact: <?= e(BUSINESS_PHONE) ?> | <?= e(BUSINESS_EMAIL) ?></div>
    </div>

    <div style="font-size:.85rem;">
        <div class="r-row"><span>Order No:</span><span style="font-weight:800;font-size:1.05rem;"><?= e($receipt_order['order_number']) ?></span></div>
        <div class="r-row"><span>Cashier:</span><span><?= e($receipt_cashier) ?></span></div>
        <div class="r-row"><span>Date:</span><span><?= date('M d, Y h:i A', strtotime($receipt_paid_at)) ?></span></div>
        <div class="r-row"><span>Served:</span><span><?= ucfirst(str_replace('_',' ',$receipt_order['order_type'])) ?></span></div>
        <div class="r-row"><span>Customer:</span><span><?= e($receipt_order['customer_name']) ?></span></div>
    </div>

    <div style="border-top:1px dashed #999;border-bottom:1px dashed #999;margin:.6rem 0;padding:.4rem 0;font-size:.85rem;">
        <div class="r-row" style="font-weight:700;text-transform:uppercase;">
            <span>Item</span><span style="text-align:right;">Qty  x  Price</span>
        </div>
        <?php $grand = 0; foreach ($receipt_items as $ri): ?>
            <div class="r-row">
                <span><?= e($ri['product_name'] ?? 'Item') ?></span>
                <span style="text-align:right;"><?= (int)$ri['quantity'] ?> &times; <?= number_format((float)$ri['price'],2) ?></span>
            </div>
            <div class="r-row" style="font-weight:700;">
                <span></span><span style="text-align:right;">= <?= number_format((float)$ri['subtotal'],2) ?></span>
            </div>
            <?php $grand += (float)$ri['subtotal']; ?>
        <?php endforeach; ?>
    </div>

    <div style="font-size:.9rem;">
        <div class="r-row"><span>Subtotal</span><span>₱ <?= number_format($grand,2) ?></span></div>
        <div class="r-row" style="font-size:1.15rem;font-weight:800;"><span>Total</span><span>₱ <?= number_format((float)$receipt_order['total_amount'],2) ?></span></div>
        <div class="r-row"><span>Payment</span><span><?= e($receipt_order['payment_method'] ?? 'Cash') ?></span></div>
        <div class="r-row"><span>Cash</span><span>₱ <?= number_format((float)($receipt_cash_received ?? $receipt_order['total_amount']),2) ?></span></div>
        <div class="r-row"><span>Change</span><span>₱ <?= number_format((float)$receipt_change,2) ?></span></div>
    </div>

    <div style="border-top:2px dashed #999;margin-top:.7rem;padding-top:.7rem;text-align:center;font-size:.8rem;line-height:1.5;">
        <div>This Serve as your official Receipt.</div>
        <div>Thank you, and please come again.</div>
        <div>for feedback, please call or text us at</div>
        <div style="font-weight:700;"><?= e(BUSINESS_PHONE) ?></div>
        <div>Email: <?= e(BUSINESS_EMAIL) ?></div>
    </div>
</div>

<?php if (!isset($receipt_no_print)): ?>
<script>
function printReceipt() {
    window.print();
}
</script>
<?php endif; ?>

