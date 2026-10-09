<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_role(['admin', 'staff']);

$pageTitle = 'Point of Sale';
$currentPage = 'pos';

$categories = db()->query('SELECT * FROM categories ORDER BY category_name')->fetchAll();
$products = db()->query(
    'SELECT p.*, c.category_name, i.quantity AS stock
     FROM products p LEFT JOIN categories c ON c.category_id=p.category_id
     LEFT JOIN inventory i ON i.product_id=p.product_id
     WHERE p.status="active" ORDER BY p.product_name')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <!-- Product list -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-box-seam me-2"></i>Products</span>
                <div class="d-flex gap-2">
                    <input type="text" id="pos-search" class="form-control form-control-sm" placeholder="Search product...">
                    <select class="form-select form-select-sm" id="pos-cat" style="width:auto">
                        <option value="0">All</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['category_id'] ?>"><?= e($c['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2 pos-products">
                    <?php foreach ($products as $p):
                        $stock = (int)$p['stock'];
                        $soldOut = $stock <= 0 || ($p['expiration_date'] && $p['expiration_date'] < date('Y-m-d'));
                    ?>
                    <div class="col-6 col-md-4 col-xl-3 pos-product" data-cat="<?= (int)$p['category_id'] ?>">
                        <div class="card kiosk-product h-100">
                            <div class="p-img">
                                <?php if ($p['image'] && file_exists(__DIR__ . '/../assets/uploads/products/' . $p['image'])): ?>
                                    <img src="<?= BASE_URL ?>assets/uploads/products/<?= e($p['image']) ?>" alt="">
                                <?php else: ?>
                                    <i class="bi bi-cup-straw p-img-icon"></i>
                                <?php endif; ?>
                            </div>
                            <div class="p-body">
                                <div class="p-name"><?= e($p['product_name']) ?></div>
                                <div class="p-cat"><?= e($p['category_name']) ?><span class="float-end text-muted">Stock: <?= $stock ?></span></div>
                                <div class="p-price"><?= format_currency($p['price']) ?></div>
                                <button class="p-add" data-id="<?= (int)$p['product_id'] ?>" <?= $soldOut ? 'disabled' : '' ?>>
                                    <i class="bi bi-plus-lg"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart -->
    <div class="col-lg-4">
        <div class="card sticky-top" style="top:80px">
            <div class="card-header"><i class="bi bi-cart3 me-2"></i>Current Sale
                <span class="badge bg-primary ms-1 cart-count"><?= cart_count('pos') ?></span>
            </div>
            <div class="card-body">
                <div class="pos-cart" style="max-height:380px;overflow-y:auto">
                    <?php $items = cart_items('pos'); ?>
                    <?php if (empty($items)): ?>
                        <div class="empty-state py-4"><i class="bi bi-cart4"></i><p class="mb-0">Click products to add to cart</p></div>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <div class="fw-bold small"><?= e($item['product_name']) ?></div>
                                    <div class="qty-control mt-1">
                                        <button class="cart-update" data-id="<?= (int)$item['product_id'] ?>" data-qty="<?= (int)$item['cart_qty']-1 ?>">−</button>
                                        <span><?= (int)$item['cart_qty'] ?></span>
                                        <button class="cart-update" data-id="<?= (int)$item['product_id'] ?>" data-qty="<?= (int)$item['cart_qty']+1 ?>">+</button>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold"><?= format_currency($item['subtotal']) ?></div>
                                    <button class="btn btn-sm btn-link text-danger p-0 cart-remove" data-id="<?= (int)$item['product_id'] ?>"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold">Total</span>
                    <span class="fs-4 fw-bold text-grad-primary" id="pos-total"><?= format_currency(cart_total('pos')) ?></span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-light flex-fill" id="pos-clear"><i class="bi bi-trash"></i> Clear</button>
                    <a href="<?= BASE_URL ?>pos/payment.php" class="btn btn-success flex-fill <?= empty($items) ? 'disabled' : '' ?>">
                        <i class="bi bi-check-lg"></i> Checkout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// POS search & category filter
const posSearch = document.getElementById('pos-search');
const posCat = document.getElementById('pos-cat');
posSearch.addEventListener('input', filterPos);
posCat.addEventListener('change', filterPos);

function filterPos() {
    const q = posSearch.value.toLowerCase();
    const cat = posCat.value;
    document.querySelectorAll('.pos-product').forEach(el => {
        const name = el.querySelector('.p-name').textContent.toLowerCase();
        const matchesCat = cat === '0' || el.dataset.cat === cat;
        const matchesName = name.includes(q);
        el.style.display = (matchesCat && matchesName) ? '' : 'none';
    });
}

// Clear cart
document.getElementById('pos-clear').addEventListener('click', () => {
    if (confirm('Clear the current cart?')) {
        fetch(`${BASE_URL}api/cart.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=clear&cart=pos'
        }).then(r => r.json()).then(() => location.reload());
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
