<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCKOUT_MINUTES', 3);

try {
    db()->exec("ALTER TABLE users ADD COLUMN failed_attempts INT NOT NULL DEFAULT 0");
} catch (Exception $e) { /* column already exists */ }
try {
    db()->exec("ALTER TABLE users ADD COLUMN locked_until DATETIME NULL");
} catch (Exception $e) { /* column already exists */ }

$error = '';
$lockout = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            $now = time();
            $lockedUntil = $user['locked_until'] ? strtotime($user['locked_until']) : 0;

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
            } elseif ($lockedUntil > 0 && $lockedUntil <= $now) {
                $stmt = db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?');
                $stmt->execute([$user['user_id']]);
                $user['failed_attempts'] = 0;
                $user['locked_until'] = null;
            }
        }

        if ($user && ($user['locked_until'] === null || strtotime($user['locked_until']) <= time())) {
            $isInactive = $user['status'] !== 'active';

            if (!$isInactive && password_verify($password, $user['password'])) {
                db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?')
                    ->execute([$user['user_id']]);

                if ((int)$user['is_verified'] !== 1) {
                    $_SESSION['pending_registration'] = (int)$user['user_id'];
                    $error = 'Your email is not verified yet. Please enter the OTP sent to your email to verify your account before logging in.';
                    header('Location: ' . BASE_URL . 'authentication/verify.php?email=' . urlencode($user['email']));
                    exit;
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id']   = $user['user_id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['fullname']  = $user['fullname'];
                    $_SESSION['role']      = $user['role'];

                    log_activity('Login', 'User logged in: ' . $user['username']);

                    if ($user['role'] === 'kitchen') {
                        header('Location: ' . BASE_URL . 'kitchen/index.php');
                    } else {
                        header('Location: ' . BASE_URL . 'admin/dashboard/dashboard.php');
                    }
                    exit;
                }
            } else {
                $attempts = (int)$user['failed_attempts'] + 1;

                if ($attempts >= MAX_LOGIN_ATTEMPTS) {
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= e(SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/login.css" rel="stylesheet">
    <style>
        .password-requirements {
            list-style: none;
            padding: 0;
            margin: 0.5rem 0 0 0;
            font-size: 0.85rem;
        }
        .password-requirements li {
            padding: 0.3rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #dc3545;
            transition: color 0.2s;
        }
        .password-requirements li.valid {
            color: #198754;
        }
        .password-requirements li i {
            font-size: 0.95rem;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
        }
        .password-error-msg {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.4rem;
            display: none;
            font-weight: 600;
        }
        .password-error-msg.visible {
            display: block;
        }
        .password-success-msg {
            color: #198754;
            font-size: 0.85rem;
            margin-top: 0.4rem;
            display: none;
            font-weight: 600;
        }
        .password-success-msg.visible {
            display: block;
        }
        .input-icon {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 0;
            z-index: 10;
        }
        .toggle-password:hover {
            color: #495057;
        }
        .btn-login:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .input-invalid {
            border-color: #dc3545 !important;
        }
        .input-valid {
            border-color: #198754 !important;
        }
    </style>
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

        <form method="POST" id="loginForm">
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
                    <input type="password" class="form-control" name="password" id="loginPassword"
                           placeholder="Enter password" required minlength="8">
                    <button type="button" class="toggle-password" data-target="loginPassword" tabindex="-1">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <ul class="password-requirements" id="loginPasswordRequirements">
                    <li id="login-req-length"><i class="bi bi-x-circle"></i> At least 8 characters</li>
                    <li id="login-req-uppercase"><i class="bi bi-x-circle"></i> At least 1 uppercase letter (A-Z)</li>
                    <li id="login-req-lowercase"><i class="bi bi-x-circle"></i> At least 1 lowercase letter (a-z)</li>
                    <li id="login-req-number"><i class="bi bi-x-circle"></i> At least 1 number (0-9)</li>
                    <li id="login-req-special"><i class="bi bi-x-circle"></i> At least 1 special character (!@#$%&*)</li>
                </ul>
                <div class="password-error-msg" id="loginPasswordErrorMsg">
                    Error: Password does not meet all requirements.
                </div>
                <div class="password-success-msg" id="loginPasswordSuccessMsg">
                    Password format is valid
                </div>
            </div>
            <button class="btn-login" type="submit" id="loginBtn" disabled>
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="<?= BASE_URL ?>authentication/forgot_password.php" class="text-muted small">
                <i class="bi bi-key"></i> Forgot Password?
            </a>
        </div>

        <div class="login-footer">
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
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('loginPassword');
    const loginBtn = document.getElementById('loginBtn');

    if (!passwordInput || !loginBtn) return;

    const reqLength = document.getElementById('login-req-length');
    const reqUppercase = document.getElementById('login-req-uppercase');
    const reqLowercase = document.getElementById('login-req-lowercase');
    const reqNumber = document.getElementById('login-req-number');
    const reqSpecial = document.getElementById('login-req-special');

    const passwordErrorMsg = document.getElementById('loginPasswordErrorMsg');
    const passwordSuccessMsg = document.getElementById('loginPasswordSuccessMsg');

    function setRequirement(element, isValid) {
        if (!element) return;
        const icon = element.querySelector('i');
        if (!icon) return;

        if (isValid) {
            element.classList.add('valid');
            icon.style.color = '#198754';
            icon.className = 'bi bi-check-circle';
        } else {
            element.classList.remove('valid');
            icon.style.color = '#dc3545';
            icon.className = 'bi bi-x-circle';
        }
    }

    function validatePassword() {
        const password = passwordInput.value;
        const hasLength = password.length >= 8;
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[^A-Za-z0-9]/.test(password);

        setRequirement(reqLength, hasLength);
        setRequirement(reqUppercase, hasUppercase);
        setRequirement(reqLowercase, hasLowercase);
        setRequirement(reqNumber, hasNumber);
        setRequirement(reqSpecial, hasSpecial);

        const allValid = hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial;

        if (password.length > 0) {
            if (allValid) {
                passwordErrorMsg.classList.remove('visible');
                passwordSuccessMsg.classList.add('visible');
                passwordInput.classList.remove('input-invalid');
                passwordInput.classList.add('input-valid');
            } else {
                passwordErrorMsg.classList.add('visible');
                passwordSuccessMsg.classList.remove('visible');
                passwordInput.classList.add('input-invalid');
                passwordInput.classList.remove('input-valid');
            }
        } else {
            passwordErrorMsg.classList.remove('visible');
            passwordSuccessMsg.classList.remove('visible');
            passwordInput.classList.remove('input-invalid');
            passwordInput.classList.remove('input-valid');
        }

        return allValid;
    }

    function updateLoginButton() {
        const passwordValid = validatePassword();
        const username = document.querySelector('input[name="username"]').value.trim();
        const password = passwordInput.value;
        const allFieldsFilled = username !== '' && password !== '';
        loginBtn.disabled = !(allFieldsFilled && passwordValid);
    }

    passwordInput.addEventListener('input', updateLoginButton);
    passwordInput.addEventListener('keyup', updateLoginButton);
    passwordInput.addEventListener('change', updateLoginButton);

    document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });

    updateLoginButton();
});
</script>
</body>
</html>
