<?php
/**
 * Delete Account page - Email + OTP verification
 * A user can delete their own account even if they are the last admin.
 * After deletion, a new admin can register on the public Register page.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/mailer.php';
require_login();

$user = current_user();

// Ensure OTP columns exist (auto-migrate for existing DBs)
try { db()->exec("ALTER TABLE users ADD COLUMN delete_otp VARCHAR(6) NULL"); } catch (Exception $e) {}
try { db()->exec("ALTER TABLE users ADD COLUMN delete_otp_expiry DATETIME NULL"); } catch (Exception $e) {}

$error = $info = '';

// ===== Step 1: Send OTP to email to verify before deleting =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_delete_otp'])) {
    $deletePass = $_POST['delete_password'] ?? '';

    if (!password_verify($deletePass, $user['password'])) {
        $error = 'Your password is incorrect.';
    } else {
        // Generate OTP and store in DB
        $otp = generate_otp();
        $expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
        db()->prepare('UPDATE users SET delete_otp = ?, delete_otp_expiry = ? WHERE user_id = ?')
            ->execute([$otp, $expiry, $user['user_id']]);

        // Send OTP to the user's registered email
        $mailResult = send_otp_email($user['email'] ?? '', $otp);
        if ($mailResult === true) {
            $info = 'A verification code was sent to <strong>' . e($user['email']) . '</strong>. Enter it below to confirm deletion.';
            $_SESSION['delete_otp_pending'] = true;
        } else {
            $error = 'Could not send OTP email. ' . $mailResult;
        }
    }
}

// ===== Step 2: Verify OTP and delete =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $enteredOtp = trim($_POST['delete_otp'] ?? '');

    // Refresh user data (may have been deleted by another tab)
    $fresh = db()->prepare('SELECT * FROM users WHERE user_id = ?');
    $fresh->execute([$user['user_id']]);
    $freshUser = $fresh->fetch();

    if (!$freshUser) {
        $error = 'Account not found. It may have been deleted already.';
    } elseif ($freshUser['delete_otp'] !== $enteredOtp) {
        $error = 'Incorrect verification code. Please check and try again.';
    } elseif (strtotime($freshUser['delete_otp_expiry']) < time()) {
        $error = 'This code has expired. Please request a new one.';
    } else {
        $deletedUsername = $freshUser['username'];
        db()->prepare('DELETE FROM users WHERE user_id = ?')->execute([$freshUser['user_id']]);
        log_activity('Delete Account', 'User deleted their own account (OTP verified): ' . $deletedUsername);

        // Destroy session and redirect to login
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . 'authentication/login.php?account=deleted');
        exit;
    }
}

$pageTitle = 'Delete Account';
$currentPage = 'account';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-exclamation-triangle me-2"></i>Delete My Account
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Deleting your account <strong>permanently removes</strong>
                    <span class="fw-bold"><?= e($user['fullname']) ?> (<?= e($user['username']) ?>)</span>.
                    You will be logged out immediately. After deletion, a new admin can register.
                </p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                <?php if ($info): ?>
                    <div class="alert alert-info"><?= $info ?></div>
                <?php endif; ?>

                <div class="bg-soft rounded-3 p-3">
                    <?php $otpPending = $_SESSION['delete_otp_pending'] ?? false; ?>
                    <?php if (!$otpPending): ?>
                    <!-- STEP 1: enter password to receive OTP code -->
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Your Password</label>
                            <input type="password" class="form-control" name="delete_password" required
                                   placeholder="Confirm with your password" autocomplete="current-password">
                            <div class="form-text">We'll send a verification code to
                                <strong><?= e($user['email'] ?? '') ?></strong> to confirm deletion.</div>
                        </div>
                        <button type="submit" name="send_delete_otp" value="1" class="btn btn-danger">
                            <i class="bi bi-envelope-check"></i> Send Verification Code
                        </button>
                        <a href="<?= BASE_URL ?>authentication/change_password.php" class="btn btn-light ms-1">
                            Cancel
                        </a>
                    </form>
                    <?php else: ?>
                    <!-- STEP 2: enter OTP code to complete deletion -->
                    <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to permanently delete this account? This cannot be undone.');">
                        <div class="mb-3">
                            <label class="form-label">Verification Code</label>
                            <input type="text" class="form-control text-center fw-bold"
                                   name="delete_otp" placeholder="••••••" maxlength="6"
                                   inputmode="numeric" pattern="[0-9]{6}" required
                                   autofocus style="letter-spacing:8px;font-size:1.3rem;">
                            <div class="form-text">Enter the 6-digit code sent to
                                <strong><?= e($user['email'] ?? '') ?></strong>.</div>
                        </div>
                        <button type="submit" name="confirm_delete" value="1" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Confirm &amp; Delete My Account
                        </button>
                        <a href="<?= BASE_URL ?>authentication/change_password.php" class="btn btn-light ms-1">
                            Cancel
                        </a>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
