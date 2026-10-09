<?php
/**
 * Sales API - dashboard stats
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'stats':
        $period = $_GET['period'] ?? 'today';
        $dateCond = match($period) {
            'today'   => 'DATE(sale_date) = CURDATE()',
            'weekly'  => 'YEARWEEK(sale_date, 1) = YEARWEEK(CURDATE(), 1)',
            'monthly' => 'MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())',
            'yearly'  => 'YEAR(sale_date) = YEAR(CURDATE())',
            default   => 'DATE(sale_date) = CURDATE()'
        };
        $stmt = db()->query("SELECT COALESCE(SUM(total_amount),0) AS revenue, COUNT(*) AS transactions FROM sales WHERE $dateCond");
        echo json_encode($stmt->fetch());
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
