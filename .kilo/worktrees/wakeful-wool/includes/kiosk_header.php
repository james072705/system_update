<?php
/**
 * Kiosk header (public facing - no login needed)
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
$pageTitle = $pageTitle ?? "Ferry's Restobar";
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>assets/css/style.css?v=3.0" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/kiosk.css?v=3.1" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/darkmode.css?v=3.0" rel="stylesheet">
</head>
<body class="kiosk-mode">
