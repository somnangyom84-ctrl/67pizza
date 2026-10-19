<?php
/**
 * 67pizza – PDO database connection
 * Default XAMPP credentials: user "root", empty password.
 * If your MySQL uses a password, change DB_PASS below.
 */
declare(strict_types=1);

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', '67pizza_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared PDO instance.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo '<h2 style="font-family:sans-serif">Database connection failed</h2>';
            echo '<p style="font-family:sans-serif">Check <code>config/database.php</code> and make sure MySQL is running and the database <code>67pizza_db</code> has been imported.</p>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        } else {
            echo 'Service temporarily unavailable.';
        }
        exit;
    }
    return $pdo;
}
