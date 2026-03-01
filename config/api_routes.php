<?php

declare(strict_types=1);

/**
 * api_routes.php — REST API route definitions.
 *
 * This file receives a fully constructed App\Core\Router instance bound to
 * $router.  All /api/v1/* routes are registered here.
 *
 * All handlers in this file are expected to return JSON — never HTML.
 * Authentication is enforced via App\Middleware\ApiAuthMiddleware on every
 * protected route.
 *
 * Route paths are relative to the api/v1/ base (the Request path() method
 * strips the script directory prefix automatically).
 */

use App\Middleware\ApiAuthMiddleware;

// ---------------------------------------------------------------------------
// Public routes (no JWT required)
// ---------------------------------------------------------------------------

$router->post('/auth/login', 'Api\AuthApiController@login');
$router->post('/auth/refresh', 'Api\AuthApiController@refresh');

// ---------------------------------------------------------------------------
// Protected routes (JWT required)
// ---------------------------------------------------------------------------

$router->post('/auth/logout', 'Api\AuthApiController@logout', [ApiAuthMiddleware::class]);

// -- Dashboard --
$router->get('/dashboard', 'Api\DashboardApiController@summary', [ApiAuthMiddleware::class]);
$router->get('/dashboard/chart', 'Api\DashboardApiController@chart', [ApiAuthMiddleware::class]);

// -- Products --
$router->get('/products', 'Api\ProductApiController@index', [ApiAuthMiddleware::class]);
$router->get('/products/:id', 'Api\ProductApiController@show', [ApiAuthMiddleware::class]);
$router->post('/products', 'Api\ProductApiController@store', [ApiAuthMiddleware::class]);
$router->put('/products/:id', 'Api\ProductApiController@update', [ApiAuthMiddleware::class]);
$router->delete('/products/:id', 'Api\ProductApiController@destroy', [ApiAuthMiddleware::class]);

// -- Sales --
$router->get('/sales', 'Api\SaleApiController@index', [ApiAuthMiddleware::class]);
$router->get('/sales/:id', 'Api\SaleApiController@show', [ApiAuthMiddleware::class]);
$router->post('/sales', 'Api\SaleApiController@store', [ApiAuthMiddleware::class]);

// -- Customers --
$router->get('/customers', 'Api\CustomerApiController@index', [ApiAuthMiddleware::class]);
$router->post('/customers', 'Api\CustomerApiController@store', [ApiAuthMiddleware::class]);
$router->get('/customers/:id/credits', 'Api\CustomerApiController@credits', [ApiAuthMiddleware::class]);
$router->post('/customers/:id/payments', 'Api\CustomerApiController@recordPayment', [ApiAuthMiddleware::class]);
