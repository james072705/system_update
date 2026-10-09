<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_login();

$user = current_user();
$success = $error = '';

// ===== Handle Change Password =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE users SET password = ? WHERE user_id = ?');
        $stmt->execute([$hash, $user['user_id']]);
        log_activity('Change Password', 'User changed their password');
        $success = 'Password updated successfully.';
    }
}

$pageTitle = 'Change Password';
$currentPage = 'account';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-key me-2"></i>Change Password
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">Update your account password. Choose a strong password you don't use elsewhere.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= e($success) ?></div>
                <?php endif; ?>

                <form method="POST" data-validate>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <div class="form-text">Minimum 6 characters.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" value="1" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Update Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Separated: Delete Account -->
        <div class="card border-danger mt-4">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-trash me-2"></i>Delete Account
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Permanently delete <strong><?= e($user['fullname']) ?> (<?= e($user['username']) ?>)</strong>.
                    You will be logged out immediately. After deletion, a new admin can register.
                </p>
                <a href="<?= BASE_URL ?>authentication/delete_account.php" class="btn btn-danger">
                    <i class="bi bi-person-x"></i> Delete My Account
                </a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
