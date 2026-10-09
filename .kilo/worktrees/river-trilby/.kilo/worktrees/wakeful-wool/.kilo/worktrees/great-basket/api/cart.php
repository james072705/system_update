<?php
/**
 * Cart API - session-based cart operations
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
// Which cart? 'pos' -> POS cart; default (kiosk) -> kiosk cart
$cartKey = ($_GET['cart'] ?? $_POST['cart'] ?? 'kiosk') === 'pos' ? 'pos' : 'kiosk';

switch ($action) {
case 'add':
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));

        // Validate product exists & active
        $stmt = db()->prepare('SELECT * FROM products WHERE product_id = ? AND status = "active"');
        $stmt->execute([$pid]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit;
        }

        // Check expiry
        if ($product['expiration_date'] && $product['expiration_date'] < date('Y-m-d')) {
            echo json_encode(['success' => false, 'message' => $product['product_name'] . ' is expired and cannot be ordered.']);
            exit;
        }

        // Check stock limit
        $stock = get_stock($pid);
        if ($stock <= 0) {
            echo json_encode(['success' => false, 'message' => $product['product_name'] . ' is sold out.']);
            exit;
        }

// Current qty already in cart + new qty must not exceed stock
        $key = cart_init($cartKey);
        $sessionKey = cart_key($cartKey);
        $currentInCart = (int)($_SESSION[$sessionKey][$pid] ?? 0);
        $newTotal = $currentInCart + $qty;
        if ($newTotal > $stock) {
            $remaining = $stock - $currentInCart;
            echo json_encode([
                'success' => false,
                'message' => 'Only ' . $remaining . ' available for ' . $product['product_name'] . '.'
            ]);
            exit;
        }

        cart_add($pid, $qty, $cartKey);
        echo json_encode([
            'success' => true,
            'message' => $product['product_name'] . ' added to cart',
            'count'   => cart_count($cartKey)
        ]);
        break;

    case 'update':
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 0);

        // Enforce stock limit
        if ($qty > 0) {
            $stock = get_stock($pid);
            if ($qty > $stock) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Only ' . $stock . ' available for this item.'
                ]);
                exit;
            }
        }

        cart_update($pid, $qty, $cartKey);
        echo json_encode(['success' => true, 'count' => cart_count($cartKey)]);
        break;

    case 'remove':
        $pid = (int)($_POST['product_id'] ?? 0);
        cart_remove($pid, $cartKey);
        echo json_encode(['success' => true, 'count' => cart_count($cartKey)]);
        break;

    case 'clear':
        cart_clear($cartKey);
        echo json_encode(['success' => true, 'count' => 0]);
        break;

    case 'count':
        echo json_encode(['count' => cart_count($cartKey)]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
