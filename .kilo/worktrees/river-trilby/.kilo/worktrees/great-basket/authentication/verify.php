<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/mailer.php';

// Redirect if already fully logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Get the pending user id from session (set during registration)
$pendingId = $_SESSION['pending_registration'] ?? null;
$emailFromUrl = trim($_GET['email'] ?? '');

// If no pending registration, try to find an unverified user by email from URL
if (!$pendingId && $emailFromUrl) {
    $stmt = db()->prepare('SELECT user_id FROM users WHERE email = ? AND is_verified = 0 LIMIT 1');
    $stmt->execute([$emailFromUrl]);
    if ($row = $stmt->fetch()) {
        $_SESSION['pending_registration'] = (int)$row['user_id'];
        $pendingId = (int)$row['user_id'];
    }
}

// If still no pending user, redirect to login
if (!$pendingId) {
    header('Location: ' . BASE_URL . 'authentication/login.php');
    exit;
}

// Load the pending user
$stmt = db()->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
$stmt->execute([$pendingId]);
$pendingUser = $stmt->fetch();

// Account may already be verified
if ($pendingUser && (int)$pendingUser['is_verified'] === 1) {
    unset($_SESSION['pending_registration']);
    $_SESSION['success'] = 'Your account is already verified. You can now log in.';
    header('Location: ' . BASE_URL . 'authentication/login.php');
    exit;
}

$message = '';
$error   = '';
$otpSent = true;

// Show warning from registration (email send failure fallback)
if ($warning = flash('warning')) {
    $error = $warning;
}

// ---- Resend OTP ----
if (isset($_POST['resend'])) {
    $otp = generate_otp();
    $expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
    $stmt = db()->prepare('UPDATE users SET otp = ?, otp_expiry = ? WHERE user_id = ?');
    $stmt->execute([$otp, $expiry, $pendingId]);

    $mailResult = send_otp_email($pendingUser['email'], $otp);
    if ($mailResult === true) {
        $message = 'A new OTP has been sent to <strong>' . e($pendingUser['email']) . '</strong>.';
    } else {
        $error = 'Could not send OTP. ' . $mailResult;
    }
}

// ---- Verify OTP ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $enteredOtp = trim($_POST['otp'] ?? '');

    if ($enteredOtp === '') {
        $error = 'Please enter the OTP code.';
    } else {
        // Refresh user data
        $stmt = db()->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
        $stmt->execute([$pendingId]);
        $pendingUser = $stmt->fetch();

        if (!$pendingUser) {
            $error = 'Account not found. Please register again.';
        } elseif ((int)$pendingUser['is_verified'] === 1) {
            $message = 'Account already verified. You can now log in.';
            unset($_SESSION['pending_registration']);
        } elseif ($pendingUser['otp'] !== $enteredOtp) {
            $error = 'Incorrect OTP. Please check the code and try again.';
        } elseif (strtotime($pendingUser['otp_expiry']) < time()) {
            $error = 'This OTP has expired. Please request a new one.';
        } else {
            // Success - mark verified, clear OTP
            $stmt = db()->prepare('UPDATE users SET is_verified = 1, otp = NULL, otp_expiry = NULL WHERE user_id = ?');
            $stmt->execute([$pendingId]);

            unset($_SESSION['pending_registration']);
            log_activity('Register & Verify', 'Account verified: ' . $pendingUser['username']);

            $_SESSION['success'] = 'Email verified successfully! You can now log in.';
            header('Location: ' . BASE_URL . 'authentication/login.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | <?= e(SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/login.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/darkmode.css" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo" style="background:linear-gradient(135deg,#2a9d8f,#23837a);"><i class="bi bi-shield-check"></i></div>
        <h2>Verify Your Email</h2>
        <p class="login-sub">Enter the one-time code sent to your email</p>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-x-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($pendingUser): ?>
        <div class="text-center mb-3">
            <p class="text-muted small mb-1">A <strong>6-digit code</strong> was sent to:</p>
            <div class="alert alert-light border d-inline-block px-3 py-1 mb-0">
                <i class="bi bi-envelope me-1"></i><strong><?= e($pendingUser['email']) ?></strong>
            </div>
            <p class="text-muted small mt-2 mb-0">Code expires in <?= e(OTP_EXPIRY_MINUTES) ?> minutes.</p>
        </div>

        <form method="POST" data-validate>
            <div class="mb-3">
                <label class="form-label">One-Time Password</label>
                <div class="input-icon">
                    <i class="bi bi-key"></i>
                    <input type="text" class="form-control text-center fw-bold" name="otp" placeholder="••••••"
                           maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required autofocus
                           style="letter-spacing:8px;font-size:1.4rem;">
                </div>
                <div class="form-text text-center">Enter the 6-digit code from your email.</div>
            </div>
            <button class="btn-login" type="submit" name="verify" value="1">
                <i class="bi bi-shield-check"></i> Verify Account
            </button>
        </form>

        <div class="login-footer">
            <form method="POST" class="d-inline">
                <button type="submit" name="resend" value="1" class="btn btn-sm btn-soft">
                    <i class="bi bi-arrow-repeat"></i> Resend Code
                </button>
            </form>
            <div class="mt-2">
                <a href="<?= BASE_URL ?>authentication/login.php" class="text-muted">
                    <i class="bi bi-box-arrow-in-right"></i> Back to Login
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-person-x"></i>
            <p>No pending account found. Please register first.</p>
            <a href="<?= BASE_URL ?>authentication/register.php" class="btn btn-primary">Register</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>const BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>assets/js/theme.js"></script>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
