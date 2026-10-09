<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/mailer.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$success = '';
$error   = '';

// Handle resend code request
if (isset($_POST['resend']) && isset($_SESSION['password_reset_email'])) {
    $email = $_SESSION['password_reset_email'];
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $otp = generate_otp();
        $expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
        $stmt = db()->prepare('UPDATE users SET otp = ?, otp_expiry = ? WHERE user_id = ?');
        $stmt->execute([$otp, $expiry, $user['user_id']]);

        $mailResult = send_otp_email($email, $otp);
        if ($mailResult === true) {
            $success = 'A new verification code has been sent to <strong>' . e($email) . '</strong>.';
        } else {
            $error = 'Could not resend the code. ' . $mailResult;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['resend'])) {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if the email exists in the system
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Don't reveal whether the email exists (security best practice).
            // Just show a generic success message.
            $success = 'If an account exists with that email, a password reset link has been sent. Please check your inbox.';
        } else {
            // Generate OTP and store it
            $otp = generate_otp();
            $expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

            $stmt = db()->prepare('UPDATE users SET otp = ?, otp_expiry = ? WHERE user_id = ?');
            $stmt->execute([$otp, $expiry, $user['user_id']]);

            // Send the OTP email
            $mailResult = send_otp_email($email, $otp);

            if ($mailResult === true) {
                // Store email in session so reset page knows which account
                $_SESSION['password_reset_email'] = $email;
                $success = 'A 6-digit verification code has been sent to <strong>' . e($email) . '</strong>. Please check your inbox.';
            } else {
                $error = 'Could not send the reset email. ' . $mailResult;
            }
        }
    }
}

// If we have a pending reset, show the code input form inline
$showCodeInput = isset($_SESSION['password_reset_email']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | <?= e(SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/login.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/darkmode.css" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo" style="background:linear-gradient(135deg,#457b9d,#3a6b8a);"><i class="bi bi-key"></i></div>
        <h2>Forgot Password</h2>
        <p class="login-sub">We'll send a code to reset your password</p>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-x-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$showCodeInput): ?>
            <!-- Step 1: Enter email to get OTP -->
            <form method="POST" data-validate>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope"></i>
                        <input type="email" class="form-control" name="email" placeholder="Enter your registered email"
                               value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                    </div>
                </div>
                <button class="btn-login" type="submit">
                    <i class="bi bi-send"></i> Send Reset Code
                </button>
            </form>
        <?php else: ?>
            <!-- Step 2: Enter OTP to proceed to reset -->
            <form method="POST" action="reset_password.php" data-validate>
                <div class="text-center mb-3">
                    <p class="text-muted small mb-1">Code sent to:</p>
                    <div class="alert alert-light border d-inline-block px-3 py-1 mb-0">
                        <i class="bi bi-envelope me-1"></i><strong><?= e($_SESSION['password_reset_email']) ?></strong>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Valid for <?= e(OTP_EXPIRY_MINUTES) ?> minutes.</p>
                </div>
                <div class="mb-3">
                    <label class="form-label">Enter the 6-digit Code</label>
                    <div class="input-icon">
                        <i class="bi bi-shield-lock"></i>
                        <input type="text" class="form-control text-center fw-bold" name="otp" placeholder="••••••"
                               maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required autofocus
                               style="letter-spacing:8px;font-size:1.4rem;">
                    </div>
                    <div class="form-text text-center">Then you'll be able to set a new password.</div>
                </div>
                <button class="btn-login" type="submit">
                    <i class="bi bi-arrow-right-circle"></i> Continue to Reset
                </button>
            </form>
            <div class="login-footer mt-3">
                <form method="POST" class="d-inline">
                    <button type="submit" name="resend" value="1" class="btn btn-sm btn-soft">
                        <i class="bi bi-arrow-repeat"></i> Resend Code
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div class="login-footer">
            <a href="<?= BASE_URL ?>authentication/login.php" class="text-muted">
                <i class="bi bi-box-arrow-in-left"></i> Back to Login
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>const BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>assets/js/theme.js"></script>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
