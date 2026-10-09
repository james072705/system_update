<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role(['admin', 'staff']);

// Export to Excel (CSV format with .xls extension for Excel compatibility)
$type = $_GET['type'] ?? 'report';
$date = $_GET['date'] ?? date('Y-m-d');
$month = $_GET['month'] ?? date('Y-m');

function export_csv($filename, $headers, $rows) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

switch ($type) {
    case 'inventory':
        $products = db()->query("SELECT p.product_name, c.category_name, p.price, i.quantity FROM products p LEFT JOIN categories c ON c.category_id=p.category_id LEFT JOIN inventory i ON i.product_id=p.product_id ORDER BY p.product_name")->fetchAll();
        $rows = [];
        foreach ($products as $p) $rows[] = [$p['product_name'], $p['category_name'], $p['price'], $p['quantity']];
        export_csv('inventory_report.csv', ['Product','Category','Price','Stock'], $rows);
        break;

    case 'stock':
        $movements = db()->query("SELECT sm.date, p.product_name, sm.movement_type, sm.quantity, sm.remarks FROM stock_movements sm LEFT JOIN products p ON p.product_id=sm.product_id ORDER BY sm.date DESC")->fetchAll();
        $rows = [];
        foreach ($movements as $m) $rows[] = [$m['date'], $m['product_name'], $m['movement_type'], $m['quantity'], $m['remarks']];
        export_csv('stock_movement_report.csv', ['Date','Product','Type','Qty','Remarks'], $rows);
        break;

    case 'top':
        $products = db()->query("SELECT p.product_name, SUM(si.quantity) qty, SUM(si.subtotal) revenue FROM sales_items si JOIN sales s ON s.sale_id=si.sale_id JOIN products p ON p.product_id=si.product_id GROUP BY si.product_id,p.product_name ORDER BY qty DESC LIMIT 20")->fetchAll();
        $rows = [];
        foreach ($products as $i=>$p) $rows[] = [$i+1, $p['product_name'], $p['qty'], $p['revenue']];
        export_csv('top_products.csv', ['#','Product','Qty Sold','Revenue'], $rows);
        break;

    default:
        if (isset($_GET['month'])) {
            $sales = db()->prepare("SELECT * FROM sales WHERE DATE_FORMAT(sale_date,'%Y-%m')=? ORDER BY sale_date DESC");
            $sales->execute([$month]);
        } else {
            $sales = db()->prepare("SELECT * FROM sales WHERE DATE(sale_date)=? ORDER BY sale_date DESC");
            $sales->execute([$date]);
        }
        $sales = $sales->fetchAll();
        $rows = [];
        foreach ($sales as $s) $rows[] = [$s['invoice_number'], $s['sale_date'], $s['cashier'], $s['order_type'], $s['total_amount']];
        export_csv('sales_report.csv', ['Invoice','Date','Cashier','Type','Total'], $rows);
        break;
}
