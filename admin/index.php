<?php

declare(strict_types=1);

/**
 * admin/index.php — Platform admin panel front controller.
 *
 * This is an entirely separate entry point from public/index.php.  It uses
 * its own session namespace (keyed by $_SESSION['admin_id']), its own route
 * file, and will eventually its own middleware stack that enforces the
 * super-admin role.
 *
 * Accessible at: http://localhost/kinarahub/admin/
 *
 * Request flow:
 *   Browser → Apache → admin/.htaccess → admin/index.php
 *           → Config (app, db) → Admin Router → Middleware → Admin Controller → Admin View
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
// 2. Application config — loads .env, timezone, constants, error reporting.
// ---------------------------------------------------------------------------
require_once dirname(__DIR__) . '/config/app.php';

// ---------------------------------------------------------------------------
// 3. Database — PDO singleton.
// ---------------------------------------------------------------------------
require_once dirname(__DIR__) . '/config/db.php';

// ---------------------------------------------------------------------------
// 4. Admin session — separate from store staff sessions.
//    Admin identity is stored in $_SESSION['admin_id'].
//    A dedicated cookie name avoids collisions with store sessions running
//    on the same domain.
// ---------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'name'            => 'KINARAHUB_ADMIN_SESSION',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_path'     => '/kinarahub/admin/',
        // In production over HTTPS flip this to true:
        'cookie_secure'   => (APP_ENV === 'production'),
        'use_strict_mode' => true,
    ]);
}

// ---------------------------------------------------------------------------
// 5. Build the Request object.
// ---------------------------------------------------------------------------
$request = new App\Core\Request();

// ---------------------------------------------------------------------------
// 6. Build the Router and load admin route definitions.
// ---------------------------------------------------------------------------
$router = new App\Core\Router();

require_once dirname(__DIR__) . '/config/admin_routes.php';

// ---------------------------------------------------------------------------
// 7. Dispatch — the router resolves the URI to an Admin controller method.
//    Admin middleware (see Phase 11) will check $_SESSION['admin_id'] and
//    enforce super-admin privileges before every admin route.
// ---------------------------------------------------------------------------
$router->dispatch($request);
