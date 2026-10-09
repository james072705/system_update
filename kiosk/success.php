<?php
/**
 * Kiosk order success page
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
header('Location: ' . BASE_URL . 'kiosk/print_receipt.php');
exit;
