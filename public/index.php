<?php

declare(strict_types=1);

/**
 * public/index.php — Web front controller.
 *
 * All HTTP requests for the web UI are funnelled through this file by the
 * root .htaccess.  The file bootstraps the application and hands off to the
 * router.
 *
 * Request flow:
 *   Browser → Apache → .htaccess → public/index.php
 *              → Config (app, db) → Router → Middleware → Controller → View
 */

// ---------------------------------------------------------------------------
// 1. Composer autoloader
// ---------------------------------------------------------------------------
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($autoloader)) {
    http_response_code(503);
    echo '<h1>Service Unavailable</h1>';
    echo '<p>Dependencies not installed. Run <code>composer install</code>.</p>';
    exit(1);
}

require_once $autoloader;

// ---------------------------------------------------------------------------
// 2. Application config — loads .env, sets timezone, defines constants,
//    and configures error reporting.
// ---------------------------------------------------------------------------
require_once dirname(__DIR__) . '/config/app.php';

// ---------------------------------------------------------------------------
// 3. Database — initialise PDO singleton (reads credentials from $_ENV).
//    The return value ($pdo) is available to anything that also requires the
//    file; controllers use App\Core\Database::getInstance() instead.
// ---------------------------------------------------------------------------
require_once dirname(__DIR__) . '/config/db.php';

// ---------------------------------------------------------------------------
// 4. PHP session — web requests are stateful.
//    session_regenerate_id(true) is called after successful login (in the
//    auth controller) to prevent session fixation.
// ---------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        // In production over HTTPS flip this to true:
        'cookie_secure'   => (APP_ENV === 'production'),
        'use_strict_mode' => true,
    ]);
}

// ---------------------------------------------------------------------------
// 5. Build the Request object (wraps $_GET, $_POST, $_SERVER, headers …)
// ---------------------------------------------------------------------------
$request = new App\Core\Request();

// ---------------------------------------------------------------------------
// 6. Build the Router and load all web route definitions.
// ---------------------------------------------------------------------------
$router = new App\Core\Router();

require_once dirname(__DIR__) . '/config/routes.php';

// ---------------------------------------------------------------------------
// 7. Dispatch — the router matches the URI, runs middleware stack, calls the
//    controller method, and the controller renders a view or redirects.
// ---------------------------------------------------------------------------
$router->dispatch($request);
