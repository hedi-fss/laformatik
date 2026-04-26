<?php
/**
 * LAFORMATIK — Secure PDO Database Connection
 * Uses PDO with error handling and UTF-8 support.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_NAME', 'laformatik');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    die("<div style='padding:2rem;color:#ef4444;font-family:sans-serif;text-align:center;'>
        <h2>Database Connection Error</h2>
        <p>" . htmlspecialchars($e->getMessage()) . "</p>
        <p>Make sure MySQL is running and the <strong>laformatik</strong> database exists.</p>
    </div>");
}
