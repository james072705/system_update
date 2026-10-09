<?php
/**
 * Shared helper functions
 */
require_once __DIR__ . '/database.php';

/* ============ INPUT & OUTPUT ============ */

/** Sanitize a string for safe HTML output */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Sanitize raw input */
function sanitize($value) {
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

/* ============ FORMATTING ============ */

function format_currency($amount) {
    return CURRENCY . number_format((float)$amount, 2);
}

function format_number($num) {
    return number_format((float)$num);
}

function format_date($date, $fmt = 'M d, Y') {
    return $date ? date($fmt, strtotime($date)) : '—';
}

function format_datetime($date) {
    return $date ? date('M d, Y h:i A', strtotime($date)) : '—';
}

function time_ago($datetime) {
    if (!$datetime) return '—';
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

/* ============ INVENTORY HELPERS ============ */

function get_stock($product_id) {
    $stmt = db()->prepare('SELECT quantity FROM inventory WHERE product_id = ?');
    $stmt->execute([$product_id]);
    $row = $stmt->fetch();
    return $row ? (int)$row['quantity'] : 0;
}

/**
 * Compute days left before a product expires.
 * Returns:
 *   positive int  -> days left
 *   0             -> expires today
 *   negative int  -> days since expired
 *   null          -> no expiration date
 */
function days_until_expiry($expiration_date) {
    if (!$expiration_date) return null;
    $exp = strtotime(date('Y-m-d', strtotime($expiration_date)));
    $today = strtotime(date('Y-m-d'));
    return (int)round(($exp - $today) / 86400);
}

/** Human-friendly "X days left" label for an expiration date */
function expiry_label($expiration_date) {
    $days = days_until_expiry($expiration_date);
    if ($days === null) return null;
    if ($days < 0)  return 'Expired ' . abs($days) . ' day(s) ago';
    if ($days === 0) return 'Expires today';
    if ($days === 1) return '1 day left';
    return $days . ' days left';
}

function is_low_stock($product_id) {
    $stmt = db()->prepare(
        'SELECT i.quantity, p.reorder_level FROM inventory i
         JOIN products p ON p.product_id = i.product_id
         WHERE i.product_id = ?');
    $stmt->execute([$product_id]);
    $row = $stmt->fetch();
    return $row && $row['quantity'] <= $row['reorder_level'];
}

/* ============ NOTIFICATIONS ============ */

function add_notification($type, $message, $user_id = null) {
    $stmt = db()->prepare('INSERT INTO notifications (type, message, user_id) VALUES (?, ?, ?)');
    $stmt->execute([$type, $message, $user_id]);
}

function unread_notifications_count() {
    $user = current_user();
    if ($user) {
        $stmt = db()->prepare("SELECT COUNT(*) AS c FROM notifications WHERE status = 'unread' AND (user_id = ? OR user_id IS NULL)");
        $stmt->execute([$user['user_id']]);
    } else {
        $stmt = db()->query("SELECT COUNT(*) AS c FROM notifications WHERE status = 'unread' AND user_id IS NULL");
    }
    return (int)$stmt->fetch()['c'];
}

/**
 * AUTO-DEACTIVATE expiring products.
 * When a product has 5 days (or fewer) left before its expiration date,
 * its status is automatically set to 'inactive' so it can no longer be sold.
 * Once deactivated, it will not appear in the kiosk/POS until re-activated.
 */
function auto_deactivate_expiring_products() {
    $pdo = db();

    // Find active products expiring within 5 days (or already expired)
    // and set them inactive.
    $rows = $pdo->query(
        "SELECT p.product_id, p.product_name, p.expiration_date
         FROM products p
         WHERE p.status = 'active'
           AND p.expiration_date IS NOT NULL
           AND p.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 5 DAY)"
    )->fetchAll();

    foreach ($rows as $p) {
        // Deactivate the product
        $stmt = $pdo->prepare('UPDATE products SET status = "inactive" WHERE product_id = ?');
        $stmt->execute([$p['product_id']]);

        // Notify once (avoid spamming)
        $exists = $pdo->prepare(
            "SELECT COUNT(*) AS c FROM notifications
             WHERE type = 'expiry' AND message LIKE ? AND status = 'unread'");
        $exists->execute(['%' . $p['product_name'] . '% deactivated%']);
        if ((int)$exists->fetch()['c'] === 0) {
            add_notification('expiry', $p['product_name'] . ' expires on ' . $p['expiration_date'] . ' and has been auto-deactivated (within 5 days).');
        }
    }

    return count($rows);
}

/** Auto-scan inventory and push low-stock / expiry notifications */
function scan_inventory_alerts() {
    $pdo = db();

    // Auto-deactivate products expiring within 5 days
    auto_deactivate_expiring_products();

    // Low stock
    $low = $pdo->query(
        "SELECT p.product_name FROM products p
         JOIN inventory i ON i.product_id = p.product_id
         WHERE p.status = 'active' AND i.quantity <= p.reorder_level"
    )->fetchAll();

    foreach ($low as $p) {
        $exists = $pdo->prepare(
            "SELECT COUNT(*) AS c FROM notifications
             WHERE type = 'low_stock' AND message LIKE ? AND status = 'unread'");
        $exists->execute(['%' . $p['product_name'] . '%']);
        if ((int)$exists->fetch()['c'] === 0) {
            add_notification('low_stock', $p['product_name'] . ' is running low on stock.');
        }
    }

    // Expiring within 7 days
    $exp = $pdo->query(
        "SELECT product_name, expiration_date FROM products
         WHERE status = 'active' AND expiration_date IS NOT NULL
         AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
    )->fetchAll();

    foreach ($exp as $p) {
        $exists = $pdo->prepare(
            "SELECT COUNT(*) AS c FROM notifications
             WHERE type = 'expiry' AND message LIKE ? AND status = 'unread'");
        $exists->execute(['%' . $p['product_name'] . '%']);
        if ((int)$exists->fetch()['c'] === 0) {
            add_notification('expiry', $p['product_name'] . ' expires on ' . $p['expiration_date'] . '.');
        }
    }

    // Expired products
    $expired = $pdo->query(
        "SELECT product_name FROM products
         WHERE status = 'active' AND expiration_date IS NOT NULL
         AND expiration_date < CURDATE()"
    )->fetchAll();

    foreach ($expired as $p) {
        $exists = $pdo->prepare(
            "SELECT COUNT(*) AS c FROM notifications
             WHERE type = 'expired' AND message LIKE ? AND status = 'unread'");
        $exists->execute(['%' . $p['product_name'] . '%']);
        if ((int)$exists->fetch()['c'] === 0) {
            add_notification('expired', $p['product_name'] . ' has already expired.');
        }
    }
}

/* ============ AUDIT LOG ============ */

function log_activity($action, $details = '') {
    $userId = $_SESSION['user_id'] ?? null;
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '';

    // Only log if user_id is provided and actually exists in the database
    if ($userId !== null && $userId !== '') {
        $stmt = db()->prepare('SELECT COUNT(*) AS c FROM users WHERE user_id = ?');
        $stmt->execute([$userId]);
        if ((int)$stmt->fetch()['c'] === 0) {
            $userId = null;
        }
    }

    $stmt = db()->prepare(
        'INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $details, $ip]);
}

/* ============ NUMBER GENERATORS ============ */

function generate_invoice_number() {
    $stmt = db()->query("SELECT COUNT(*) AS c FROM sales WHERE DATE(sale_date) = CURDATE()");
    $count = (int)$stmt->fetch()['c'] + 1;
    return 'INV-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

function generate_order_number() {
    $stmt = db()->query("SELECT COUNT(*) AS c FROM orders WHERE DATE(order_date) = CURDATE()");
    $count = (int)$stmt->fetch()['c'] + 1;
    return 'ORD-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate a unique 6-digit order number for the kiosk receipt.
 * Format: 6 digits (e.g. 000123). Used by the customer to pay at the counter.
 */
function generate_short_order_number() {
    $stmt = db()->prepare(
        "SELECT MAX(CAST(order_number AS UNSIGNED)) AS last_num FROM orders WHERE DATE(order_date) = CURDATE()"
    );
    $stmt->execute();
    $last = $stmt->fetch();

    $num = 1;
    if ($last && !empty($last['last_num'])) {
        $num = (int)$last['last_num'] + 1;
        if ($num > 100) {
            $num = 1;
        }
    }

    return str_pad((string)$num, 2, '0', STR_PAD_LEFT);
}

/* ============ CART (session based) ============ */
/**
 * Two separate carts:
 *   - 'cart'     -> customer kiosk
 *   - 'pos_cart' -> admin/staff POS
 * This prevents POS items from appearing in the kiosk cart and vice versa.
 * All cart functions accept an optional $cartKey (default 'cart').
 */

function cart_key($cartKey = null) {
    return ($cartKey === 'pos') ? 'pos_cart' : 'cart';
}

function cart_init($cartKey = null) {
    $key = cart_key($cartKey);
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    return $key;
}

function cart_add($product_id, $qty = 1, $cartKey = null) {
    $key = cart_init($cartKey);
    if (isset($_SESSION[$key][$product_id])) {
        $_SESSION[$key][$product_id] += (int)$qty;
    } else {
        $_SESSION[$key][$product_id] = (int)$qty;
    }
}

function cart_update($product_id, $qty, $cartKey = null) {
    $key = cart_init($cartKey);
    if ((int)$qty <= 0) {
        unset($_SESSION[$key][$product_id]);
    } else {
        $_SESSION[$key][$product_id] = (int)$qty;
    }
}

function cart_remove($product_id, $cartKey = null) {
    $key = cart_init($cartKey);
    unset($_SESSION[$key][$product_id]);
}

function cart_clear($cartKey = null) {
    $key = cart_key($cartKey);
    $_SESSION[$key] = [];
}

function cart_count($cartKey = null) {
    $key = cart_init($cartKey);
    return array_sum($_SESSION[$key]);
}

function cart_total($cartKey = null) {
    $key = cart_init($cartKey);
    $total = 0;
    foreach ($_SESSION[$key] as $pid => $qty) {
        $stmt = db()->prepare('SELECT price FROM products WHERE product_id = ?');
        $stmt->execute([$pid]);
        if ($row = $stmt->fetch()) {
            $total += $row['price'] * $qty;
        }
    }
    return $total;
}

/** Returns cart items joined with product details */
function cart_items($cartKey = null) {
    $key = cart_init($cartKey);
    $items = [];
    foreach ($_SESSION[$key] as $pid => $qty) {
        $stmt = db()->prepare(
            'SELECT p.*, c.category_name, i.quantity AS stock
             FROM products p
             LEFT JOIN categories c ON c.category_id = p.category_id
             LEFT JOIN inventory i ON i.product_id = p.product_id
             WHERE p.product_id = ? AND p.status = "active"');
        $stmt->execute([$pid]);
        if ($row = $stmt->fetch()) {
            $row['cart_qty']  = $qty;
            $row['subtotal']  = $row['price'] * $qty;
            $items[] = $row;
        }
    }
    return $items;
}

/* ============ MISC ============ */

/** Flash message helper */
function flash($key) {
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

