<?php
/**
 * Shared header for admin/POS pages
 * Requires: $pageTitle, $currentPage (for active nav)
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_login();
$user = current_user();

// Auto-deactivate any products expiring within 5 days (run on each admin page load)
auto_deactivate_expiring_products();

$notifCount = unread_notifications_count();
$pageTitle = $pageTitle ?? 'Dashboard';
$currentPage = $currentPage ?? '';
require_once __DIR__ . '/alerts.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" rel="preload">
<link href="<?= BASE_URL ?>assets/css/style.css?v=3.0" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/dashboard.css?v=3.0" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/darkmode.css?v=3.0" rel="stylesheet">
</head>
<body>
<div class="app-wrapper">

    <!-- Sidebar backdrop -->
    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

    <!-- ============ SIDEBAR ============ -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="logo"><i class="bi bi-cup-straw"></i></div>
            <div class="brand-text"><?= e(SITE_NAME) ?>
                <small><?= e(APP_TAGLINE) ?></small>
            </div>
        </div>

        <nav class="nav flex-column mt-2">
            <div class="nav-section">Main</div>
            <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/dashboard/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <?php if (in_array($user['role'], ['admin', 'staff'])): ?>
            <a class="nav-link <?= $currentPage === 'pos' ? 'active' : '' ?>" href="<?= BASE_URL ?>pos/index.php">
                <i class="bi bi-cart3"></i> Point of Sale
            </a>
            <?php endif; ?>

            <?php if (in_array($user['role'], ['admin', 'staff'])): ?>
            <a class="nav-link <?= $currentPage === 'kiosk' ? 'active' : '' ?>" href="<?= BASE_URL ?>kiosk/index.php" target="_blank">
                <i class="bi bi-touch"></i> Customer Kiosk
                <span class="badge bg-primary">Open</span>
            </a>
            <?php endif; ?>

            <?php if ($user['role'] === 'admin'): ?>
            <div class="nav-section">Management</div>
            <a class="nav-link <?= $currentPage === 'payments' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/payments/settle.php">
                <i class="bi bi-cash-coin"></i> Payment Settlement
            </a>
            <a class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/categories/list.php">
                <i class="bi bi-tags"></i> Categories
            </a>
            <a class="nav-link <?= $currentPage === 'products' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/products/list.php">
                <i class="bi bi-box-seam"></i> Products
            </a>
            <a class="nav-link <?= $currentPage === 'inventory' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/inventory/inventory.php">
                <i class="bi bi-clipboard-data"></i> Inventory
            </a>
            <a class="nav-link <?= $currentPage === 'staff' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/staff/accounts.php">
                <i class="bi bi-people"></i> Staff Accounts
            </a>

            <div class="nav-section">Insights</div>
            <a class="nav-link <?= $currentPage === 'forecast' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/forecast/forecast.php">
                <i class="bi bi-graph-up-arrow"></i> Sales Forecast
            </a>
            <a class="nav-link <?= $currentPage === 'forecast_history' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/forecast/history.php">
                <i class="bi bi-clock-history"></i> Forecast History
            </a>
            <a class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/reports/daily.php">
                <i class="bi bi-file-earmark-bar-graph"></i> Reports
            </a>
            <a class="nav-link <?= $currentPage === 'sales_history' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/reports/sales_history.php">
                <i class="bi bi-clock-history"></i> Sales History
            </a>
            <a class="nav-link <?= $currentPage === 'logs' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/audit_logs/logs.php">
                <i class="bi bi-journal-text"></i> Audit Logs
            </a>
            <?php endif; ?>

            <?php if ($user['role'] === 'kitchen'): ?>
            <div class="nav-section">Kitchen</div>
            <a class="nav-link <?= $currentPage === 'kitchen' ? 'active' : '' ?>" href="<?= BASE_URL ?>kitchen/index.php">
                <i class="bi bi-egg-fried"></i> Order Queue
            </a>
            <?php endif; ?>

            <div class="nav-section">System</div>
            <a class="nav-link" href="<?= BASE_URL ?>authentication/change_password.php">
                <i class="bi bi-key"></i> Change Password
            </a>
            <a class="nav-link text-danger" href="<?= BASE_URL ?>authentication/delete_account.php">
                <i class="bi bi-person-x"></i> Delete Account
            </a>
            <a class="nav-link text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
            <form id="logout-form" action="<?= BASE_URL ?>authentication/logout.php" method="POST" class="d-none"></form>
        </nav>

        <div class="sidebar-footer">
            <div class="d-flex align-items-center justify-content-between">
                <span><i class="bi bi-moon-stars"></i> Theme</span>
                <button class="btn btn-sm btn-soft theme-toggle"><i class="bi bi-moon-stars-fill"></i></button>
            </div>
            <div class="mt-2 text-muted small"><?= e(SITE_NAME) ?> v1.0</div>
        </div>
    </aside>

    <!-- ============ MAIN CONTENT ============ -->
    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <button class="icon-btn d-lg-none" id="sidebar-toggle"><i class="bi bi-list"></i></button>
            <div>
                <div class="page-title"><?= e($pageTitle) ?></div>
                <nav class="breadcrumb"><span class="breadcrumb-item"><?= e(SITE_NAME) ?></span><span class="breadcrumb-item active"><?= e($pageTitle) ?></span></nav>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <?php if (in_array($user['role'], ['admin', 'staff'])): ?>
                <a href="<?= BASE_URL ?>kiosk/index.php" target="_blank" class="btn btn-sm btn-soft d-none d-md-inline-flex"><i class="bi bi-touch"></i> Kiosk</a>
                <?php endif; ?>

                <!-- Notifications -->
                <div class="dropdown">
                    <button class="icon-btn" data-bs-toggle="dropdown" aria-expanded="false" id="notif-btn">
                        <i class="bi bi-bell"></i>
                        <span class="dot" id="notif-dot" style="display:<?= $notifCount > 0 ? 'block' : 'none' ?>"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notif-menu p-0">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <strong>Notifications</strong>
                            <a href="<?= BASE_URL ?>admin/notifications/notifications.php" class="small">View all</a>
                        </div>
<?php
                        $notifs = db()->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 8")->fetchAll();
                        if ($notifs):
                            foreach ($notifs as $n):
                                $icon = match($n['type']) {
                                    'low_stock' => 'bi-exclamation-triangle',
                                    'expiry'   => 'bi-calendar-x',
                                    'expired'  => 'bi-x-circle',
                                    'forecast' => 'bi-graph-up',
                                    default    => 'bi-info-circle'
                                };
                                $bg = match($n['type']) {
                                    'low_stock' => 'bg-warning text-dark',
                                    'expiry'   => 'bg-info text-white',
                                    'expired'  => 'bg-danger text-white',
                                    'forecast' => 'bg-success text-white',
                                    default    => 'bg-primary text-white'
                                };
                        ?>
                        <div class="notif-item">
                            <div class="notif-icon <?= $bg ?>"><i class="bi <?= $icon ?>"></i></div>
                            <div>
                                <div class="small"><?= e($n['message']) ?></div>
                                <div class="notif-time"><i class="bi bi-clock"></i> <?= time_ago($n['created_at']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="empty-state py-4"><i class="bi bi-bell-slash"></i><p class="mb-0">No notifications</p></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Theme toggle -->
                <button class="icon-btn theme-toggle"><i class="bi bi-moon-stars-fill"></i></button>

                <!-- User -->
                <div class="dropdown">
                    <div class="user-chip" data-bs-toggle="dropdown">
                        <div class="avatar"><?= strtoupper(substr($user['fullname'], 0, 1)) ?></div>
                        <div class="d-none d-md-block">
                            <div class="fw-bold small lh-1"><?= e($user['fullname']) ?></div>
                            <div class="role-badge"><?= ucfirst($user['role']) ?></div>
                        </div>
                        <i class="bi bi-chevron-down small text-muted"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-header">Signed in as <strong><?= e($user['username']) ?></strong></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>authentication/change_password.php"><i class="bi bi-key me-2"></i>Change Password</a></li>
                        <li><a class="dropdown-item text-danger" href="#"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page content -->
        <main class="p-3 p-lg-4">
            <?php render_flash(); ?>
