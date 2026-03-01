<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * AuthMiddleware — Session-based authentication gate for web routes.
 *
 * Every web route that requires a logged-in store staff member must
 * pass through this middleware before reaching the controller.  The
 * middleware reads the PHP session populated by the login controller
 * and short-circuits with a redirect to /login when the session is
 * absent or incomplete.
 *
 * After a successful check, three properties are written onto the
 * $request object so that downstream middleware and controllers can
 * read them without touching $_SESSION directly:
 *
 *   $request->storeId  — the authenticated store's ID
 *   $request->staffId  — the authenticated staff member's user ID
 *   $request->roleId   — the staff member's RBAC role ID
 *
 * store_id is NEVER accepted from the request body — it is always
 * sourced from the session (CLAUDE.md architecture rule).
 */
class AuthMiddleware
{
    /**
     * Authenticate the request against the active PHP session.
     *
     * Checks that both $_SESSION['store_id'] and $_SESSION['user_id']
     * are present and non-empty.  If either is missing the visitor is
     * redirected to /login immediately and execution stops.
     *
     * When the session is valid the method hydrates three properties on
     * the Request object, then hands control to the next middleware or
     * controller in the pipeline by invoking $next($request).
     *
     * @param Request  $request The current HTTP request object.
     * @param callable $next    The next handler in the middleware pipeline.
     *
     * @return void
     */
    public function handle(Request $request, callable $next): void
    {
        // Ensure a session is active before inspecting it.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $storeId = $_SESSION['store_id'] ?? null;
        $userId  = $_SESSION['user_id']  ?? null;

        // Both identifiers must be present and truthy (non-zero integers).
        if (empty($storeId) || empty($userId)) {
            Response::redirect('/login');
            return; // Defensive: redirect() should exit, but be explicit.
        }

        // Hydrate request properties so downstream handlers never need
        // to touch $_SESSION directly (single source of truth in pipeline).
        $request->storeId = (int) $storeId;
        $request->staffId = (int) $userId;
        $request->roleId  = isset($_SESSION['role_id']) ? (int) $_SESSION['role_id'] : 0;

        // Pass control to the next middleware / controller.
        $next($request);
    }
}
