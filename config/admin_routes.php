<?php

declare(strict_types=1);

/**
 * admin_routes.php — Platform admin panel route definitions.
 *
 * This file receives a fully constructed App\Core\Router instance bound to
 * $router.  All /admin/* routes are registered here.
 *
 * All handlers in this file are protected by App\Middleware\AdminAuthMiddleware
 * which verifies $_SESSION['admin_id'] is set and belongs to a super-admin.
 *
 * Admin routes will be registered here in Phase 11.
 */

// ---------------------------------------------------------------------------
// Phase 11 — Admin routes registered here.
// ---------------------------------------------------------------------------
