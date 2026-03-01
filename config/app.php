<?php

declare(strict_types=1);

/**
 * app.php — Application-level configuration.
 *
 * Loads the .env file (if not already loaded), defines application-wide
 * constants, and sets the PHP default timezone.
 *
 * This file must be required before any other application code runs.
 */

// ---------------------------------------------------------------------------
// Bootstrap Composer autoloader so phpdotenv is available.
// ---------------------------------------------------------------------------
$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// ---------------------------------------------------------------------------
// Load .env — only once per request.
// ---------------------------------------------------------------------------
if (!defined('ENV_LOADED')) {
    $dotenvPath = dirname(__DIR__);

    if (file_exists($dotenvPath . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
        $dotenv->load();

        // Validate that the critical keys are present.
        $dotenv->required([
            'APP_URL',
            'APP_ENV',
            'DB_HOST',
            'DB_NAME',
            'DB_USER',
            'JWT_SECRET',
        ]);
    }

    define('ENV_LOADED', true);
}

// ---------------------------------------------------------------------------
// Timezone — must be set before any date/time calls.
// ---------------------------------------------------------------------------
define('TIMEZONE', 'Asia/Kolkata');
date_default_timezone_set(TIMEZONE);

// ---------------------------------------------------------------------------
// Application constants.
// ---------------------------------------------------------------------------

/** Currency ISO code used throughout the application. */
define('CURRENCY', 'INR');

/** Human-readable currency symbol rendered in views. */
define('CURRENCY_SYMBOL', '₹');

/** Default number of records returned per paginated page. */
define('PER_PAGE', 25);

/** JWT signing secret from .env. */
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? '');

/** JWT access token lifetime in seconds (15 minutes). */
define('JWT_ACCESS_TTL', (int) ($_ENV['JWT_ACCESS_TTL'] ?? 900));

/** JWT refresh token lifetime in seconds (30 days). */
define('JWT_REFRESH_TTL', (int) ($_ENV['JWT_REFRESH_TTL'] ?? 2592000));

/** Canonical public URL of the application (no trailing slash). */
define('APP_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost/kinarahub', '/'));

/**
 * Execution environment.
 * Accepted values: development | staging | production
 */
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

// ---------------------------------------------------------------------------
// Error reporting — verbose in development, silent in production.
// ---------------------------------------------------------------------------
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}
