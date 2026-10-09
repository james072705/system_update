<?php
/**
 * Kiosk - Receipt page redirect
 * Redirects to the isolated print page for reliable thermal printing.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$order = $_SESSION['last_order'] ?? null;
if (!$order) {
    header('Location: ' . BASE_URL . 'kiosk/index.php');
    exit;
}

header('Location: ' . BASE_URL . 'kiosk/print_receipt.php');
exit;
