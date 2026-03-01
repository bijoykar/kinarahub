<?php

declare(strict_types=1);

/**
 * routes.php — Application route definitions.
 *
 * This file receives a fully constructed App\Core\Router instance (bound to
 * the variable $router) and registers all application routes.
 *
 * Routes will be expanded in Phase 3+
 *
 * Supported helpers:
 *   $router->get($path, $handler, $middlewares)
 *   $router->post($path, $handler, $middlewares)
 *   $router->put($path, $handler, $middlewares)
 *   $router->delete($path, $handler, $middlewares)
 *
 * Handler format: 'ControllerClass@method'  (class must live in App\Controllers)
 *                 or any valid PHP callable.
 *
 * Middleware format: array of fully-qualified class names that implement the
 *                    middleware interface (handle(Request, callable): void).
 */

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\PermissionMiddleware;

// ---------------------------------------------------------------------------
// Public routes (no authentication required)
// ---------------------------------------------------------------------------

$router->get('/', 'HomeController@index');

// ---------------------------------------------------------------------------
// Auth routes — Phase 3: Registration, Verification, Login, Logout, Setup
// ---------------------------------------------------------------------------

$router->get('/register', 'StoreController@showRegister');
$router->post('/register', 'StoreController@register', [CsrfMiddleware::class]);
$router->get('/verify/:token', 'StoreController@verifyEmail');
$router->get('/login', 'StoreController@showLogin');
$router->post('/login', 'StoreController@login', [CsrfMiddleware::class]);
$router->post('/logout', 'StoreController@logout', [AuthMiddleware::class]);
$router->get('/setup', 'StoreController@showSetup', [AuthMiddleware::class]);
$router->post('/setup', 'StoreController@saveSetup', [AuthMiddleware::class, CsrfMiddleware::class]);

// ---------------------------------------------------------------------------
// Authenticated routes — require login
// ---------------------------------------------------------------------------

$router->get('/dashboard', 'DashboardController@index', [AuthMiddleware::class]);

// ---------------------------------------------------------------------------
// Settings / RBAC routes — Phase 4: Roles & Staff management
// ---------------------------------------------------------------------------

// Roles
$router->get('/settings/roles', 'RoleController@index', [AuthMiddleware::class, new PermissionMiddleware('settings', 'read')]);
$router->get('/settings/roles/create', 'RoleController@create', [AuthMiddleware::class, new PermissionMiddleware('settings', 'create')]);
$router->post('/settings/roles', 'RoleController@store', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('settings', 'create')]);
$router->get('/settings/roles/:id/edit', 'RoleController@edit', [AuthMiddleware::class, new PermissionMiddleware('settings', 'update')]);
$router->post('/settings/roles/:id', 'RoleController@update', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('settings', 'update')]);
$router->post('/settings/roles/:id/delete', 'RoleController@destroy', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('settings', 'delete')]);

// Staff
$router->get('/settings/staff', 'StaffController@index', [AuthMiddleware::class, new PermissionMiddleware('settings', 'read')]);
$router->post('/settings/staff', 'StaffController@store', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('settings', 'create')]);
$router->post('/settings/staff/:id', 'StaffController@update', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('settings', 'update')]);
$router->post('/settings/staff/:id/toggle', 'StaffController@toggleStatus', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('settings', 'update')]);

// ---------------------------------------------------------------------------
// Inventory routes — Phase 5: Products & Categories
// ---------------------------------------------------------------------------

$router->get('/inventory', 'ProductController@index', [AuthMiddleware::class, new PermissionMiddleware('inventory', 'read')]);
$router->post('/inventory', 'ProductController@store', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('inventory', 'create')]);
$router->post('/inventory/:id', 'ProductController@update', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('inventory', 'update')]);
$router->post('/inventory/:id/delete', 'ProductController@destroy', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('inventory', 'delete')]);
$router->post('/inventory/import', 'ProductController@import', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('inventory', 'create')]);
$router->get('/inventory/export', 'ProductController@export', [AuthMiddleware::class, new PermissionMiddleware('inventory', 'read')]);

// Categories
$router->post('/inventory/categories', 'CategoryController@store', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('inventory', 'create')]);
$router->post('/inventory/categories/:id', 'CategoryController@update', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('inventory', 'update')]);
$router->post('/inventory/categories/:id/delete', 'CategoryController@destroy', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('inventory', 'delete')]);

// ---------------------------------------------------------------------------
// Sales routes — Phase 6: POS, Bookkeeping & Sales History
// ---------------------------------------------------------------------------

$router->get('/pos', 'SaleController@pos', [AuthMiddleware::class, new PermissionMiddleware('sales', 'create')]);
$router->get('/sales', 'SaleController@index', [AuthMiddleware::class, new PermissionMiddleware('sales', 'read')]);
$router->get('/sales/bookkeeping', 'SaleController@bookkeeping', [AuthMiddleware::class, new PermissionMiddleware('sales', 'create')]);
$router->get('/sales/:id', 'SaleController@show', [AuthMiddleware::class, new PermissionMiddleware('sales', 'read')]);
$router->post('/sales', 'SaleController@store', [AuthMiddleware::class, CsrfMiddleware::class, new PermissionMiddleware('sales', 'create')]);
