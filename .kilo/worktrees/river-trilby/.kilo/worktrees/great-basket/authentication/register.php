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

$errors = [];
$formData = ['email' => '', 'username' => '', 'role' => ''];

// Check if an admin account already exists (only one admin allowed)
$adminExists = (int)db()->query("SELECT COUNT(*) c FROM users WHERE role = 'admin'")->fetch()['c'] > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? '';

    $formData['email']    = $email;
    $formData['username'] = $username;
    $formData['role']     = $role;

    // ---- Allowed roles ----
    $allowedRoles = ['admin', 'kitchen', 'staff'];

    // ---- Validation ----
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Password and confirmation do not match.';
    }
    if (!in_array($role, $allowedRoles)) {
        $errors[] = 'Please select a valid role.';
    }
    // Only one admin allowed
    if ($role === 'admin' && $adminExists) {
        $errors[] = 'An admin account already exists. Only one admin is allowed. Please choose Kitchen or Staff.';
    }

    // ---- Check duplicates ----
    if (empty($errors)) {
        $stmt = db()->prepare('SELECT user_id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([$email, $username]);
        $existing = $stmt->fetch();

        if ($existing) {
            $errors[] = 'That email or username is already registered. Please log in instead.';
        }
    }

    // ---- Create account + send OTP ----
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $otp  = generate_otp();
        $expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

        try {
            $stmt = db()->prepare(
                "INSERT INTO users (fullname, email, username, password, role, status, is_verified, otp, otp_expiry)
                 VALUES (?, ?, ?, ?, ?, 'active', 0, ?, ?)");
            $fullname = $username; // use username as display name for now
            $stmt->execute([$fullname, $email, $username, $hash, $role, $otp, $expiry]);

            $userId = db()->lastInsertId();

            // Send OTP email
            $mailResult = send_otp_email($email, $otp);

            // Store user id in session for the verification step
            $_SESSION['pending_registration'] = (int)$userId;

            if ($mailResult === true) {
                header('Location: ' . BASE_URL . 'authentication/verify.php?email=' . urlencode($email));
                exit;
            } else {
                // Email failed - show a warning but still allow verification via a fallback
                $_SESSION['warning'] = 'Account created, but the OTP email could not be sent. ' . $mailResult;
                header('Location: ' . BASE_URL . 'authentication/verify.php?email=' . urlencode($email));
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?= e(SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/login.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/darkmode.css" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo"><i class="bi bi-person-plus-fill"></i></div>
        <h2>Create Account</h2>
        <p class="login-sub">Register to <?= e(SITE_NAME) ?></p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="bi bi-exclamation-circle me-1"></i>Please fix the following:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" data-validate>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-icon">
                    <i class="bi bi-envelope"></i>
                    <input type="email" class="form-control" name="email" placeholder="you@email.com"
                           value="<?= e($formData['email']) ?>" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-icon">
                    <i class="bi bi-person"></i>
                    <input type="text" class="form-control" name="username" placeholder="Choose a username (min 3 chars)"
                           value="<?= e($formData['username']) ?>" required minlength="3">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-icon">
                    <i class="bi bi-lock"></i>
                    <input type="password" class="form-control" name="password" placeholder="Min 6 characters" required minlength="6">
                </div>
            </div>
<div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <div class="input-icon">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Re-enter password" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Select Role</label>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="role-option <?= $adminExists ? 'disabled' : '' ?>">
                            <input type="radio" name="role" value="admin" class="d-none"
                                <?= $adminExists ? 'disabled' : '' ?>
                                <?= ($formData['role'] ?? '') === 'admin' ? 'checked' : '' ?>>
                            <div class="role-card text-center">
                                <i class="bi bi-shield-lock"></i>
                                <div class="fw-bold">Admin</div>
                                <small class="text-muted">Full access</small>
                            </div>
                        </label>
                    </div>
                    <div class="col-6">
                        <label class="role-option">
                            <input type="radio" name="role" value="kitchen" class="d-none"
                                <?= ($formData['role'] ?? '') === 'kitchen' ? 'checked' : '' ?>>
                            <div class="role-card text-center">
                                <i class="bi bi-egg-fried"></i>
                                <div class="fw-bold">Kitchen</div>
                                <small class="text-muted">Manage orders</small>
                            </div>
                        </label>
                    </div>
                </div>
                <?php if ($adminExists): ?>
                    <div class="form-text text-warning mt-2">
                        <i class="bi bi-info-circle"></i> Admin role is taken — only one admin is allowed.
                    </div>
                <?php endif; ?>
            </div>

            <button class="btn-login" type="submit">
                <i class="bi bi-envelope-check"></i> Register &amp; Verify
            </button>
        </form>

        <div class="login-footer">
            <p class="mb-2">Already have an account?</p>
            <a href="<?= BASE_URL ?>authentication/login.php" class="fw-bold">
                <i class="bi bi-box-arrow-in-right"></i> Back to Login
            </a>
            <div class="d-flex justify-content-center gap-3 mb-3 mt-3">
                <button class="btn btn-sm btn-soft theme-toggle"><i class="bi bi-moon-stars-fill"></i> Theme</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>const BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>assets/js/theme.js"></script>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
