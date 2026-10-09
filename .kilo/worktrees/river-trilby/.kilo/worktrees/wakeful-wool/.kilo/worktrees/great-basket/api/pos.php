<?php
/**
 * POS API - Process sale complete
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_role(['admin', 'staff']);

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'complete') {
    $items = cart_items('pos');
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
        exit;
    }

$cash = (float)($_POST['cash'] ?? 0);
    $orderType = $_POST['order_type'] ?? 'dine_in';
    $customerName = trim($_POST['customer_name'] ?? 'Guest');
    // POS is cash-only; always record payment method as Cash
    $paymentMethod = 'Cash';
    $total = array_sum(array_column($items, 'subtotal'));

    if ($cash < $total) {
        echo json_encode(['success' => false, 'message' => 'Insufficient cash.']);
        exit;
    }

    $change = $cash - $total;

    // Validate stock & expiry
    foreach ($items as $item) {
        if ((int)$item['stock'] < (int)$item['cart_qty']) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock for ' . $item['product_name'] . '.']);
            exit;
        }
        if ($item['expiration_date'] && $item['expiration_date'] < date('Y-m-d')) {
            echo json_encode(['success' => false, 'message' => $item['product_name'] . ' is expired and cannot be sold.']);
            exit;
        }
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        // Create order (for kitchen) - payment already taken at POS, so mark it PAID
        $orderNo = generate_order_number();
        $stmt = $pdo->prepare(
            'INSERT INTO orders (order_number, customer_name, status, total_amount, order_type, payment_method, payment_status, paid_at, notes)
             VALUES (?, ?, "pending", ?, ?, ?, "paid", NOW(), ?)');
        $stmt->execute([$orderNo, $customerName, $total, $orderType, $paymentMethod, 'POS sale']);
        $orderId = $pdo->lastInsertId();

        // Create sale
        $invoiceNo = generate_invoice_number();
        $cashier = $_SESSION['fullname'] ?? 'POS';
        $stmt = $pdo->prepare(
            'INSERT INTO sales (invoice_number, total_amount, cash, change_amount, cashier, payment_method, order_type)
             VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$invoiceNo, $total, $cash, $change, $cashier, $paymentMethod, $orderType]);
        $saleId = $pdo->lastInsertId();

        // Insert sale items + order items + update inventory
        foreach ($items as $item) {
            $pid = (int)$item['product_id'];
            $qty = (int)$item['cart_qty'];
            $price = (float)$item['price'];
            $subtotal = $price * $qty;

            $stmt = $pdo->prepare('INSERT INTO sales_items (sale_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$saleId, $pid, $qty, $price, $subtotal]);

            $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$orderId, $pid, $qty, $price, $subtotal]);

            $stmt = $pdo->prepare('UPDATE inventory SET quantity = quantity - ? WHERE product_id = ?');
            $stmt->execute([$qty, $pid]);

            $stmt = $pdo->prepare('INSERT INTO stock_movements (product_id, movement_type, quantity, remarks, user_id) VALUES (?, "sale", ?, ?, ?)');
            $stmt->execute([$pid, $qty, 'Sale #' . $invoiceNo, $_SESSION['user_id']]);
        }

        $pdo->commit();
        log_activity('POS', 'Completed sale ' . $invoiceNo . ' total ' . $total);
        scan_inventory_alerts();
        cart_clear('pos');

        echo json_encode([
            'success' => true,
            'sale_id' => $saleId,
            'receipt_url' => BASE_URL . 'pos/receipt.php?sale_id=' . $saleId
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
