<?php
declare(strict_types=1);

/**
 * migrations/seed_admin.php — Seeds the platform admin account.
 * Reads ADMIN_EMAIL and ADMIN_PASSWORD from .env, bcrypt-hashes the password,
 * and inserts via INSERT IGNORE (safe to re-run).
 *
 * Usage: php migrations/seed_admin.php
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

$host  = $_ENV['DB_HOST'] ?? 'localhost';
$name  = $_ENV['DB_NAME'] ?? 'kinarahub';
$user  = $_ENV['DB_USER'] ?? 'root';
$pass  = $_ENV['DB_PASS'] ?? '';
$email = $_ENV['ADMIN_EMAIL'] ?? 'admin@kinarahub.com';
$pw    = $_ENV['ADMIN_PASSWORD'] ?? 'Admin@123';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $hash = password_hash($pw, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT IGNORE INTO admins (email, password_hash, name) VALUES (?, ?, ?)');
    $stmt->execute([$email, $hash, 'Platform Admin']);

    if ($stmt->rowCount() > 0) {
        echo "Admin account seeded: {$email}\n";
    } else {
        echo "Admin '{$email}' already exists — skipped.\n";
    }
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
