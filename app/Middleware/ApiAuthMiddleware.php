<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\Jwt;

/**
 * ApiAuthMiddleware — Validates JWT Bearer token for API requests.
 *
 * Decodes the access token from the Authorization header, verifies the
 * signature and expiry, then attaches store_id, staffId, and roleId to
 * the Request object. Returns 401 JSON on failure.
 */
class ApiAuthMiddleware
{
    public function handle(Request $request, callable $next): void
    {
        $token = $request->bearerToken();

        if ($token === null) {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Authentication required. Please provide a valid Bearer token.',
            ], 401);
        }

        $secret = defined('JWT_SECRET') ? JWT_SECRET : ($_ENV['JWT_SECRET'] ?? '');
        $payload = Jwt::decode($token, $secret);

        if ($payload === null) {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Invalid or expired token.',
            ], 401);
        }

        // Verify this is an access token (not a refresh token).
        if (($payload['type'] ?? '') !== 'access') {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Invalid token type.',
            ], 401);
        }

        // Attach tenant and user context to the request.
        $request->storeId = (int) ($payload['store_id'] ?? 0);
        $request->staffId = (int) ($payload['sub'] ?? 0);
        $request->roleId  = (int) ($payload['role_id'] ?? 0);

        if ($request->storeId === 0 || $request->staffId === 0) {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Invalid token payload.',
            ], 401);
        }

        $next();
    }
}
