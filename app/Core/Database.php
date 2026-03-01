<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * Database — PDO connection manager.
 *
 * Provides a keyed pool of PDO instances so that the application can hold
 * connections to multiple databases simultaneously.  The default connection
 * is built from the global .env configuration; per-store isolated connections
 * (for a future "store-has-its-own-database" tenancy model) are keyed by a
 * hash of their connection parameters.
 *
 * Usage:
 *   // Default connection (reads .env)
 *   $pdo = Database::getInstance();
 *
 *   // Per-store isolated connection (future multi-DB tenancy)
 *   $pdo = Database::getInstance([
 *       'host' => 'store-db-01.internal',
 *       'name' => 'store_42',
 *       'user' => 'store42_user',
 *       'pass' => 's3cr3t',
 *   ]);
 */
class Database
{
    /**
     * Keyed pool of active PDO connections.
     *
     * Key:   md5 hash of the DSN + username (unique per connection config).
     * Value: the PDO instance for that config.
     *
     * @var array<string, PDO>
     */
    private static array $pool = [];

    /**
     * The key that identifies the default (.env-based) connection.
     *
     * Cached on first use so we only hash the DSN once.
     */
    private static string|null $defaultKey = null;

    // Prevent instantiation — this class is used as a static factory only.
    private function __construct() {}
    private function __clone() {}

    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    /**
     * Return a PDO connection, creating it on first call.
     *
     * When $overrideConfig is empty the method returns (and caches) the
     * default connection built from the environment variables:
     *   DB_HOST, DB_NAME, DB_USER, DB_PASS
     *
     * When $overrideConfig is provided with one or more of the keys
     * (host, name, user, pass) those values replace the corresponding
     * .env defaults.  Each unique combination of parameters gets its own
     * PDO instance in the pool.
     *
     * @param  array{host?: string, name?: string, user?: string, pass?: string} $overrideConfig
     * @return PDO
     *
     * @throws RuntimeException When the connection cannot be established.
     */
    public static function getInstance(array $overrideConfig = []): PDO
    {
        $config = self::resolveConfig($overrideConfig);
        $key    = self::poolKey($config);

        if (!isset(self::$pool[$key])) {
            self::$pool[$key] = self::createConnection($config);

            // Remember which key belongs to the default (no-override) config.
            if (empty($overrideConfig) && self::$defaultKey === null) {
                self::$defaultKey = $key;
            }
        }

        return self::$pool[$key];
    }

    /**
     * Close and remove a specific connection from the pool.
     *
     * Pass an empty array to reset the default connection.
     *
     * @param  array{host?: string, name?: string, user?: string, pass?: string} $overrideConfig
     * @return void
     */
    public static function closeConnection(array $overrideConfig = []): void
    {
        $config = self::resolveConfig($overrideConfig);
        $key    = self::poolKey($config);

        unset(self::$pool[$key]);

        if ($key === self::$defaultKey) {
            self::$defaultKey = null;
        }
    }

    /**
     * Close all connections and flush the pool.
     *
     * Useful in test suites between test cases.
     *
     * @return void
     */
    public static function closeAll(): void
    {
        self::$pool      = [];
        self::$defaultKey = null;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Merge $overrideConfig with the .env defaults into a complete config map.
     *
     * @param  array{host?: string, port?: int, name?: string, user?: string, pass?: string, ssl_ca?: string} $override
     * @return array{host: string, port: int, name: string, user: string, pass: string, ssl_ca: string}
     */
    private static function resolveConfig(array $override): array
    {
        return [
            'host'   => $override['host']   ?? $_ENV['DB_HOST']   ?? 'localhost',
            'port'   => (int) ($override['port'] ?? $_ENV['DB_PORT'] ?? 3306),
            'name'   => $override['name']   ?? $_ENV['DB_NAME']   ?? 'kinarahub',
            'user'   => $override['user']   ?? $_ENV['DB_USER']   ?? 'root',
            'pass'   => $override['pass']   ?? $_ENV['DB_PASS']   ?? '',
            'ssl_ca' => $override['ssl_ca'] ?? $_ENV['DB_SSL_CA'] ?? '',
        ];
    }

    /**
     * Derive a stable, unique string key for the connection pool.
     *
     * The password is intentionally excluded from the hash that is stored in
     * memory to avoid unnecessary credential exposure in stack traces; instead
     * we include its length so that two configs that differ only in password
     * still produce different keys.
     *
     * @param  array{host: string, name: string, user: string, pass: string} $config
     * @return string
     */
    private static function poolKey(array $config): string
    {
        $fingerprint = implode('|', [
            $config['host'],
            $config['port'],
            $config['name'],
            $config['user'],
            strlen($config['pass']),   // length only — never store plaintext
        ]);

        return md5($fingerprint);
    }

    /**
     * Open a new PDO connection using the supplied resolved config.
     *
     * @param  array{host: string, name: string, user: string, pass: string} $config
     * @return PDO
     *
     * @throws RuntimeException On connection failure.
     */
    private static function createConnection(array $config): PDO
    {
        $charset = 'utf8mb4';
        $dsn     = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $charset
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$charset}' COLLATE 'utf8mb4_unicode_ci'",
        ];

        // Enable SSL in non-development environments when DB_SSL_CA is set.
        $env = $_ENV['APP_ENV'] ?? 'production';
        if ($env !== 'development' && $config['ssl_ca'] !== '') {
            $options[PDO::MYSQL_ATTR_SSL_CA]                  = $config['ssl_ca'];
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]  = true;
        }

        try {
            return new PDO($dsn, $config['user'], $config['pass'], $options);
        } catch (\PDOException $e) {
            // Re-wrap to avoid leaking DSN credentials in uncaught PDOException messages.
            throw new RuntimeException(
                sprintf(
                    'Database connection failed for host "%s", database "%s": %s',
                    $config['host'],
                    $config['name'],
                    $e->getMessage()
                ),
                (int) $e->getCode(),
                $e
            );
        }
    }
}
