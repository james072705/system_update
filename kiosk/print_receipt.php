<?php
/**
 * Kiosk - Isolated Receipt Print Page
 * Standalone page with NO kiosk UI chrome.
 * Designed for RAWBT / Fully Kiosk Browser thermal printing.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$orderNumber = $_GET['order_number'] ?? null;
$order = $_SESSION['last_order'] ?? null;

if (!$order && $orderNumber) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE order_number = ?');
    $stmt->execute([$orderNumber]);
    $dbOrder = $stmt->fetch();
    if ($dbOrder) {
        $items = db()->prepare(
            'SELECT oi.*, p.product_name FROM order_items oi
             LEFT JOIN products p ON p.product_id = oi.product_id
             WHERE oi.order_id = ? ORDER BY oi.item_id ASC'
        );
        $items->execute([$dbOrder['order_id']]);
        $items = $items->fetchAll();
        $order = [
            'order_number' => $dbOrder['order_number'],
            'customer_name' => $dbOrder['customer_name'],
            'total' => $dbOrder['total_amount'],
            'order_type' => $dbOrder['order_type'],
            'payment_method' => $dbOrder['payment_method'],
            'date' => $dbOrder['date'],
            'items' => $items
        ];
    }
}

if (!$order) {
    header('Location: ' . BASE_URL . 'kiosk/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Order #<?= e($order['order_number']) ?></title>
    <style>
        @page {
            margin: 0;
            size: 56mm auto;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            min-height: auto;
            background: #fff !important;
            color: #000;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .receipt-wrap {
            width: 54mm;
            background: #fff;
            padding: 2mm;
            margin: 0;
        }
        .r-head {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .r-head .biz {
            font-size: 14px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .r-head .addr {
            font-size: 10px;
            margin-top: 2px;
        }
        .r-head .dt {
            font-size: 10px;
            margin-top: 2px;
        }
        .r-section {
            margin-bottom: 6px;
        }
        .r-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .r-order-num {
            text-align: center;
            font-size: 26px;
            font-weight: 900;
            letter-spacing: 3px;
            margin: 6px 0;
            color: #000;
        }
        .r-row {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
            font-size: 12px;
        }
        .r-divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .r-total {
            font-size: 14px;
            font-weight: 900;
        }
        .r-footer {
            text-align: center;
            border-top: 2px dashed #000;
            padding-top: 5px;
            margin-top: 6px;
            font-size: 10px;
            line-height: 1.5;
        }
        .r-footer strong {
            font-size: 11px;
        }
        @media print {
            html, body {
                background: #fff !important;
                margin: 0;
                padding: 0;
            }
            .receipt-wrap {
                width: 54mm;
                padding: 2mm;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-wrap">
        <div class="r-head">
            <div class="biz"><?= e(SITE_NAME) ?></div>
            <div class="addr">Caloocan City, Philippines</div>
            <div class="dt"><?= date('M d, Y h:i A', strtotime($order['date'])) ?></div>
        </div>

        <div class="r-section">
            <div class="r-label">Your Order Number</div>
            <div class="r-order-num"><?= e($order['order_number']) ?></div>
        </div>

        <div class="r-divider"></div>

        <div class="r-section">
            <div class="r-row">
                <span>Total</span>
                <span><?= format_currency($order['total']) ?></span>
            </div>
            <div class="r-row">
                <span>Payment</span>
                <span>Cash (Pending)</span>
            </div>
            <div class="r-row">
                <span>Status</span>
                <span>UNPAID</span>
            </div>
        </div>

        <div class="r-divider"></div>

        <div class="r-footer">
            Please proceed to the <strong>counter</strong> to pay.<br>
            Present this <strong>order number</strong> to the cashier.<br>
            Your order will be processed once payment is confirmed.
        </div>
    </div>

    <script>
        function doPrint() {
            window.scrollTo(0, 0);
            window.print();
        }

        window.addEventListener('load', function() {
            setTimeout(doPrint, 5000);
        });
        window.onafterprint = function() {
            setTimeout(function() {
                window.location.href = '<?= BASE_URL ?>kiosk/index.php';
            }, 2000);
        };
        setTimeout(function() {
            window.location.href = '<?= BASE_URL ?>kiosk/index.php';
        }, 30000);
    </script>
</body>
</html>
