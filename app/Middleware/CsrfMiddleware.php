<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * CsrfMiddleware — Cross-Site Request Forgery protection for web routes.
 *
 * Behaviour per HTTP method:
 *
 *  GET  — Generates a fresh CSRF token and stores it in the session if
 *          one does not already exist.  The token is available via the
 *          static helpers token() and field() so that views can embed it
 *          in forms without touching $_SESSION directly.
 *
 *  POST / PUT / DELETE — Validates the _csrf_token field submitted with
 *          the request against the value stored in the session.  A
 *          mismatch (or absent token) results in a 403 JSON response; the
 *          pipeline is terminated immediately.
 *
 * The token is a 64-character hex string produced by bin2hex(random_bytes(32)),
 * giving 256 bits of entropy — far beyond the OWASP minimum.
 *
 * Session key:  $_SESSION['csrf_token']
 * Request field: _csrf_token (POST body)
 */
class CsrfMiddleware
{
    /**
     * Perform CSRF validation or generation, then advance the pipeline.
     *
     * @param Request  $request The current HTTP request object.
     * @param callable $next    The next handler in the middleware pipeline.
     *
     * @return void
     */
    public function handle(Request $request, callable $next): void
    {
        // Ensure a session is active.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $method = strtoupper($request->method());

        if ($method === 'GET') {
            // Generate a token only when one is absent — preserving the same
            // token across GET requests within a session is intentional so that
            // multiple open tabs in the same session all share one valid token.
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            $next($request);
            return;
        }

        // For state-changing methods, enforce token presence and equality.
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $submitted = $request->post('_csrf_token');
            $stored    = $_SESSION['csrf_token'] ?? null;

            // hash_equals is timing-safe (mitigates timing side-channel attacks).
            $valid = !empty($submitted)
                && !empty($stored)
                && hash_equals($stored, $submitted);

            if (!$valid) {
                Response::json(
                    ['success' => false, 'error' => 'Invalid CSRF token'],
                    403
                );
                return; // Defensive: Response::json() should exit.
            }
        }

        $next($request);
    }

    // -----------------------------------------------------------------------
    // Static convenience helpers — used directly in view templates.
    // -----------------------------------------------------------------------

    /**
     * Return the current session CSRF token string.
     *
     * Generates a new token if the session does not already hold one.
     * Call this helper inside view templates to read the token without
     * coupling the template to $_SESSION.
     *
     * @return string 64-character hex CSRF token.
     */
    public static function token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    /**
     * Return a ready-to-embed hidden HTML input carrying the CSRF token.
     *
     * Example output:
     *   <input type="hidden" name="_csrf_token" value="a3f...e9">
     *
     * Usage in a view file:
     *   <?= \App\Middleware\CsrfMiddleware::field() ?>
     *
     * The value is HTML-encoded via htmlspecialchars to prevent any
     * accidental XSS if the token somehow contains angle brackets.
     *
     * @return string HTML hidden input element (not terminated by a newline).
     */
    public static function field(): string
    {
        $token = self::token();
        $safe  = htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<input type="hidden" name="_csrf_token" value="' . $safe . '">';
    }
}
