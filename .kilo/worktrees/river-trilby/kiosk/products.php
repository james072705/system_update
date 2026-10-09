<?php
/**
 * Kiosk product listing - same experience as the main menu
 * (redirects to main kiosk page)
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
header('Location: ' . BASE_URL . 'kiosk/index.php');
exit;
