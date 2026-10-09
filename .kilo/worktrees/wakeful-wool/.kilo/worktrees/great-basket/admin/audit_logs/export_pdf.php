<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../libs/fpdf.php';
require_role('admin');

$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$where = '1=1';
$params = [];

if ($dateFrom !== '') {
    $where .= ' AND DATE(log_date) >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where .= ' AND DATE(log_date) <= :date_to';
    $params[':date_to'] = $dateTo;
}

$logs = db()->prepare(
    'SELECT al.*, u.username FROM audit_logs al
     LEFT JOIN users u ON u.user_id = al.user_id
     WHERE ' . $where . '
     ORDER BY al.log_date DESC LIMIT 500'
);
$logs->execute($params);
$logs = $logs->fetchAll();

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, SITE_NAME, 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Audit Logs' . ($dateFrom !== '' ? ' | From: ' . $dateFrom : '') . ($dateTo !== '' ? ' To: ' . $dateTo : ''), 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(28, 8, 'Date', 1, 0, 'C', true);
$pdf->Cell(22, 8, 'User', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Action', 1, 0, 'C', true);
$pdf->Cell(120, 8, 'Details', 1, 0, 'C', true);
$pdf->Cell(28, 8, 'IP', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 9);
foreach ($logs as $l) {
    $pdf->Cell(28, 7, format_datetime($l['log_date']), 1);
    $pdf->Cell(22, 7, $l['username'] ?? 'System', 1);
    $pdf->Cell(50, 7, $l['action'], 1);
    $details = substr($l['details'] ?? '', 0, 90);
    $pdf->Cell(120, 7, $details, 1);
    $pdf->Cell(28, 7, $l['ip_address'], 1, 1);
}

$filename = 'audit_logs' . ($dateFrom !== '' ? '_' . $dateFrom : '') . ($dateTo !== '' ? '_to_' . $dateTo : '') . '.pdf';
$pdf->Output('D', $filename);
exit;
