<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Lockout settings
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCKOUT_MINUTES', 3);

// Auto-migrate: ensure the lockout columns exist (works on existing databases)
try {
    db()->exec("ALTER TABLE users ADD COLUMN failed_attempts INT NOT NULL DEFAULT 0");
} catch (Exception $e) { /* column already exists */ }
try {
    db()->exec("ALTER TABLE users ADD COLUMN locked_until DATETIME NULL");
} catch (Exception $e) { /* column already exists */ }

$error = '';
$lockout = null; // [remaining_seconds] when account is locked

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // If account exists, check lockout state FIRST
        if ($user) {
            $now = time();
            $lockedUntil = $user['locked_until'] ? strtotime($user['locked_until']) : 0;

            // If a lock is still active
            if ($lockedUntil > $now) {
                $remaining = $lockedUntil - $now;
                $mins = floor($remaining / 60);
                $secs = $remaining % 60;
                $lockout = [
                    'remaining' => $remaining,
                    'message' => sprintf(
                        'Too many failed login attempts. Account is locked. Please try again in %d:%02d minutes.',
                        $mins, $secs
                    )
                ];
                log_activity('Login Blocked', 'Locked account attempted login: ' . $username);
                $error = $lockout['message'];
            }
            // Lock expired -> reset counters
            elseif ($lockedUntil > 0 && $lockedUntil <= $now) {
                $stmt = db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?');
                $stmt->execute([$user['user_id']]);
                $user['failed_attempts'] = 0;
                $user['locked_until'] = null;
            }
        }

// Proceed with verification only if not locked
        if ($user && ($user['locked_until'] === null || strtotime($user['locked_until']) <= time())) {
            // Reject inactive accounts (status is ENUM 'active'/'inactive')
            $isInactive = $user['status'] !== 'active';

            if (!$isInactive && password_verify($password, $user['password'])) {
                // SUCCESS -> reset failed attempts
                db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?')
                    ->execute([$user['user_id']]);

                // Check email verification
                if ((int)$user['is_verified'] !== 1) {
                    $_SESSION['pending_registration'] = (int)$user['user_id'];
                    $error = 'Your email is not verified yet. Please enter the OTP sent to your email to verify your account before logging in.';
                    header('Location: ' . BASE_URL . 'authentication/verify.php?email=' . urlencode($user['email']));
                    exit;
                } else {
                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id']   = $user['user_id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['fullname']  = $user['fullname'];
                    $_SESSION['role']      = $user['role'];

                    log_activity('Login', 'User logged in: ' . $user['username']);

                    // Redirect based on role
                    if ($user['role'] === 'kitchen') {
                        header('Location: ' . BASE_URL . 'kitchen/index.php');
                    } else {
                        header('Location: ' . BASE_URL . 'admin/dashboard/dashboard.php');
                    }
                    exit;
                }
            } else {
                // FAILED attempt -> increment counter
                $attempts = (int)$user['failed_attempts'] + 1;

                if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                    // Lock the account for 3 minutes
                    $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60);
                    db()->prepare('UPDATE users SET failed_attempts = ?, locked_until = ? WHERE user_id = ?')
                        ->execute([$attempts, $lockUntil, $user['user_id']]);

                    $error = 'Invalid username or password. You have reached the maximum of ' . MAX_LOGIN_ATTEMPTS .
                             ' failed attempts. Your account is now locked for ' . LOCKOUT_MINUTES . ' minutes.';
                    log_activity('Login Locked', 'Account locked after failed attempts: ' . $username);
                } else {
                    $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
                    db()->prepare('UPDATE users SET failed_attempts = ? WHERE user_id = ?')
                        ->execute([$attempts, $user['user_id']]);
                    $error = 'Invalid username or password. Attempt ' . $attempts . ' of ' . MAX_LOGIN_ATTEMPTS .
                             '. ' . $remaining . ' attempt(s) remaining before your account locks.';
                }
            }
        } elseif (!$user) {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= e(SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>assets/css/style.css?v=2.1" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/login.css?v=2.1" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/darkmode.css?v=2.1" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo"><i class="bi bi-cup-straw"></i></div>
        <h2>Welcome Back</h2>
        <p class="login-sub">Sign in to <?= e(SITE_NAME) ?> <?= e(APP_TAGLINE) ?></p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-x-circle me-2"></i><?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['account']) && $_GET['account'] === 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-trash me-2"></i>Your account has been deleted. You can now register a new administrator account.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" data-validate>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-icon">
                    <i class="bi bi-person"></i>
                    <input type="text" class="form-control" name="username" placeholder="Enter username" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-icon">
                    <i class="bi bi-lock"></i>
                    <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                </div>
            </div>
<button class="btn-login" type="submit">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="<?= BASE_URL ?>authentication/forgot_password.php" class="text-muted small">
                <i class="bi bi-key"></i> Forgot Password?
            </a>
        </div>

<div class="login-footer">
            <div class="d-flex justify-content-center gap-3 mb-3 mt-3">
                <button class="btn btn-sm btn-soft theme-toggle"><i class="bi bi-moon-stars-fill"></i> Theme</button>
            </div>
            <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                <span class="text-muted">Don't have an account?</span>
                <a href="<?= BASE_URL ?>authentication/register.php" class="btn btn-outline-primary btn-sm fw-bold">
                    <i class="bi bi-person-plus-fill"></i> Register
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>const BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>assets/js/theme.js?v=2.3"></script>
<script src="<?= BASE_URL ?>assets/js/app.js?v=2.1"></script>
</body>
</html>
