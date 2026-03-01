<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Response — HTTP response helpers.
 *
 * All methods are static so that controllers can call them without needing a
 * Response instance injected.  Every method that sends a complete response
 * terminates execution via exit() so that no additional output can leak after
 * the response is sent.
 *
 * View templates live in the `views/` directory relative to the project root.
 * Template files receive all keys from $data as local variables via extract().
 */
class Response
{
    // -----------------------------------------------------------------------
    // JSON responses
    // -----------------------------------------------------------------------

    /**
     * Send a raw JSON response and exit.
     *
     * @param  mixed $data  Any JSON-serialisable value.
     * @param  int   $code  HTTP status code (default 200).
     * @return never
     */
    public static function json(mixed $data, int $code = 200): never
    {
        self::setStatusCode($code);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        exit;
    }

    /**
     * Send a standardised API success envelope and exit.
     *
     * Response shape:
     * ```json
     * { "success": true, "data": ..., "meta": {...}, "error": null }
     * ```
     *
     * @param  mixed                $data  The primary response payload.
     * @param  array<string, mixed> $meta  Optional pagination / extra metadata.
     * @param  int                  $code  HTTP status code (default 200).
     * @return never
     */
    public static function apiSuccess(mixed $data, array $meta = [], int $code = 200): never
    {
        self::json([
            'success' => true,
            'data'    => $data,
            'meta'    => $meta ?: null,
            'error'   => null,
        ], $code);
    }

    /**
     * Send a standardised API error envelope and exit.
     *
     * Response shape:
     * ```json
     * { "success": false, "data": null, "meta": null, "error": "..." }
     * ```
     *
     * @param  string $message  Human-readable error description.
     * @param  int    $code     HTTP status code (default 400).
     * @return never
     */
    public static function apiError(string $message, int $code = 400): never
    {
        self::json([
            'success' => false,
            'data'    => null,
            'meta'    => null,
            'error'   => $message,
        ], $code);
    }

    // -----------------------------------------------------------------------
    // HTML / view responses
    // -----------------------------------------------------------------------

    /**
     * Render a PHP view template and send it as an HTML response.
     *
     * The $template string is a path relative to the `views/` directory,
     * without the `.php` extension.  For example:
     *
     *   Response::view('products/list', ['products' => $rows]);
     *
     * maps to  <project-root>/views/products/list.php
     *
     * All keys in $data are extracted into the template's local scope.
     *
     * @param  string               $template  Relative template path (no .php suffix).
     * @param  array<string, mixed> $data      Variables to expose in the template.
     * @param  int                  $code      HTTP status code (default 200).
     * @return never
     */
    public static function view(string $template, array $data = [], int $code = 200): never
    {
        $templatePath = self::resolveViewPath($template);

        self::setStatusCode($code);
        header('Content-Type: text/html; charset=utf-8');

        if (!file_exists($templatePath)) {
            // Avoid infinite recursion if the 404 template itself is missing.
            if ($template === 'errors/404') {
                http_response_code(404);
                echo '<h1>404 Not Found</h1><p>The requested page does not exist.</p>';
                exit;
            }

            self::notFound();
        }

        // Expose $data keys as local variables inside the template.
        extract($data, EXTR_SKIP);

        // Buffer the output so we can catch any rendering errors.
        ob_start();
        require $templatePath;
        $output = ob_get_clean();

        echo $output;

        exit;
    }

    // -----------------------------------------------------------------------
    // Redirect
    // -----------------------------------------------------------------------

    /**
     * Send an HTTP redirect response and exit.
     *
     * Root-relative paths (starting with "/") are automatically resolved to
     * absolute URLs using APP_URL so that sub-directory installs work correctly.
     *
     * Examples with APP_URL = "http://localhost/kinarahub":
     *   /dashboard               → http://localhost/kinarahub/dashboard
     *   /kinarahub/admin/stores  → http://localhost/kinarahub/admin/stores
     *   https://example.com      → https://example.com  (unchanged)
     *
     * @param  string $url   Target URL (absolute or root-relative).
     * @param  int    $code  HTTP status code — 301 (permanent) or 302 (temporary).
     * @return never
     */
    public static function redirect(string $url, int $code = 302): never
    {
        // Resolve root-relative paths to absolute URLs.
        if (str_starts_with($url, '/') && defined('APP_URL')) {
            $basePath = rtrim((string)(parse_url(APP_URL, PHP_URL_PATH) ?? ''), '/');

            if ($basePath !== '' && !str_starts_with($url, $basePath)) {
                // Short path (e.g. /dashboard) — prepend full APP_URL.
                $url = APP_URL . $url;
            } elseif ($basePath !== '') {
                // Already carries the base path (e.g. /kinarahub/admin/...) — make absolute.
                $scheme = (string)(parse_url(APP_URL, PHP_URL_SCHEME) ?? 'http');
                $host   = (string)(parse_url(APP_URL, PHP_URL_HOST)   ?? 'localhost');
                $url    = $scheme . '://' . $host . $url;
            }
        }

        self::setStatusCode($code);
        header('Location: ' . $url, true, $code);

        exit;
    }

    // -----------------------------------------------------------------------
    // Shorthand error responses
    // -----------------------------------------------------------------------

    /**
     * Send a 404 Not Found HTML response and exit.
     *
     * @return never
     */
    public static function notFound(): never
    {
        self::view('errors/404', [], 404);
    }

    /**
     * Send a 403 Forbidden HTML response and exit.
     *
     * @return never
     */
    public static function forbidden(): never
    {
        self::view('errors/403', [], 403);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Resolve a template name to an absolute filesystem path.
     *
     * Views are stored in <project-root>/views/.
     *
     * @param  string $template  Relative template path without .php suffix.
     * @return string            Absolute path including .php suffix.
     */
    private static function resolveViewPath(string $template): string
    {
        // Normalise: strip leading slashes and any injected ".." segments.
        $safe     = implode('/', array_filter(
            explode('/', str_replace('\\', '/', $template)),
            static fn (string $segment): bool => $segment !== '' && $segment !== '..'
        ));

        $viewsDir = dirname(__DIR__, 2) . '/views';

        return $viewsDir . '/' . $safe . '.php';
    }

    /**
     * Set the HTTP response status code, using http_response_code().
     *
     * @param  int $code
     * @return void
     */
    private static function setStatusCode(int $code): void
    {
        http_response_code($code);
    }
}
