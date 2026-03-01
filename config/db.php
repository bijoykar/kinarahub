<?php

declare(strict_types=1);

/**
 * db.php — PDO database connection (singleton).
 *
 * Reads all connection parameters from environment variables so that the
 * same codebase runs in local development and production without any code
 * changes — only the .env file differs.
 *
 * LOCAL .env example:
 *   DB_HOST=localhost
 *   DB_NAME=kinarahub
 *   DB_USER=root
 *   DB_PASS=
 *   APP_ENV=development
 *
 * PRODUCTION .env example:
 *   DB_HOST=db.yourhost.com
 *   DB_NAME=kinarahub
 *   DB_USER=kinarahub_app
 *   DB_PASS=StrongPass@123
 *   DB_PORT=3306                  # optional, defaults to 3306
 *   DB_SSL_CA=/etc/ssl/mysql.pem  # optional, enables SSL verification
 *   APP_ENV=production
 *
 * Usage (from any file that already required config/app.php):
 *
 *   Preferred — use the singleton helper:
 *     $pdo = App\Core\Database::getInstance();
 *
 *   Direct require (returns the PDO instance):
 *     $pdo = require __DIR__ . '/db.php';
 */

return (static function (): PDO {
    // Static variable persists for the lifetime of the PHP process
    // (single web request under mod_php / PHP-FPM).
    static $instance = null;

    if ($instance !== null) {
        return $instance;
    }

    // ------------------------------------------------------------------
    // Connection parameters — all sourced from environment variables.
    // ------------------------------------------------------------------
    $host    = $_ENV['DB_HOST'] ?? 'localhost';
    $port    = (int) ($_ENV['DB_PORT'] ?? 3306);
    $name    = $_ENV['DB_NAME'] ?? 'kinarahub';
    $user    = $_ENV['DB_USER'] ?? 'root';
    $pass    = $_ENV['DB_PASS'] ?? '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$charset}' COLLATE 'utf8mb4_unicode_ci'",
    ];

    // ------------------------------------------------------------------
    // SSL/TLS — enabled in production when DB_SSL_CA is set.
    // In development (APP_ENV=development) SSL is skipped automatically.
    // ------------------------------------------------------------------
    $env   = $_ENV['APP_ENV'] ?? 'production';
    $sslCa = $_ENV['DB_SSL_CA'] ?? '';

    if ($env !== 'development' && $sslCa !== '') {
        $options[PDO::MYSQL_ATTR_SSL_CA]     = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $instance = new PDO($dsn, $user, $pass, $options);

    return $instance;
})();
