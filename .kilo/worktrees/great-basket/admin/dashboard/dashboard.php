<?php
/**
 * Admin Dashboard - Main analytics overview
 */
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role(['admin', 'staff']);

// Scan alerts on dashboard load
scan_inventory_alerts();

$pdo = db();

// ---- Stats ----
$todaySales  = $pdo->query("SELECT COALESCE(SUM(total_amount),0) s FROM sales WHERE DATE(sale_date)=CURDATE()")->fetch();
$todayTrans  = $pdo->query("SELECT COUNT(*) c FROM sales WHERE DATE(sale_date)=CURDATE()")->fetch();
$weekSales   = $pdo->query("SELECT COALESCE(SUM(total_amount),0) s FROM sales WHERE YEARWEEK(sale_date,1)=YEARWEEK(CURDATE(),1)")->fetch();
$monthSales  = $pdo->query("SELECT COALESCE(SUM(total_amount),0) s FROM sales WHERE MONTH(sale_date)=MONTH(CURDATE()) AND YEAR(sale_date)=YEAR(CURDATE())")->fetch();
$totalProducts = $pdo->query("SELECT COUNT(*) c FROM products WHERE status='active'")->fetch();
$totalTrans    = $pdo->query("SELECT COUNT(*) c FROM sales")->fetch();
$lowStock     = $pdo->query("SELECT COUNT(*) c FROM inventory i JOIN products p ON p.product_id=i.product_id WHERE i.quantity <= p.reorder_level AND p.status='active'")->fetch();
$expiring     = $pdo->query("SELECT COUNT(*) c FROM products WHERE expiration_date IS NOT NULL AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetch();
$pendingOrders = $pdo->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")->fetch();

// ---- Chart data: last 7 days sales ----
$chart7 = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $row = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) s FROM sales WHERE DATE(sale_date)=?");
    $row->execute([$d]);
    $chart7['labels'][] = date('D', strtotime($d));
    $chart7['sales'][]  = (float)$row->fetch()['s'];
}

// ---- Top selling products ----
$topProducts = $pdo->query(
    "SELECT p.product_name, SUM(si.quantity) qty, SUM(si.subtotal) revenue
     FROM sales_items si JOIN products p ON p.product_id=si.product_id
     GROUP BY si.product_id, p.product_name
     ORDER BY qty DESC LIMIT 5")->fetchAll();

// ---- Inventory low stock list ----
$lowStockList = $pdo->query(
    "SELECT p.product_name, i.quantity, p.reorder_level
     FROM inventory i JOIN products p ON p.product_id=i.product_id
     WHERE i.quantity <= p.reorder_level AND p.status='active'
     ORDER BY i.quantity ASC LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-grad-primary text-white"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-value"><?= format_currency($todaySales['s']) ?></div>
            <div class="stat-label">Today's Sales</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-grad-success text-white"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-value"><?= format_currency($weekSales['s']) ?></div>
            <div class="stat-label">Weekly Sales</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-grad-warning text-white"><i class="bi bi-calendar-week"></i></div>
            <div class="stat-value"><?= format_currency($monthSales['s']) ?></div>
            <div class="stat-label">Monthly Sales</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-grad-info text-white"><i class="bi bi-receipt"></i></div>
            <div class="stat-value"><?= format_number($todayTrans['c']) ?></div>
            <div class="stat-label">Transactions Today</div>
        </div>
    </div>
</div>

<!-- Second row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-grad-purple text-white"><i class="bi bi-box-seam"></i></div>
            <div class="stat-value"><?= format_number($totalProducts['c']) ?></div>
            <div class="stat-label">Active Products</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-grad-warning text-white"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-value"><?= format_number($lowStock['c']) ?></div>
            <div class="stat-label">Low Stock Items</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-grad-pink text-white"><i class="bi bi-calendar-x"></i></div>
            <div class="stat-value"><?= format_number($expiring['c']) ?></div>
            <div class="stat-label">Expiring Soon</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-grad-info text-white"><i class="bi bi-egg-fried"></i></div>
            <div class="stat-value"><?= format_number($pendingOrders['c']) ?></div>
            <div class="stat-label">Pending Orders</div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-graph-up me-2"></i>Sales Trend (Last 7 Days)</div>
            <div class="card-body">
                <div class="chart-box">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-trophy me-2"></i>Top Selling Products</div>
            <div class="card-body">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Alerts</div>
            <div class="card-body">
                <?php if (empty($lowStockList)): ?>
                    <div class="empty-state"><i class="bi bi-check2-circle"></i><p class="mb-0">All products are well stocked.</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Product</th><th class="text-center">Stock</th><th class="text-center">Reorder</th></tr></thead>
                            <tbody>
                            <?php foreach ($lowStockList as $l): ?>
                                <tr>
                                    <td><?= e($l['product_name']) ?></td>
                                    <td class="text-center"><span class="badge bg-soft-danger"><?= (int)$l['quantity'] ?></span></td>
                                    <td class="text-center"><?= (int)$l['reorder_level'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="<?= BASE_URL ?>admin/inventory/low_stock.php" class="btn btn-sm btn-soft">View All</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-graph-up-arrow me-2"></i>Forecast Preview</div>
            <div class="card-body">
                <?php
                $forecasts = $pdo->query(
                    "SELECT f.*, p.product_name FROM forecasts f
                     JOIN products p ON p.product_id=f.product_id
                     ORDER BY f.forecast_date DESC LIMIT 5")->fetchAll();
                if (empty($forecasts)): ?>
                    <div class="empty-state">
                        <i class="bi bi-robot"></i>
                        <p class="mb-1">No forecast data yet.</p>
                        <a href="<?= BASE_URL ?>admin/forecast/forecast.php" class="btn btn-sm btn-primary">Run Forecast</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Product</th><th>Forecast Date</th><th class="text-end">Predicted Sales</th><th class="text-end">Reorder Qty</th></tr></thead>
                            <tbody>
                            <?php foreach ($forecasts as $f): ?>
                                <tr>
                                    <td><?= e($f['product_name']) ?></td>
                                    <td><?= format_date($f['forecast_date']) ?></td>
                                    <td class="text-end"><?= format_number($f['predicted_sales']) ?></td>
                                    <td class="text-end"><span class="badge bg-soft-success"><?= (int)$f['reorder_quantity'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const salesLabels = <?= json_encode($chart7['labels']) ?>;
const salesData = <?= json_encode($chart7['sales']) ?>;
const topLabels = <?= json_encode(array_column($topProducts, 'product_name')) ?>;
const topData = <?= json_encode(array_map('intval', array_column($topProducts, 'qty'))) ?>;

document.addEventListener('DOMContentLoaded', () => {
    createLineChart('salesChart', salesLabels, [{
        label: 'Sales (₱)',
        data: salesData,
        borderColor: '#e63946',
        backgroundColor: 'rgba(230,57,70,.1)',
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#e63946'
    }]);

    createDoughnutChart('topProductsChart', topLabels, topData);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
