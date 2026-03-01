<?php

declare(strict_types=1);

/**
 * admin_routes.php -- Platform admin panel route definitions.
 *
 * This file receives a fully constructed App\Core\Router instance bound to
 * $router.  All /admin/* routes are registered here.
 *
 * Routes are split into two groups:
 *   1. Public (login/logout) -- no middleware
 *   2. Protected -- AdminAuthMiddleware + CsrfMiddleware
 */

use App\Middleware\AdminAuthMiddleware;
use App\Middleware\CsrfMiddleware;

// ---------------------------------------------------------------------------
// Public admin routes (no auth required)
// ---------------------------------------------------------------------------

// Root redirect: /admin/ → /admin/login
$router->get('/', function () {
    header('Location: /kinarahub/admin/login');
    exit;
});

$router->get('/login',  'Admin\AdminAuthController@showLogin');
$router->post('/login', 'Admin\AdminAuthController@login', [CsrfMiddleware::class]);

// ---------------------------------------------------------------------------
// Protected admin routes (require $_SESSION['admin_id'])
// ---------------------------------------------------------------------------
$adminAuth = AdminAuthMiddleware::class;
$csrf      = CsrfMiddleware::class;

// Logout
$router->post('/logout', 'Admin\AdminAuthController@logout', [$adminAuth, $csrf]);

// Dashboard
$router->get('/dashboard', 'Admin\AdminDashboardController@index', [$adminAuth, $csrf]);

// Store management
$router->get('/stores',              'Admin\AdminStoreController@index',    [$adminAuth, $csrf]);
$router->get('/stores/:id',          'Admin\AdminStoreController@show',     [$adminAuth, $csrf]);
$router->post('/stores/:id/activate','Admin\AdminStoreController@activate', [$adminAuth, $csrf]);
$router->post('/stores/:id/suspend', 'Admin\AdminStoreController@suspend',  [$adminAuth, $csrf]);

// Impersonation
$router->post('/impersonate/:id',    'Admin\AdminImpersonateController@impersonate',     [$adminAuth, $csrf]);
$router->post('/exit-impersonate',   'Admin\AdminImpersonateController@exitImpersonate', [$adminAuth, $csrf]);
