<?php

declare(strict_types=1);

/**
 * api/v1/index.php — REST API front controller.
 *
 * All /api/v1/* requests are routed here by Apache.  This controller is
 * intentionally stateless — no PHP sessions are started.  Authentication is
 * handled via JWT Bearer tokens validated inside API middleware.
 *
 * Every response from this file MUST be valid JSON conforming to the
 * application envelope:
 *   { "success": bool, "data": mixed, "meta": object|null, "error": string|null }
 *
 * Request flow:
 *   Client → Apache → api/v1/.htaccess → api/v1/index.php
 *          → Config → Router → JWT Middleware → Controller → JSON response
 */

// ---------------------------------------------------------------------------
// Force JSON content-type immediately — even fatal errors should be JSON.
// ---------------------------------------------------------------------------
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');

// ---------------------------------------------------------------------------
// CORS headers for mobile app and cross-origin access.
// ---------------------------------------------------------------------------
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-HTTP-Method-Override');
header('Access-Control-Max-Age: 86400');

// Handle CORS preflight.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---------------------------------------------------------------------------
// 1. Composer autoloader
// ---------------------------------------------------------------------------
$autoloader = dirname(__DIR__, 2) . '/vendor/autoload.php';

if (!file_exists($autoloader)) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'data'    => null,
        'meta'    => null,
        'error'   => 'Service unavailable — dependencies not installed.',
    ], JSON_UNESCAPED_UNICODE);
    exit(1);
}

require_once $autoloader;

// ---------------------------------------------------------------------------
// 2. Application config — loads .env, constants, error reporting.
// ---------------------------------------------------------------------------
require_once dirname(__DIR__, 2) . '/config/app.php';

// ---------------------------------------------------------------------------
// 3. Database — PDO singleton.
// ---------------------------------------------------------------------------
require_once dirname(__DIR__, 2) . '/config/db.php';

// ---------------------------------------------------------------------------
// 4. NO session_start() — API is stateless.
//    Authentication is validated via JWT in App\Middleware\JwtMiddleware.
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// 5. Register a global exception handler so that any uncaught exception
//    returns a proper JSON 500 instead of an HTML error page.
// ---------------------------------------------------------------------------
set_exception_handler(function (Throwable $e): void {
    // Log the full exception server-side.
    error_log(sprintf(
        '[API] Uncaught %s in %s:%d — %s',
        get_class($e),
        $e->getFile(),
        $e->getLine(),
        $e->getMessage()
    ));

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data'    => null,
        'meta'    => null,
        'error'   => (APP_ENV === 'development')
            ? $e->getMessage()          // verbose in dev
            : 'Internal server error',  // safe in production
    ], JSON_UNESCAPED_UNICODE);
    exit(1);
});

// ---------------------------------------------------------------------------
// 6. Build the Request and Router, load API route definitions.
// ---------------------------------------------------------------------------
$request = new App\Core\Request();
$router  = new App\Core\Router();

require_once dirname(__DIR__, 2) . '/config/api_routes.php';

// ---------------------------------------------------------------------------
// 7. Dispatch — wrapped in try/catch as a final safety net.
//    The exception handler above also catches errors that escape this block.
// ---------------------------------------------------------------------------
try {
    // Buffer output so we can intercept HTML 404 from the Router
    // and replace it with a JSON 404 response.
    ob_start();
    $router->dispatch($request);
    $output = ob_get_clean();

    // If the Router returned an HTML 404 (no route matched), override with JSON.
    if (http_response_code() === 404 && !str_starts_with(trim($output), '{')) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'data'    => null,
            'meta'    => null,
            'error'   => 'Endpoint not found.',
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo $output;
    }
} catch (Throwable $e) {
    // Log and return JSON 500.
    error_log(sprintf(
        '[API] Dispatch exception: %s in %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data'    => null,
        'meta'    => null,
        'error'   => (APP_ENV === 'development')
            ? $e->getMessage()
            : 'Internal server error',
    ], JSON_UNESCAPED_UNICODE);
    exit(1);
}
