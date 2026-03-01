<?php

declare(strict_types=1);

/**
 * db.php — PDO database connection (singleton).
 *
 * Returns the same PDO instance on every include/require within a single
 * request lifecycle.  The connection parameters are read from the environment
 * variables populated by phpdotenv in config/app.php.
 *
 * Usage (from any file that already required config/app.php):
 *
 *   $pdo = require __DIR__ . '/db.php';
 *
 * Or, preferably, use App\Core\Database::getInstance() which wraps this file
 * and supports a keyed connection pool for multi-tenancy.
 */

(static function (): PDO {
    // The static variable persists for the lifetime of the PHP process
    // (i.e. for the duration of a single web request under PHP-FPM / mod_php).
    static $instance = null;

    if ($instance !== null) {
        return $instance;
    }

    $host    = $_ENV['DB_HOST'] ?? 'localhost';
    $name    = $_ENV['DB_NAME'] ?? 'kinarahub';
    $user    = $_ENV['DB_USER'] ?? 'root';
    $pass    = $_ENV['DB_PASS'] ?? '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$charset}' COLLATE 'utf8mb4_unicode_ci'",
    ];

    $instance = new PDO($dsn, $user, $pass, $options);

    return $instance;
})();

// NOTE: The anonymous function above is invoked immediately and its return
// value (the PDO object) becomes the return value of this file when it is
// required/included.  The static $instance ensures the connection is created
// only once per process even if the file is required multiple times.
//
// For a richer singleton with per-store connection pools, use:
//   App\Core\Database::getInstance()
