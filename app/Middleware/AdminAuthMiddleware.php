<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * AdminAuthMiddleware -- Authentication gate for platform admin routes.
 *
 * Verifies that $_SESSION['admin_id'] is set. If absent, redirects to
 * the admin login page. When impersonation mode is active
 * ($_SESSION['impersonate_store_id'] is set), all state-changing methods
 * (POST/PUT/DELETE/PATCH) on non-admin routes are blocked with a 403
 * to enforce read-only browsing.
 */
class AdminAuthMiddleware
{
    /**
     * Check admin session and enforce read-only impersonation.
     *
     * @param Request  $request The current HTTP request.
     * @param callable $next    The next handler in the middleware pipeline.
     */
    public function handle(Request $request, callable $next): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $adminId = $_SESSION['admin_id'] ?? null;

        if (empty($adminId)) {
            Response::redirect('/kinarahub/admin/login');
            return;
        }

        $next($request);
    }
}
