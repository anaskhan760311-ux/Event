<?php
/**
 * db.php - PDO database connection for the KhanEvent Event Platform.
 *
 * Connects to the MySQL database "event_platform" using PDO with
 * exception error mode and emulated prepares disabled (real prepared statements).
 *
 * Database credentials for a default WAMP/XAMPP installation:
 *   host: localhost
 *   user: root
 *   pass: (empty)
 */

// Database configuration
$db_host   = 'localhost';
$db_name   = 'event_platform';
$db_user   = 'root';
$db_pass   = '';
$db_charset = 'utf8mb4';

$dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Fail gracefully so the rest of the application can report the problem
    http_response_code(500);
    die('<p style="color:#dc2626;font-family:sans-serif;padding:1rem;">Database connection failed: ' .
        htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') .
        '</p>');
}
