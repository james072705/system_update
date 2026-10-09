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

    $allowedRoles = ['admin', 'kitchen', 'staff'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    $passwordErrors = validate_password($password);
    $errors = array_merge($errors, $passwordErrors);
    if ($password !== $confirm) {
        $errors[] = 'Password and confirmation do not match.';
    }
    if (!in_array($role, $allowedRoles)) {
        $errors[] = 'Please select a valid role.';
    }
    if ($role === 'admin' && $adminExists) {
        $errors[] = 'An admin account already exists. Only one admin is allowed. Please choose Kitchen or Staff.';
    }

    if (empty($errors)) {
        $stmt = db()->prepare('SELECT user_id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([$email, $username]);
        $existing = $stmt->fetch();

        if ($existing) {
            $errors[] = 'That email or username is already registered. Please log in instead.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $otp  = generate_otp();
        $expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

        try {
            $stmt = db()->prepare(
                "INSERT INTO users (fullname, email, username, password, role, status, is_verified, otp, otp_expiry)
                 VALUES (?, ?, ?, ?, ?, 'active', 0, ?, ?)");
            $fullname = $username;
            $stmt->execute([$fullname, $email, $username, $hash, $role, $otp, $expiry]);

            $userId = db()->lastInsertId();
            $mailResult = send_otp_email($email, $otp);
            $_SESSION['pending_registration'] = (int)$userId;

            if ($mailResult === true) {
                header('Location: ' . BASE_URL . 'authentication/verify.php?email=' . urlencode($email));
                exit;
            } else {
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?= e(SITE_NAME) ?></title>
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
        .confirm-error-msg {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.4rem;
            display: none;
            font-weight: 600;
        }
        .confirm-error-msg.visible {
            display: block;
        }
        .confirm-success-msg {
            color: #198754;
            font-size: 0.85rem;
            margin-top: 0.4rem;
            display: none;
            font-weight: 600;
        }
        .confirm-success-msg.visible {
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
        .btn-register:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .btn-register {
            width: 100%;
            border-radius: 12px;
            padding: .8rem;
            font-weight: 700;
            font-size: 1.05rem;
            background: linear-gradient(135deg, #e63946, #c1121f);
            color: #fff;
            border: none;
            transition: all .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
        }
        .btn-register:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230,57,70,.4);
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

        <form method="POST" id="registerForm">
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
                    <input type="password" class="form-control" name="password" id="registerPassword"
                           placeholder="Enter password" required minlength="8">
                    <button type="button" class="toggle-password" data-target="registerPassword" tabindex="-1">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <ul class="password-requirements" id="passwordRequirements">
                    <li id="req-length"><i class="bi bi-x-circle"></i> At least 8 characters</li>
                    <li id="req-uppercase"><i class="bi bi-x-circle"></i> At least 1 uppercase letter (A-Z)</li>
                    <li id="req-lowercase"><i class="bi bi-x-circle"></i> At least 1 lowercase letter (a-z)</li>
                    <li id="req-number"><i class="bi bi-x-circle"></i> At least 1 number (0-9)</li>
                    <li id="req-special"><i class="bi bi-x-circle"></i> At least 1 special character (!@#$%&*)</li>
                </ul>
                <div class="password-error-msg" id="passwordErrorMsg">
                    Error: Password does not meet all requirements.
                </div>
                <div class="password-success-msg" id="passwordSuccessMsg">
                    Password meets all requirements
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <div class="input-icon">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" class="form-control" name="confirm_password" id="confirmPassword"
                           placeholder="Re-enter password" required>
                    <button type="button" class="toggle-password" data-target="confirmPassword" tabindex="-1">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="confirm-error-msg" id="confirmErrorMsg">
                    Error: Passwords do not match. Please enter the same password.
                </div>
                <div class="confirm-success-msg" id="confirmSuccessMsg">
                    Passwords match
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
                    <div class="form-text text-dark mt-2">
                        <i class="bi bi-info-circle"></i> Admin role is taken — only one admin is allowed.
                    </div>
                <?php endif; ?>
            </div>

            <button class="btn-register" type="submit" id="registerBtn" disabled>
                <i class="bi bi-envelope-check"></i> Register &amp; Verify
            </button>
        </form>

        <div class="login-footer">
            <p class="mb-2">Already have an account?</p>
            <a href="<?= BASE_URL ?>authentication/login.php" class="fw-bold">
                <i class="bi bi-box-arrow-in-right"></i> Back to Login
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>const BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('registerPassword');
    const confirmInput = document.getElementById('confirmPassword');
    const registerBtn = document.getElementById('registerBtn');

    if (!passwordInput || !confirmInput || !registerBtn) return;

    const reqLength = document.getElementById('req-length');
    const reqUppercase = document.getElementById('req-uppercase');
    const reqLowercase = document.getElementById('req-lowercase');
    const reqNumber = document.getElementById('req-number');
    const reqSpecial = document.getElementById('req-special');

    const passwordErrorMsg = document.getElementById('passwordErrorMsg');
    const passwordSuccessMsg = document.getElementById('passwordSuccessMsg');
    const confirmErrorMsg = document.getElementById('confirmErrorMsg');
    const confirmSuccessMsg = document.getElementById('confirmSuccessMsg');

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

    function validateConfirm() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;

        if (confirm.length === 0) {
            confirmErrorMsg.classList.remove('visible');
            confirmSuccessMsg.classList.remove('visible');
            confirmInput.classList.remove('input-invalid');
            confirmInput.classList.remove('input-valid');
            return false;
        }

        if (password === confirm) {
            confirmErrorMsg.classList.remove('visible');
            confirmSuccessMsg.classList.add('visible');
            confirmInput.classList.remove('input-invalid');
            confirmInput.classList.add('input-valid');
            return true;
        } else {
            confirmErrorMsg.classList.add('visible');
            confirmSuccessMsg.classList.remove('visible');
            confirmInput.classList.add('input-invalid');
            confirmInput.classList.remove('input-valid');
            return false;
        }
    }

    function updateRegisterButton() {
        const passwordValid = validatePassword();
        const confirmValid = validateConfirm();
        const email = document.querySelector('input[name="email"]').value.trim();
        const username = document.querySelector('input[name="username"]').value.trim();

        const allFieldsFilled = email !== '' && username !== '' && passwordInput.value !== '' && confirmInput.value !== '';
        registerBtn.disabled = !(allFieldsFilled && passwordValid && confirmValid);
    }

    passwordInput.addEventListener('input', updateRegisterButton);
    confirmInput.addEventListener('input', updateRegisterButton);
    passwordInput.addEventListener('keyup', updateRegisterButton);
    confirmInput.addEventListener('keyup', updateRegisterButton);

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

    updateRegisterButton();
});
</script>
</body>
</html>
