<?php
declare(strict_types=1);

/**
 * migrations/run.php -- Database migration runner.
 * Usage: php migrations/run.php
 *
 * Idempotent: safe to re-run. CREATE TABLE IF NOT EXISTS handles tables;
 * duplicate-key / duplicate-constraint errors (errno 1061, 1005, 121) are
 * treated as warnings, not failures.
 */

$root = dirname(__DIR__);

// Load .env
$envFile = $root . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Load config/app.php if it exists
$appConfig = $root . '/config/app.php';
if (file_exists($appConfig)) {
    require_once $appConfig;
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$name = $_ENV['DB_NAME'] ?? 'kinarahub';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$name}`");
    echo "Database '{$name}' ready.\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$migrationDir = __DIR__;
$files = glob($migrationDir . '/[0-9][0-9][0-9]_*.sql');
sort($files);

if (empty($files)) {
    echo "No migration files found.\n";
    exit(0);
}

// MySQL error codes that are safe to ignore on re-run
$idempotentCodes = [
    '42S01', // Table already exists
    'HY000', // General error — check driver code below
];
$idempotentDriverCodes = [1005, 1061, 1022]; // duplicate key/constraint/index

$errors = 0;
foreach ($files as $file) {
    $filename = basename($file);
    echo "Running {$filename}... ";

    $sql = file_get_contents($file);
    $statements = array_filter(
        array_map('trim', preg_split('/;\s*\n/', $sql)),
        fn($s) => !empty($s)
    );

    $fileFailed = false;
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        // Strip trailing semicolon if present
        $statement = rtrim($statement, ';');
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            $driverCode = (int) $e->errorInfo[1];
            if (in_array($driverCode, $idempotentDriverCodes)) {
                // Safe duplicate — skip silently
                continue;
            }
            echo "FAILED: " . $e->getMessage() . "\n";
            $fileFailed = true;
            $errors++;
            break;
        }
    }
    if (!$fileFailed) {
        echo "OK\n";
    }
}

echo "\n" . ($errors === 0 ? "All migrations completed successfully." : "{$errors} migration(s) failed.") . "\n";
exit($errors > 0 ? 1 : 0);
