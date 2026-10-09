<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Require a pending password reset (email must be in session)
$email = $_SESSION['password_reset_email'] ?? '';
if ($email === '') {
    header('Location: ' . BASE_URL . 'authentication/forgot_password.php');
    exit;
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $otp         = trim($_POST['otp'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';

    // Load the user by email
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'Account not found. Please try again.';
        unset($_SESSION['password_reset_email']);
    } elseif ($otp === '') {
        $error = 'Please enter the OTP code.';
    } elseif ($user['otp'] !== $otp) {
        $error = 'Incorrect OTP. Please check the code and try again.';
    } elseif ($user['otp_expiry'] && strtotime($user['otp_expiry']) < time()) {
        $error = 'This OTP has expired. Please request a new one.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($newPassword !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } else {
        // Success - update the password, clear OTP, mark verified
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE users SET password = ?, otp = NULL, otp_expiry = NULL, is_verified = 1 WHERE user_id = ?');
        $stmt->execute([$hash, $user['user_id']]);

        log_activity('Password Reset', 'Password reset for user: ' . $user['username']);

        // Clear the reset session
        unset($_SESSION['password_reset_email']);

        $_SESSION['success'] = 'Your password has been reset successfully. You can now log in.';
        header('Location: ' . BASE_URL . 'authentication/login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | <?= e(SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/login.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/darkmode.css" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo" style="background:linear-gradient(135deg,#2a9d8f,#23837a);"><i class="bi bi-shield-lock"></i></div>
        <h2>Reset Password</h2>
        <p class="login-sub">Enter the code and set a new password</p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-x-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" data-validate>
            <div class="mb-3">
                <label class="form-label">Verification Code</label>
                <div class="input-icon">
                    <i class="bi bi-shield-check"></i>
                    <input type="text" class="form-control text-center fw-bold" name="otp" placeholder="••••••"
                           maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required autofocus
                           style="letter-spacing:8px;font-size:1.4rem;">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <div class="input-icon">
                    <i class="bi bi-lock"></i>
                    <input type="password" class="form-control" name="new_password" placeholder="Min 6 characters" required minlength="6">
                </div>
                <div class="form-text">Minimum 6 characters.</div>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm New Password</label>
                <div class="input-icon">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Re-enter new password" required>
                </div>
            </div>
            <button class="btn-login" type="submit" name="reset" value="1">
                <i class="bi bi-check-circle"></i> Reset Password
            </button>
        </form>

        <div class="login-footer">
            <a href="<?= BASE_URL ?>authentication/forgot_password.php" class="text-muted">
                <i class="bi bi-arrow-left"></i> Resend code / change email
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
