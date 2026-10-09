<?php
/**
 * Kiosk - Customer self-ordering main page
 * Shows animated promotional ads first, then the menu after clicking "Start Order"
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$pageTitle = 'Order Online';
$selectedCat = (int)($_GET['category'] ?? 0);
$skipAds = isset($_GET['start']) && $_GET['start'] == 1;
$categories = db()->query('SELECT * FROM categories ORDER BY category_id')->fetchAll();

require_once __DIR__ . '/../includes/kiosk_header.php';
?>

<?php if ($skipAds): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const landing = document.getElementById('kiosk-landing');
        const menuWrap = document.getElementById('kiosk-menu-wrap');
        if (landing) landing.style.display = 'none';
        if (menuWrap) document.body.classList.add('kiosk-started');
    });
</script>
<?php endif; ?>

<!-- ============================================================
     LANDING / ADS SCREEN (full-screen, rotating every 5s)
     ============================================================ -->
<div class="kiosk-landing" id="kiosk-landing">
    <!-- Brand -->
    <div class="landing-brand">
        <div class="logo"><i class="bi bi-cup-straw"></i></div>
        <div>
            <h1><?= e(SITE_NAME) ?></h1>
            <small>Self-Ordering Kiosk</small>
        </div>
    </div>

<!-- ===== Ad Slide 1: Beers/Drinks ===== -->
    <div class="landing-slide active ad-gradient-1" data-slide="1">
        <div class="ad-shapes">
            <i class="bi bi-cup-straw" style="left:8%;  animation-duration:9s;"></i>
            <i class="bi bi-cup" style="right:12%; animation-duration:11s;"></i>
            <i class="bi bi-fire" style="left:70%; animation-duration:13s;"></i>
            <i class="bi bi-droplet-half" style="right:30%; animation-duration:10s;"></i>
            <i class="bi bi-lightning-charge" style="left:30%; animation-duration:12s;"></i>
        </div>
        <div class="ad-content">
            <span class="ad-badge"><i class="bi bi-star-fill me-1"></i>Best Sellers</span>
            <div class="ad-photo">
                <img src="<?= BASE_URL ?>img/beers.webp" alt="Ice-Cold Beers">
            </div>
            <h2>Ice-Cold Beers</h2>
            <p>San Miguel, Red Horse, San Mig Light &amp; more — perfectly chilled for you!</p>
        </div>
    </div>

    <!-- ===== Ad Slide 2: Wines ===== -->
    <div class="landing-slide ad-gradient-5" data-slide="2">
        <div class="ad-shapes">
            <i class="bi bi-droplet-half" style="left:10%; animation-duration:10s;"></i>
            <i class="bi bi-cup" style="right:15%; animation-duration:12s;"></i>
            <i class="bi bi-emoji-sunglasses" style="left:65%; animation-duration:9s;"></i>
            <i class="bi bi-brightness-high" style="right:35%; animation-duration:14s;"></i>
        </div>
        <div class="ad-content">
            <span class="ad-badge"><i class="bi bi-droplet-half me-1"></i>Classic Wines</span>
<div class="ad-photo">
                <img src="<?= BASE_URL ?>img/fine%20wines.webp" alt="Fine Wines">
            </div>
            <h2>Fine Wines</h2>
            <p>Bordeaux Red, Sauvignon Blanc &amp; more — sip, relax, unwind.</p>
        </div>
    </div>

<!-- ===== Ad Slide 3: Softdrinks ===== -->
    <div class="landing-slide ad-gradient-3" data-slide="3">
        <div class="ad-shapes">
            <i class="bi bi-moisture" style="left:12%; animation-duration:8s;"></i>
            <i class="bi bi-fire" style="right:20%; animation-duration:11s;"></i>
            <i class="bi bi-cup" style="left:55%; animation-duration:13s;"></i>
            <i class="bi bi-flag" style="right:10%; animation-duration:10s;"></i>
        </div>
<div class="ad-content">
            <span class="ad-badge"><i class="bi bi-moisture me-1"></i>Refreshing</span>
            <div class="ad-photo">
                <img src="<?= BASE_URL ?>img/soft%20drinks.jpg" alt="Softdrinks">
            </div>
            <h2>Softdrinks &amp; More</h2>
            <p>Iced tea, mineral water &amp; refreshing drinks — ice-cold and perfect any time.</p>
        </div>
    </div>

    <!-- ===== Ad Slide 4: Foods ===== -->
    <div class="landing-slide ad-gradient-4" data-slide="4">
        <div class="ad-shapes">
            <i class="bi bi-basket" style="left:15%; animation-duration:10s;"></i>
            <i class="bi bi-egg-fried" style="right:18%; animation-duration:12s;"></i>
            <i class="bi bi-droplet" style="left:60%; animation-duration:9s;"></i>
            <i class="bi bi-fire" style="right:25%; animation-duration:13s;"></i>
        </div>
<div class="ad-content">
            <span class="ad-badge"><i class="bi bi-egg-fried me-1"></i>Pulutan Faves</span>
            <div class="ad-photo">
                <img src="<?= BASE_URL ?>img/pulutan.jpg" alt="Masarap na Pulutan">
            </div>
            <h2>Masarap na Pulutan</h2>
            <p>Sisig, Buffalo Wings, Nachos &amp; Rice Bowls — perfect with your drinks!</p>
        </div>
    </div>

    <!-- ===== Ad Slide 5: Combo Offer ===== -->
    <div class="landing-slide ad-gradient-2" data-slide="5">
        <div class="ad-shapes">
            <i class="bi bi-gift" style="left:10%; animation-duration:9s;"></i>
            <i class="bi bi-cup-straw" style="right:22%; animation-duration:11s;"></i>
            <i class="bi bi-basket" style="left:70%; animation-duration:12s;"></i>
            <i class="bi bi-balloon-heart" style="right:12%; animation-duration:10s;"></i>
        </div>
        <div class="ad-content">
            <span class="ad-badge"><i class="bi bi-gift me-1"></i>Combo Promo</span>
            <div class="ad-photo">
                <img src="<?= BASE_URL ?>img/drinks%20%26%20pulutan.webp" alt="Drink + Pulutan Combo">
            </div>
            <h2>Drink + Pulutan Combo</h2>
            <p>Pair any beer with our sizzling pulutan and enjoy the perfect restobar night!</p>
        </div>
    </div>

    <!-- Dots indicator -->
    <div class="landing-dots" id="landing-dots">
        <button class="dot active" data-slide="1"></button>
        <button class="dot" data-slide="2"></button>
        <button class="dot" data-slide="3"></button>
        <button class="dot" data-slide="4"></button>
        <button class="dot" data-slide="5"></button>
    </div>

    <!-- Start Order button (centered) -->
    <button class="landing-start-btn" id="start-order-btn">
        <i class="bi bi-hand-index-thumb"></i> Start Order
    </button>
</div>

<!-- ============================================================
     MENU (shown after "Start Order")
     Background stays fixed and does not change
     ============================================================ -->
<div class="kiosk-menu-wrap" id="kiosk-menu-wrap">
    <!-- Fixed non-changing background -->
    <div class="kiosk-menu-bg"></div>

    <!-- Kiosk Topbar -->
    <div class="kiosk-topbar">
        <div class="k-brand">
            <div class="logo"><i class="bi bi-cup-straw"></i></div>
            <div>
                <h1><?= e(SITE_NAME) ?></h1>
                <small>Self-Ordering Kiosk</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-light btn-sm theme-toggle d-none d-md-flex"><i class="bi bi-moon-stars-fill"></i></button>
            <a href="<?= BASE_URL ?>kiosk/cart.php" class="cart-btn">
                <i class="bi bi-cart3"></i> <span class="cart-label">Cart</span>
                <span class="count cart-count"><?= cart_count() ?></span>
            </a>
        </div>
    </div>

    <!-- Category Nav -->
    <div class="kiosk-nav">
        <button class="cat-btn <?= $selectedCat === 0 ? 'active' : '' ?>" data-cat="all">
            <i class="bi bi-grid"></i> All
        </button>
        <?php foreach ($categories as $cat): ?>
        <button class="cat-btn <?= $selectedCat === (int)$cat['category_id'] ? 'active' : '' ?>" data-cat="<?= (int)$cat['category_id'] ?>">
            <i class="bi <?= e($cat['icon']) ?>"></i> <?= e($cat['category_name']) ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Search bar -->
    <div class="container mt-3">
        <div class="kiosk-search mx-auto">
            <i class="bi bi-search"></i>
            <input type="text" id="kiosk-search" placeholder="Search products...">
        </div>
    </div>

    <!-- Product Grid -->
    <div class="kiosk-grid">
        <?php
        $sql = "SELECT p.*, c.category_name, i.quantity AS stock
                FROM products p
                LEFT JOIN categories c ON c.category_id = p.category_id
                LEFT JOIN inventory i ON i.product_id = p.product_id
                WHERE p.status = 'active'";
        $params = [];
        if ($selectedCat > 0) {
            $sql .= " AND p.category_id = ?";
            $params[] = $selectedCat;
        }
        $sql .= " ORDER BY p.product_name ASC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        if ($products):
            foreach ($products as $p):
                $stock = (int)($p['stock'] ?? 0);
                $expired = $p['expiration_date'] && $p['expiration_date'] < date('Y-m-d');
                $soldOut = $stock <= 0 || $expired;
        ?>
        <div class="kiosk-product" data-cat="<?= (int)$p['category_id'] ?>" data-name="<?= e($p['product_name']) ?>">
            <div class="p-img">
                <?php if ($p['image'] && file_exists('../assets/uploads/products/' . $p['image'])): ?>
                    <img src="<?= BASE_URL ?>assets/uploads/products/<?= e($p['image']) ?>" alt="<?= e($p['product_name']) ?>">
                <?php else: ?>
                    <i class="bi bi-cup-straw p-img-icon"></i>
                <?php endif; ?>
                <?php if ($soldOut): ?>
                    <span class="stock-badge badge bg-danger">Sold Out</span>
                <?php elseif ($stock <= $p['reorder_level']): ?>
                    <span class="stock-badge badge bg-warning text-dark">Low Stock</span>
                <?php endif; ?>
            </div>
            <div class="p-body">
                <div class="p-name"><?= e($p['product_name']) ?></div>
                <div class="p-cat"><?= e($p['category_name']) ?></div>
                <div class="p-price"><?= format_currency($p['price']) ?></div>
                <div class="p-stock text-muted small">
                    <?php if ($soldOut): ?>
                        <span class="text-danger fw-bold"><i class="bi bi-x-circle"></i> Sold Out</span>
                    <?php else: ?>
                        <i class="bi bi-box-seam"></i> Available: <strong><?= (int)$stock ?></strong>
                        <?php if ($stock <= $p['reorder_level']): ?>
                            <span class="badge bg-warning text-dark ms-1">Low Stock</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <button class="p-add" data-id="<?= (int)$p['product_id'] ?>" data-stock="<?= (int)$stock ?>" <?= $soldOut ? 'disabled' : '' ?>>
                    <i class="bi bi-plus-lg"></i> Add
                </button>
            </div>
        </div>
        <?php endforeach; else: ?>
        <div class="empty-state w-100">
            <i class="bi bi-inbox"></i>
            <h5>No products available</h5>
            <p>Check back soon!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/kiosk_footer.php'; ?>
</content>

