<?php
/**
 * Display Site - Public Kitchen Display
 * Shows ONLY order numbers in two columns:
 * PREPARING ORDERS and NOW SERVING
 *
 * This is a SEPARATE site from the kitchen management site.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// When there are 5 or more ready orders, auto-complete the oldest ones
// so only the newest 1 remains in Now Serving
$readyCount = (int)db()->query("SELECT COUNT(*) FROM orders WHERE status = 'ready' AND payment_status = 'paid'")->fetchColumn();
if ($readyCount >= 5) {
    $toComplete = $readyCount - 1;
    $stmt = db()->prepare("UPDATE orders SET status = 'completed' WHERE status = 'ready' AND payment_status = 'paid' ORDER BY order_date ASC LIMIT :limit");
    $stmt->bindValue(':limit', $toComplete, PDO::PARAM_INT);
    $stmt->execute();
}

// Fetch preparing orders - ONLY order_number
$preparing = db()->query(
    "SELECT order_id, order_number
     FROM orders
     WHERE status = 'preparing' AND payment_status = 'paid'
     ORDER BY order_date ASC"
)->fetchAll();

// Fetch ready to serve orders - ONLY order_number
$ready = db()->query(
    "SELECT order_id, order_number
     FROM orders
     WHERE status = 'ready' AND payment_status = 'paid'
     ORDER BY order_date ASC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display | <?= e(SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/darkmode.css?v=3.0" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #1a1f2b;
            color: #fff;
            font-family: 'Segoe UI', Arial, sans-serif;
            height: 100vh;
            overflow: hidden;
        }

        .display-header {
            background: linear-gradient(135deg, #e63946, #c1121f);
            color: #fff;
            padding: 1.2rem 2rem;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0,0,0,.3);
        }
        .display-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            letter-spacing: 3px;
            margin: 0;
            text-transform: uppercase;
        }
        .display-header small {
            opacity: .85;
            font-size: 1rem;
            letter-spacing: 1px;
        }

        .display-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 2rem;
            height: calc(100vh - 100px);
            overflow: hidden;
        }

        .display-column {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            overflow-y: auto;
            padding-right: .5rem;
        }

        .column-header {
            background: linear-gradient(135deg, #f4a261, #e76f51);
            color: #1a1f2b;
            padding: 1.2rem 1.5rem;
            border-radius: 16px;
            font-weight: 900;
            font-size: 1.5rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,.2);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .column-header.now-serving {
            background: linear-gradient(135deg, #2a9d8f, #1d3557);
            color: #fff;
        }

        .order-number-card {
            background: #232a3a;
            border: 2px solid #2c3548;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all .3s;
            box-shadow: 0 4px 12px rgba(0,0,0,.2);
        }
        .order-number-card.now-serving .order-num {
            color: #2a9d8f;
            text-shadow: 0 2px 10px rgba(42,157,143,.3);
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .order-number-card .order-num {
            font-size: 5rem;
            font-weight: 900;
            letter-spacing: 6px;
            color: #e63946;
            text-shadow: 0 2px 10px rgba(230,57,70,.3);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #9aa7b8;
        }
        .empty-state i {
            font-size: 4rem;
            opacity: .3;
            display: block;
            margin-bottom: 1rem;
        }
        .empty-state p {
            font-size: 1.2rem;
            margin: 0;
        }

        ::-webkit-scrollbar { width: 12px; height: 12px; }
        ::-webkit-scrollbar-track { background: #1a1f2b; }
        ::-webkit-scrollbar-thumb { background: #2c3548; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #3d4a5f; }

        @media (max-width: 991.98px) {
            .display-container {
                grid-template-columns: 1fr;
                height: auto;
                overflow-y: auto;
            }
            .display-header h1 { font-size: 1.8rem; }
            .order-number-card .order-num { font-size: 3.5rem; }
        }
    </style>
</head>
<body>
    <div class="display-header">
        <h1><i class="bi bi-tv me-3"></i><?= e(SITE_NAME) ?> Kitchen Display</h1>
        <small>Order Number Caller</small>
    </div>

    <div class="display-container">
        <!-- PREPARING ORDERS -->
        <div class="display-column" id="preparing-column">
            <div class="column-header">
                <i class="bi bi-fire me-2"></i>Preparing Orders
            </div>
            <?php if (empty($preparing)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>No orders being prepared</p>
                </div>
            <?php else: ?>
                <?php foreach ($preparing as $order): ?>
                    <div class="order-number-card" data-order="<?= e($order['order_number']) ?>">
                        <div class="order-num"><?= e($order['order_number']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- NOW SERVING -->
        <div class="display-column" id="ready-column">
            <div class="column-header now-serving">
                <i class="bi bi-bell me-2"></i>Now Serving
            </div>
            <?php if (empty($ready)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>No orders ready to serve</p>
                </div>
            <?php else: ?>
                <?php foreach ($ready as $order): ?>
                    <div class="order-number-card now-serving" data-order="<?= e($order['order_number']) ?>">
                        <div class="order-num"><?= e($order['order_number']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    let announcedOrders = new Set();
    let isAnnouncing = false;
    let announcementTimeout = null;
    let lastReadyOrders = [];
    let announcementQueue = [];

    function renderOrders(preparing, ready) {
        const preparingCol = document.getElementById('preparing-column');
        const readyCol = document.getElementById('ready-column');

        // Render preparing orders
        if (preparing.length === 0) {
            preparingCol.innerHTML = '<div class="column-header"><i class="bi bi-fire me-2"></i>Preparing Orders</div><div class="empty-state"><i class="bi bi-inbox"></i><p>No orders being prepared</p></div>';
        } else {
            let html = '<div class="column-header"><i class="bi bi-fire me-2"></i>Preparing Orders</div>';
            preparing.forEach(num => {
                html += '<div class="order-number-card" data-order="' + num + '"><div class="order-num">' + num + '</div></div>';
            });
            preparingCol.innerHTML = html;
        }

        // Render ready orders
        if (ready.length === 0) {
            readyCol.innerHTML = '<div class="column-header now-serving"><i class="bi bi-bell me-2"></i>Now Serving</div><div class="empty-state"><i class="bi bi-inbox"></i><p>No orders ready to serve</p></div>';
        } else {
            let html = '<div class="column-header now-serving"><i class="bi bi-bell me-2"></i>Now Serving</div>';
            ready.forEach(num => {
                html += '<div class="order-number-card now-serving" data-order="' + num + '"><div class="order-num">' + num + '</div></div>';
            });
            readyCol.innerHTML = html;
        }
    }

    function announceOrder(orderNum) {
        if (!('speechSynthesis' in window)) return;
        const text = 'NOW SERVING ORDER NUMBER ' + orderNum;

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 0.9;
        utterance.pitch = 1;
        utterance.volume = 1;

        utterance.onend = () => {
            // After first announcement, wait 2 seconds then announce again
            announcementTimeout = setTimeout(() => {
                const repeat = new SpeechSynthesisUtterance(text);
                repeat.rate = 0.9;
                repeat.pitch = 1;
                repeat.volume = 1;
                window.speechSynthesis.speak(repeat);
            }, 2000);
        };

        window.speechSynthesis.speak(utterance);
    }

    function processQueue() {
        if (isAnnouncing || announcementQueue.length === 0) return;

        isAnnouncing = true;
        const orderNum = announcementQueue.shift();
        announceOrder(orderNum);

        // After 2nd announcement + 3 second pause, process next in queue
        setTimeout(() => {
            isAnnouncing = false;
            if (announcementQueue.length > 0) {
                processQueue();
            }
        }, 7000);
    }

    function checkNewOrders(ready) {
        const currentSet = new Set(ready);
        const previousSet = new Set(lastReadyOrders);

        // Find new orders that weren't in the previous list
        const newOrders = ready.filter(num => !previousSet.has(num));

        // Add new orders to announcement queue
        newOrders.forEach(num => {
            if (!announcedOrders.has(num)) {
                announcedOrders.add(num);
                announcementQueue.push(num);
            }
        });

        // Clean up announced orders that are no longer in the ready list
        announcedOrders.forEach(num => {
            if (!currentSet.has(num)) {
                announcedOrders.delete(num);
            }
        });

        lastReadyOrders = ready;

        // Start processing queue if not already announcing
        processQueue();
    }

    // Poll the API every 2 seconds for real-time updates
    async function pollOrders() {
        try {
            const response = await fetch('<?= BASE_URL ?>api/display.php');
            const data = await response.json();
            renderOrders(data.preparing, data.ready);
            checkNewOrders(data.ready);
        } catch (e) {
            console.error('Failed to fetch orders:', e);
        }
    }

    // Initial load - announce existing orders
    document.addEventListener('DOMContentLoaded', () => {
        const initialReady = <?= json_encode(array_column($ready, 'order_number')) ?>;
        lastReadyOrders = initialReady;
        initialReady.forEach(num => announcedOrders.add(num));

        // Announce first order immediately if exists
        if (initialReady.length > 0) {
            announcementQueue.push(initialReady[0]);
            processQueue();
        }

        // Poll every 2 seconds for new orders
        setInterval(pollOrders, 2000);
    });

    // Full page reload every 20 seconds as fallback
    setInterval(() => {
        if (announcementTimeout) clearTimeout(announcementTimeout);
        location.reload();
    }, 20000);
    </script>
</body>
</html>
