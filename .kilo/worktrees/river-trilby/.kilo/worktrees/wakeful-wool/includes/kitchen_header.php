<?php
/**
 * Kitchen header - requires login (kitchen role)
 * Manages ONLY customer orders coming from the kiosk.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_login();
require_role('kitchen');
$user = current_user();

$pageTitle = $pageTitle ?? 'Kitchen Order Management';
$activeTab = $_GET['tab'] ?? 'pending';

// Live order counts (only PAID orders - unpaid orders stay hidden until admin settles)
$counts = [
    'pending'   => (int)db()->query("SELECT COUNT(*) c FROM orders WHERE status='pending' AND payment_status='paid'")->fetch()['c'],
    'preparing' => (int)db()->query("SELECT COUNT(*) c FROM orders WHERE status='preparing' AND payment_status='paid'")->fetch()['c'],
    'ready'     => (int)db()->query("SELECT COUNT(*) c FROM orders WHERE status='ready' AND payment_status='paid'")->fetch()['c'],
    'completed' => (int)db()->query("SELECT COUNT(*) c FROM orders WHERE status='completed' AND payment_status='paid'")->fetch()['c'],
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?> Kitchen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>assets/css/style.css?v=3.0" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/dashboard.css?v=3.0" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/darkmode.css?v=3.0" rel="stylesheet">
    <style>
        body { background: #1a1f2b; }
        .kitchen-topbar {
            background: linear-gradient(135deg, #e63946, #c1121f);
            color: #fff; padding: .9rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 4px 16px rgba(0,0,0,.3);
        }
        .kitchen-topbar .logo { width: 44px; height: 44px; border-radius: 12px; background: rgba(255,255,255,.2); display: grid; place-items: center; font-size: 1.4rem; }
        .kitchen-topbar h1 { font-size: 1.3rem; margin: 0; color: #fff; }
        .kitchen-topbar small { opacity: .85; }
        .kitchen-tabs {
            display: flex; gap: .5rem; background: #232a3a; padding: .8rem 1.5rem;
            border-bottom: 1px solid #2c3548; overflow-x: auto;
        }
        .kitchen-tabs .tab {
            flex-shrink: 0; text-decoration: none; color: #9aa7b8;
            border: 2px solid #2c3548; border-radius: 50px; padding: .5rem 1.1rem;
            font-weight: 700; display: flex; align-items: center; gap: .5rem; transition: all .2s;
        }
        .kitchen-tabs .tab:hover { border-color: #e63946; color: #fff; }
        .kitchen-tabs .tab.active { background: #e63946; border-color: #e63946; color: #fff; }
        .kitchen-tabs .tab .count {
            background: rgba(255,255,255,.2); border-radius: 50px; min-width: 22px; height: 22px;
            display: grid; place-items: center; font-size: .75rem; padding: 0 .4rem;
        }
        .kitchen-tabs .tab.active .count { background: rgba(255,255,255,.3); }
        .kitchen-content { padding: 1.5rem; }
        .k-order-card {
            background: #232a3a; border: 1px solid #2c3548; border-radius: 16px;
            overflow: hidden; transition: transform .2s, box-shadow .2s;
        }
        .k-order-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.3); }
        .k-order-card .k-head {
            background: #2a3345; padding: .75rem 1rem;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #2c3548;
        }
        .k-order-card .k-head h6 { margin: 0; color: #e8edf5; font-weight: 700; }
        .k-order-card .k-body { padding: 1rem; }
        .k-order-card .k-item { display: flex; justify-content: space-between; padding: .4rem 0; border-bottom: 1px dashed #2c3548; color: #c3cdd9; }
        .k-order-card .k-total { display: flex; justify-content: space-between; font-weight: 800; color: #e8edf5; padding-top: .6rem; }
        .k-order-card .k-actions { display: flex; gap: .5rem; padding: 0 1rem 1rem; }
        .k-order-card .k-time { font-size: .75rem; color: #9aa7b8; }
        .badge-k { padding: .35em .7em; border-radius: 50px; font-weight: 700; font-size: .75rem; }
        .st-pending { background: #f4a261; color: #1a1f2b; }
        .st-preparing { background: #457b9d; color: #fff; }
        .st-ready { background: #2a9d8f; color: #fff; }
        .st-completed { background: #6c757d; color: #fff; }
        .st-cancelled { background: #e76f51; color: #fff; }
        .empty-state { text-align: center; padding: 3rem 1rem; color: #9aa7b8; }
        .empty-state i { font-size: 3rem; opacity: .4; }
        .k-detail-item { background: #232a3a; border: 1px solid #2c3548; border-radius: 12px; padding: .8rem; }
        .k-detail-item .label { color: #9aa7b8; font-size: .8rem; }
        .k-detail-item .value { color: #e8edf5; font-weight: 700; }
        .table { color: #c3cdd9; }
        .table thead th { background: #2a3345; color: #9aa7b8; border-color: #2c3548; }
        .table tbody td { border-color: #2c3548; }
        .table-hover tbody tr:hover { background: #2a3345; }
::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: #1a1f2b; }
        ::-webkit-scrollbar-thumb { background: #2c3548; border-radius: 10px; }
        @media (max-width: 767.98px) {
            .kitchen-topbar { flex-wrap: wrap; gap: .6rem; padding: .7rem 1rem; }
            .kitchen-topbar h1 { font-size: 1.05rem; }
            .kitchen-tabs { padding: .6rem 1rem; }
            .kitchen-content { padding: .8rem; }
            .k-order-card .k-actions { flex-wrap: wrap; }
            .k-order-card .k-actions .btn { flex: 1 1 auto; }
        }
    </style>
</head>
<body>
<div class="kitchen-topbar">
    <div class="d-flex align-items-center gap-3">
        <div class="logo"><i class="bi bi-egg-fried"></i></div>
        <div>
            <h1><i class="bi bi-fire me-1"></i>Kitchen - <?= e(SITE_NAME) ?></h1>
            <small>Customer Order Management</small>
        </div>
    </div>
<div class="d-flex align-items-center gap-2">
        <span class="d-none d-md-inline-flex align-items-center gap-2 me-1" style="background:rgba(255,255,255,.15);padding:.3rem .8rem;border-radius:50px;font-size:.8rem;">
            <i class="bi bi-person"></i> <?= e($user['fullname'] ?? 'Kitchen') ?>
        </span>
        <button class="btn btn-sm btn-light" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        <button class="btn btn-sm btn-light theme-toggle"><i class="bi bi-moon-stars-fill"></i></button>
        <a href="<?= BASE_URL ?>authentication/logout.php" class="btn btn-sm btn-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</div>

<div class="kitchen-tabs">
    <a class="tab <?= $activeTab === 'pending' ? 'active' : '' ?>" href="?tab=pending">
        <i class="bi bi-hourglass-split"></i> Pending <span class="count"><?= $counts['pending'] ?></span>
    </a>
    <a class="tab <?= $activeTab === 'preparing' ? 'active' : '' ?>" href="?tab=preparing">
        <i class="bi bi-fire"></i> Preparing <span class="count"><?= $counts['preparing'] ?></span>
    </a>
    <a class="tab <?= $activeTab === 'ready' ? 'active' : '' ?>" href="?tab=ready">
        <i class="bi bi-bell"></i> Ready to Serve <span class="count"><?= $counts['ready'] ?></span>
    </a>
    <a class="tab <?= $activeTab === 'completed' ? 'active' : '' ?>" href="?tab=completed">
        <i class="bi bi-check2-circle"></i> Completed <span class="count"><?= $counts['completed'] ?></span>
    </a>
</div>

<div class="kitchen-content">
</content>
