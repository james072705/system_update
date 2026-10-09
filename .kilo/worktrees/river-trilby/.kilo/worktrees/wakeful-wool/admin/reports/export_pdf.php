<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../libs/fpdf.php';
require_role(['admin', 'staff']);

$type = $_GET['type'] ?? 'report';
$date = $_GET['date'] ?? date('Y-m-d');
$week = $_GET['week'] ?? date('Y-m-d');
$month = $_GET['month'] ?? date('Y-m');
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, SITE_NAME, 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, APP_TAGLINE . ' | Caloocan City, Philippines', 0, 1, 'C');
$pdf->Ln(2);

switch ($type) {
    case 'inventory':
        $products = db()->query("SELECT p.*, c.category_name, i.quantity AS stock FROM products p LEFT JOIN categories c ON c.category_id=p.category_id LEFT JOIN inventory i ON i.product_id=p.product_id ORDER BY p.product_name")->fetchAll();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Inventory Report', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(60, 7, 'Product', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Category', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Price', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Stock', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Value', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 9);
        foreach ($products as $p) {
            $pdf->Cell(60, 7, $p['product_name'], 1);
            $pdf->Cell(40, 7, $p['category_name'], 1);
            $pdf->Cell(25, 7, format_currency($p['price']), 1, 0, 'R');
            $pdf->Cell(25, 7, (string)(int)$p['stock'], 1, 0, 'R');
            $pdf->Cell(35, 7, format_currency($p['price'] * $p['stock']), 1, 1, 'R');
        }
        break;

    case 'stock':
        $movements = db()->query("SELECT sm.*, p.product_name FROM stock_movements sm LEFT JOIN products p ON p.product_id=sm.product_id ORDER BY sm.date DESC")->fetchAll();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Stock Movement Report', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(35, 7, 'Date', 1, 0, 'C', true);
        $pdf->Cell(50, 7, 'Product', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Type', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Qty', 1, 0, 'C', true);
        $pdf->Cell(55, 7, 'Remarks', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 9);
        foreach ($movements as $m) {
            $pdf->Cell(35, 7, format_datetime($m['date']), 1);
            $pdf->Cell(50, 7, $m['product_name'], 1);
            $pdf->Cell(25, 7, ucfirst($m['movement_type']), 1);
            $pdf->Cell(25, 7, (string)(int)$m['quantity'], 1, 0, 'R');
            $pdf->Cell(55, 7, $m['remarks'], 1, 1);
        }
        break;

    case 'expiry':
        $products = db()->query("SELECT p.*, c.category_name, i.quantity AS stock FROM products p LEFT JOIN categories c ON c.category_id=p.category_id LEFT JOIN inventory i ON i.product_id=p.product_id WHERE p.expiration_date IS NOT NULL ORDER BY p.expiration_date")->fetchAll();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Expiry Report', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(70, 7, 'Product', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Category', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Stock', 1, 0, 'C', true);
        $pdf->Cell(50, 7, 'Expiration', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 9);
        foreach ($products as $p) {
            $pdf->Cell(70, 7, $p['product_name'], 1);
            $pdf->Cell(40, 7, $p['category_name'], 1);
            $pdf->Cell(30, 7, (string)(int)$p['stock'], 1, 0, 'R');
            $pdf->Cell(50, 7, format_date($p['expiration_date']), 1, 1);
        }
        break;

    case 'top':
        $products = db()->query("SELECT p.product_name, SUM(si.quantity) qty, SUM(si.subtotal) revenue FROM sales_items si JOIN sales s ON s.sale_id=si.sale_id JOIN products p ON p.product_id=si.product_id GROUP BY si.product_id, p.product_name ORDER BY qty DESC LIMIT 20")->fetchAll();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Top Selling Products', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(10, 7, '#', 1, 0, 'C', true);
        $pdf->Cell(80, 7, 'Product', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Qty Sold', 1, 0, 'C', true);
        $pdf->Cell(60, 7, 'Revenue', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 9);
        foreach ($products as $i => $p) {
            $pdf->Cell(10, 7, (string)($i + 1), 1, 0, 'C');
            $pdf->Cell(80, 7, $p['product_name'], 1);
            $pdf->Cell(40, 7, (string)(int)$p['qty'], 1, 0, 'R');
            $pdf->Cell(60, 7, format_currency($p['revenue']), 1, 1, 'R');
        }
        break;

    case 'forecast':
        $forecasts = db()->query('SELECT f.*, p.product_name FROM forecasts f JOIN products p ON p.product_id=f.product_id ORDER BY f.forecast_date DESC')->fetchAll();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Sales Forecast Report', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(70, 7, 'Product', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Forecast Date', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Predicted Sales', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Reorder Qty', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 9);
        foreach ($forecasts as $f) {
            $pdf->Cell(70, 7, $f['product_name'], 1);
            $pdf->Cell(40, 7, format_date($f['forecast_date']), 1);
            $pdf->Cell(40, 7, format_number($f['predicted_sales']), 1, 0, 'R');
            $pdf->Cell(40, 7, (string)(int)$f['reorder_quantity'], 1, 1, 'R');
        }
        break;

    case 'yearly':
        $year = (int)($_GET['year'] ?? date('Y'));
        $sales = db()->prepare("SELECT * FROM sales WHERE YEAR(sale_date) = ? ORDER BY sale_date DESC");
        $sales->execute([$year]);
        $sales = $sales->fetchAll();
        $total = array_sum(array_column($sales, 'total_amount'));
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Yearly Sales Report - ' . $year, 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(30, 7, 'Invoice', 1, 0, 'C', true);
        $pdf->Cell(45, 7, 'Date', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Cashier', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Type', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Total', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 9);
        foreach ($sales as $s) {
            $pdf->Cell(30, 7, $s['invoice_number'], 1);
            $pdf->Cell(45, 7, format_datetime($s['sale_date']), 1);
            $pdf->Cell(40, 7, $s['cashier'], 1);
            $pdf->Cell(40, 7, ucfirst(str_replace('_', ' ', $s['order_type'])), 1);
            $pdf->Cell(35, 7, format_currency($s['total_amount']), 1, 1, 'R');
        }
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(155, 7, 'TOTAL', 1, 0, 'R', true);
        $pdf->Cell(35, 7, format_currency($total), 1, 1, 'R', true);
        break;

    default:
        if (isset($_GET['month'])) {
            $sales = db()->prepare("SELECT * FROM sales WHERE DATE_FORMAT(sale_date,'%Y-%m')=? ORDER BY sale_date DESC");
            $sales->execute([$month]);
            $title = 'Monthly Sales Report - ' . date('F Y', strtotime($month . '-01'));
        } elseif (isset($_GET['from']) && isset($_GET['to'])) {
            $sales = db()->prepare("SELECT * FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? ORDER BY sale_date DESC");
            $sales->execute([$from, $to]);
            $title = 'Sales Report - ' . $from . ' to ' . $to;
        } elseif (isset($_GET['week'])) {
            $ws = date('Y-m-d', strtotime('monday this week', strtotime($week)));
            $we = date('Y-m-d', strtotime('sunday this week', strtotime($week)));
            $sales = db()->prepare("SELECT * FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? ORDER BY sale_date DESC");
            $sales->execute([$ws, $we]);
            $title = 'Weekly Sales Report - ' . format_date($ws) . ' to ' . format_date($we);
        } else {
            $sales = db()->prepare("SELECT * FROM sales WHERE DATE(sale_date)=? ORDER BY sale_date DESC");
            $sales->execute([$date]);
            $title = 'Daily Sales Report - ' . format_date($date);
        }
        $sales = $sales->fetchAll();
        $total = array_sum(array_column($sales, 'total_amount'));
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, $title, 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(35, 7, 'Invoice', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Date', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Cashier', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Type', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Total', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 9);
        foreach ($sales as $s) {
            $pdf->Cell(35, 7, $s['invoice_number'], 1);
            $pdf->Cell(40, 7, format_datetime($s['sale_date']), 1);
            $pdf->Cell(30, 7, $s['cashier'], 1);
            $pdf->Cell(30, 7, ucfirst(str_replace('_', ' ', $s['order_type'])), 1);
            $pdf->Cell(40, 7, format_currency($s['total_amount']), 1, 1, 'R');
        }
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(155, 7, 'TOTAL', 1, 0, 'R', true);
        $pdf->Cell(35, 7, format_currency($total), 1, 1, 'R', true);
        break;
}

$filename = ($type === 'report' ? 'sales_report' : $type) . '.pdf';
if (isset($_GET['month'])) {
    $filename = 'monthly_sales_' . $month . '.pdf';
} elseif (isset($_GET['from']) && isset($_GET['to'])) {
    $filename = 'sales_report_' . $from . '_to_' . $to . '.pdf';
} elseif (isset($_GET['week'])) {
    $filename = 'weekly_sales_' . $week . '.pdf';
} elseif (isset($_GET['year'])) {
    $filename = 'yearly_sales_' . $year . '.pdf';
}
$pdf->Output('D', $filename);
exit;
