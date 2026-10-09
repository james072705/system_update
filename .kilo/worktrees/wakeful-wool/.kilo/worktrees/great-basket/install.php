<?php
/**
 * Installer - Creates the database, tables, and default admin account.
 * Run once: http://localhost/kiosk/install.php
 * Then delete this file for security.
 */
require_once __DIR__ . '/config/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $host = $_POST['db_host'] ?? 'localhost';
    $user = $_POST['db_user'] ?? 'root';
$pass = $_POST['db_pass'] ?? '';
    $name = $_POST['db_name'] ?? 'kiosk_system';
    $adminUser = $_POST['admin_user'] ?? 'admin';
    $adminPass = $_POST['admin_pass'] ?? '';
    $adminName = $_POST['admin_name'] ?? 'Administrator';
    $adminRole = $_POST['admin_role'] ?? 'admin';

    $errors = [];

    try {
        // Connect without DB
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create DB
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$name`");

        // Read and execute schema + sample data
        $schema = file_get_contents(__DIR__ . '/database/kiosk_system.sql');
        $sample = file_get_contents(__DIR__ . '/database/sample_data.sql');

        // Strip CREATE DATABASE / USE lines from bottom-up (we already did that)
        $pdo->exec($schema);
        $pdo->exec($sample);

// Create admin account only (kitchen & kiosk auto-open, no login needed)
        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $adminEmail = $adminUser . '@kiosk.local';
        $stmt = $pdo->prepare(
            "INSERT INTO users (fullname, email, username, password, role, status, is_verified, otp, otp_expiry)
             VALUES (?, ?, ?, ?, ?, 'active', 1, NULL, NULL)");
        $stmt->execute([$adminName, $adminEmail, $adminUser, $hash, $adminRole]);

        $installed = true;
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Ferry's Kiosk System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body{background:#f4f6fb;font-family:'Segoe UI',sans-serif}
        .install-card{max-width:640px;margin:60px auto;background:#fff;border-radius:20px;box-shadow:0 10px 40px rgba(0,0,0,.1);padding:2.5rem}
        .step-icon{width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#e63946,#c1121f);color:#fff;display:grid;place-items:center;font-size:1.8rem;margin:0 auto 1rem}
        .success-box{background:#e8f5f0;border:1px solid #2a9d8f;color:#1c6b5f;border-radius:12px;padding:1.5rem;text-align:center}
        .success-box i{font-size:2.5rem;color:#2a9d8f}
    </style>
</head>
<body>
<div class="container">
    <div class="install-card">
        <div class="text-center">
            <div class="step-icon"><i class="bi bi-database-gear"></i></div>
            <h2 class="fw-bold">Ferry's Kiosk Installer</h2>
            <p class="text-muted">Set up your database and default accounts</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Installation failed:</strong>
                <ul class="mb-0 mt-2"><?php foreach ($errors as $er): ?><li><?= htmlspecialchars($er) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <?php if (isset($installed) && $installed): ?>
            <div class="success-box">
                <i class="bi bi-check-circle-fill"></i>
                <h4 class="mt-3 fw-bold">Installation Successful!</h4>
                <p class="mb-4">Your database has been created and populated.</p>
<div class="text-start mb-4">
                    <h6 class="fw-bold">Default Account</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Admin</strong></td><td><?= htmlspecialchars($adminUser) ?> / <?= htmlspecialchars($adminPass) ?></td></tr>
                    </table>
                    <p class="text-muted small">Kitchen &amp; Kiosk auto-open — no login needed.</p>
                </div>
                <a href="authentication/login.php" class="btn btn-primary btn-lg">Go to Login</a>
            </div>
            <div class="alert alert-warning mt-4 mb-0">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Security:</strong> Please delete <code>install.php</code> from your server now.
            </div>
        <?php else: ?>
            <form method="POST">
                <h6 class="fw-bold text-uppercase text-muted mb-3">Database Configuration</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Host</label>
                        <input class="form-control" name="db_host" value="localhost" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">User</label>
                        <input class="form-control" name="db_user" value="root" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Password</label>
                        <input class="form-control" name="db_pass" value="">
                    </div>
                    <div class="col-12">
<label class="form-label">Database Name</label>
                        <input class="form-control" name="db_name" value="kiosk_system" required>
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="fw-bold text-uppercase text-muted mb-3">Admin Account</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input class="form-control" name="admin_name" value="Administrator" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="admin_role">
                            <option value="admin">Administrator</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input class="form-control" name="admin_user" value="admin" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input class="form-control" type="text" name="admin_pass" value="admin123" required>
                    </div>
                </div>

                <button class="btn btn-primary btn-lg w-100 mt-4" name="install" value="1">
                    <i class="bi bi-rocket-takeoff"></i> Install Now
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
