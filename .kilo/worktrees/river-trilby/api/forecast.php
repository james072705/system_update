<?php
/**
 * Forecast API - returns forecast data for charts
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $rows = db()->query(
            'SELECT f.*, p.product_name FROM forecasts f
             JOIN products p ON p.product_id=f.product_id
             ORDER BY f.forecast_date DESC LIMIT 30')->fetchAll();
        echo json_encode($rows);
        break;

    case 'trend':
        $rows = db()->query(
            "SELECT DATE(sale_date) d, SUM(total_amount) s
             FROM sales GROUP BY DATE(sale_date)
             ORDER BY d ASC LIMIT 30")->fetchAll();
        echo json_encode($rows);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
