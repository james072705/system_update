<?php
/**
 * PDO Database connection (singleton)
 */
require_once __DIR__ . '/constants.php';

function db() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            die('<div style="font-family:Arial;text-align:center;margin-top:80px;color:#dc3545">
                 <h2>Database Connection Error</h2>
                 <p>' . htmlspecialchars($e->getMessage()) . '</p>
                 <p>Please make sure MySQL is running in XAMPP and run <a href="install.php">install.php</a>.</p>
                 </div>');
        }
    }

    return $pdo;
}

