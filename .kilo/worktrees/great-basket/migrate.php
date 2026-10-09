<?php
/**
 * MIGRATION RUNNER
 * Adds the new email/verification columns to your EXISTING database
 * so the registration & forgot-password features work.
 *
 * How to use:
 *   1. Make sure Apache + MySQL are running in XAMPP
 *   2. Open: http://localhost/kiosk/migrate.php
 *   3. Click "Run Migration"
 *   4. Delete migrate.php afterward
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

$results = [];
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['migrate'])) {
    $pdo = db();

// Helper to check if a column exists (SHOW queries don't support prepared placeholders)
    $columnExists = function ($table, $column) use ($pdo) {
        $escaped = str_replace(['`', '\\'], '', $column);
        // pdo->quote() already wraps in quotes, so use it directly
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE " . $pdo->quote($escaped));
        return (bool)$stmt->fetch();
    };

// Helper to check if a specific column exists in a given table
    $columnExistsIn = function ($table, $column) use ($pdo) {
        $tableSafe = str_replace(['`', '\\'], '', $table);
        $colSafe   = str_replace(['`', '\\'], '', $column);
        $stmt = $pdo->query("SHOW COLUMNS FROM `$tableSafe` LIKE " . $pdo->quote($colSafe));
        return (bool)$stmt->fetch();
    };

    // Helper to check if a table has a specific index
    $indexExists = function ($table, $index) use ($pdo) {
        $escaped = str_replace(['`', '\\'], '', $index);
        $stmt = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = " . $pdo->quote($escaped));
        return (bool)$stmt->fetch();
    };

    try {
        // 1. Add email column (nullable first so existing rows survive)
        if (!$columnExists('users', 'email')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(150) NULL AFTER fullname");
            $results[] = "Added 'email' column to users table.";
        } else {
            $results[] = "'email' column already exists.";
        }

        // 2. Add is_verified column
        if (!$columnExists('users', 'is_verified')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
            $results[] = "Added 'is_verified' column.";
        } else {
            $results[] = "'is_verified' column already exists.";
        }

        // 3. Add otp column
        if (!$columnExists('users', 'otp')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN otp VARCHAR(6) NULL AFTER is_verified");
            $results[] = "Added 'otp' column.";
        } else {
            $results[] = "'otp' column already exists.";
        }

        // 4. Add otp_expiry column
        if (!$columnExists('users', 'otp_expiry')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN otp_expiry DATETIME NULL AFTER otp");
            $results[] = "Added 'otp_expiry' column.";
        } else {
            $results[] = "'otp_expiry' column already exists.";
        }

        // 5. Backfill email for existing users
        $pdo->exec("UPDATE users SET email = CONCAT(username, '@kiosk.local') WHERE email IS NULL OR email = ''");
        $results[] = "Backfilled missing emails.";

        // 6. Mark all existing users as verified
        $pdo->exec("UPDATE users SET is_verified = 1 WHERE is_verified = 0");
        $results[] = "Marked all existing users as verified.";

        // 7. Make email NOT NULL
        $pdo->exec("ALTER TABLE users MODIFY email VARCHAR(150) NOT NULL");
        $results[] = "Made 'email' column NOT NULL.";

        // 8. Add unique index on email (if not exists)
        if (!$indexExists('users', 'email')) {
            $pdo->exec("ALTER TABLE users ADD UNIQUE INDEX email (email)");
            $results[] = "Added UNIQUE index on 'email'.";
        } else {
            $results[] = "UNIQUE index on 'email' already exists.";
        }

        // 9. Add payment_method column to orders (if not exists)
        if (!$columnExistsIn('orders', 'payment_method')) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash'");
            $results[] = "Added 'payment_method' column to orders table.";
        } else {
            $results[] = "'payment_method' column already exists in orders.";
        }

        // 10. Add payment_status column to orders (if not exists)
        if (!$columnExistsIn('orders', 'payment_status')) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid'");
            $results[] = "Added 'payment_status' column to orders table.";
        } else {
            $results[] = "'payment_status' column already exists in orders.";
        }

        // 11. Add paid_at column to orders (if not exists)
        if (!$columnExistsIn('orders', 'paid_at')) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN paid_at DATETIME NULL");
            $results[] = "Added 'paid_at' column to orders table.";
        } else {
            $results[] = "'paid_at' column already exists in orders.";
        }

        // 13. Backfill existing orders as paid so they still flow to kitchen
        $pdo->exec("UPDATE orders SET payment_status = 'paid' WHERE payment_status IS NULL OR payment_status = ''");
        $results[] = "Backfilled existing orders as paid.";

        // 14. Add order_id to stock_movements (if not exists)
        if (!$columnExistsIn('stock_movements', 'order_id')) {
            $pdo->exec("ALTER TABLE stock_movements ADD COLUMN order_id INT NULL AFTER product_id");
            $results[] = "Added 'order_id' column to stock_movements table.";
        } else {
            $results[] = "'order_id' column already exists in stock_movements.";
        }

        $migrated = true;
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
    <title>Database Migration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>body{background:#f4f6fb;font-family:'Segoe UI',sans-serif}.card{max-width:640px;margin:60px auto;border-radius:20px;box-shadow:0 10px 40px rgba(0,0,0,.1)}</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#2a9d8f,#23837a);color:#fff;display:grid;place-items:center;font-size:1.8rem;margin:0 auto 1rem;">
                    <i class="bi bi-database-up"></i>
                </div>
                <h2 class="fw-bold">Database Migration</h2>
                <p class="text-muted">Adds email/verification columns to your existing database</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <strong>Migration failed:</strong>
                    <ul class="mb-0 mt-2"><?php foreach ($errors as $er): ?><li><?= htmlspecialchars($er) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <?php if (isset($migrated) && $migrated): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <strong>Migration completed successfully!</strong>
                </div>
                <ul class="list-group mb-3">
                    <?php foreach ($results as $r): ?>
                        <li class="list-group-item"><i class="bi bi-check-lg text-success me-2"></i><?= htmlspecialchars($r) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="authentication/login.php" class="btn btn-primary w-100">Go to Login</a>
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Please <strong>delete migrate.php</strong> from your server now.
                </div>
            <?php else: ?>
                <p class="text-muted">This will update your <code>users</code> table to add the missing columns needed for the email verification and forgot-password features.</p>
                <form method="POST">
                    <button class="btn btn-success w-100" name="migrate" value="1">
                        <i class="bi bi-play"></i> Run Migration
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
