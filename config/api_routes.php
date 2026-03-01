<?php

declare(strict_types=1);

/**
 * api_routes.php — REST API route definitions.
 *
 * This file receives a fully constructed App\Core\Router instance bound to
 * $router.  All /api/v1/* routes are registered here.
 *
 * All handlers in this file are expected to return JSON — never HTML.
 * Authentication is enforced via App\Middleware\JwtMiddleware on every
 * protected route.
 *
 * API routes will be registered here in Phase 10.
 */

// ---------------------------------------------------------------------------
// Phase 10 — API routes registered here.
// ---------------------------------------------------------------------------
