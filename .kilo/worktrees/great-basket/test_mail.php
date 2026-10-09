<?php
/**
 * TEST SCRIPT - verifies the SMTP mailer works.
 * Visit: http://localhost/kiosk/test_mail.php
 * After confirming, DELETE this file.
 */
require_once __DIR__ . '/config/mail.php';
require_once __DIR__ . '/config/mailer.php';

$result = '';
$to = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = trim($_POST['to'] ?? '');
    if ($to === '') {
        $result = 'Please enter a destination email.';
    } else {
        $otp = generate_otp();
        $out = send_otp_email($to, $otp);
        $result = $out === true
            ? "✅ OTP (<strong>$otp</strong>) sent successfully to <strong>$to</strong>! Check your inbox."
            : "❌ " . e($out);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test SMTP Mailer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:520px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h4 class="mb-3"><i class="bi bi-envelope"></i> Test SMTP Mailer</h4>
            <p class="text-muted">Enter an email to send a test OTP via <code><?= e(SMTP_HOST) ?></code>.</p>

            <?php if ($result): ?>
                <div class="alert <?= $result === 'Please enter a destination email.' ? 'alert-warning' : (strpos($result, '✅') !== false ? 'alert-success' : 'alert-danger') ?>"><?= $result ?></div>
            <?php endif; ?>

            <form method="POST">
                <label class="form-label">Destination Email</label>
                <input type="email" class="form-control mb-3" name="to" value="<?= e($to) ?>" placeholder="recipient@example.com" required>
                <button class="btn btn-primary w-100"><i class="bi bi-send"></i> Send Test OTP</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
