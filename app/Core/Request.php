<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Request — HTTP request abstraction.
 *
 * Wraps the PHP superglobals ($_GET, $_POST, $_FILES, $_SERVER, php://input)
 * and exposes a clean, type-safe interface for controller and middleware code.
 *
 * After the Router dispatches a request, it populates the public mutable
 * properties ($params, $storeId, $staffId, $roleId) so that downstream code
 * never has to reach directly into the superglobals for these values.
 */
class Request
{
    // -----------------------------------------------------------------------
    // Properties injected by the Router after successful route matching.
    // -----------------------------------------------------------------------

    /**
     * Named URL parameters extracted from the matched route pattern.
     *
     * Example: route "/products/:id" matched against "/products/42"
     *          produces ['id' => '42'].
     *
     * @var array<string, string>
     */
    public array $params = [];

    /**
     * ID of the store (tenant) associated with the current session or JWT.
     * Populated by authentication / tenant-resolution middleware.
     */
    public int|null $storeId = null;

    /**
     * ID of the authenticated staff member.
     * Populated by authentication middleware.
     */
    public int|null $staffId = null;

    /**
     * Role ID of the authenticated staff member.
     * Populated by authentication middleware.
     */
    public int|null $roleId = null;

    // -----------------------------------------------------------------------
    // Internal state
    // -----------------------------------------------------------------------

    /**
     * Lazily-decoded JSON body from php://input.
     * NULL means "not yet parsed"; an empty array means "parsed but empty".
     *
     * @var array<string, mixed>|null
     */
    private array|null $jsonBody = null;

    // -----------------------------------------------------------------------
    // HTTP method & path
    // -----------------------------------------------------------------------

    /**
     * Return the HTTP method in uppercase.
     *
     * Respects the X-HTTP-Method-Override header sent by some API clients and
     * HTML forms that cannot issue PUT/PATCH/DELETE requests directly.
     *
     * @return string  e.g. 'GET', 'POST', 'PUT', 'DELETE', 'PATCH'
     */
    public function method(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Allow method tunnelling via a custom header or hidden _method field.
        if ($method === 'POST') {
            $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']
                ?? $_POST['_method']
                ?? null;

            if ($override !== null) {
                $method = strtoupper(trim($override));
            }
        }

        return $method;
    }

    /**
     * Return the request path without the query string.
     *
     * The path is always normalised to start with "/" and never ends with "/"
     * (except for the root path itself).
     *
     * @return string  e.g. '/products/42'
     */
    public function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Strip query string.
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Strip the application sub-directory prefix when the app is installed
        // in a sub-folder (e.g. /kinarahub/).
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($scriptDir !== '' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }

        $path = '/' . ltrim($path, '/');

        // Remove trailing slash (except root).
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    // -----------------------------------------------------------------------
    // Input readers
    // -----------------------------------------------------------------------

    /**
     * Retrieve a value from the $_GET superglobal.
     *
     * @param  string $key
     * @param  mixed  $default  Returned when the key is absent.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Retrieve a value from the $_POST superglobal, with whitespace trimmed.
     *
     * Only string values are trimmed; arrays and other types are returned as-is.
     *
     * @param  string $key
     * @param  mixed  $default  Returned when the key is absent.
     * @return mixed
     */
    public function post(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $default;

        if (is_string($value)) {
            $value = trim($value);
        }

        return $value;
    }

    /**
     * Retrieve a value from a JSON-decoded php://input body.
     *
     * Suitable for API requests that send application/json payloads.
     * The body is decoded once and cached for the lifetime of the request.
     *
     * @param  string $key
     * @param  mixed  $default  Returned when the key is absent or body is not valid JSON.
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->jsonBody === null) {
            $this->jsonBody = $this->parseJsonBody();
        }

        return $this->jsonBody[$key] ?? $default;
    }

    /**
     * Return all POST data merged with JSON body data.
     *
     * JSON body values take precedence over POST values for the same key,
     * as API callers exclusively use the JSON body.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->jsonBody === null) {
            $this->jsonBody = $this->parseJsonBody();
        }

        $post = array_map(
            static fn (mixed $v): mixed => is_string($v) ? trim($v) : $v,
            $_POST
        );

        return array_merge($post, $this->jsonBody);
    }

    /**
     * Retrieve an uploaded file descriptor from $_FILES.
     *
     * Returns the raw entry from $_FILES (which is an associative array with
     * keys: name, type, tmp_name, error, size) or NULL if the key is absent.
     *
     * @param  string $key  The HTML input[name] attribute.
     * @return array{name: string, type: string, tmp_name: string, error: int, size: int}|null
     */
    public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;

        if ($file === null || !is_array($file)) {
            return null;
        }

        // Only return the entry when a file was actually uploaded.
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }

    // -----------------------------------------------------------------------
    // Header helpers
    // -----------------------------------------------------------------------

    /**
     * Retrieve a request header value by name.
     *
     * Header names are case-insensitive per RFC 7230.  Internally PHP stores
     * them in $_SERVER as HTTP_<UPPER_CASE_HEADER_NAME>.
     *
     * @param  string $key  The header name (e.g. 'Content-Type', 'X-Requested-With').
     * @return string|null  NULL when the header is not present.
     */
    public function header(string $key): ?string
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        $value     = $_SERVER[$serverKey] ?? null;

        // Special-case headers that PHP stores without the HTTP_ prefix.
        if ($value === null) {
            $value = match (strtolower($key)) {
                'content-type'   => $_SERVER['CONTENT_TYPE']   ?? null,
                'content-length' => $_SERVER['CONTENT_LENGTH'] ?? null,
                'authorization'  => $_SERVER['HTTP_AUTHORIZATION']
                                    ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                                    ?? null,
                default          => null,
            };
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Extract the token from an "Authorization: Bearer <token>" header.
     *
     * @return string|null  The raw token string, or NULL if the header is
     *                      absent or not a Bearer scheme.
     */
    public function bearerToken(): ?string
    {
        $authHeader = $this->header('Authorization');

        if ($authHeader === null) {
            return null;
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($authHeader, 7));

        return $token !== '' ? $token : null;
    }

    // -----------------------------------------------------------------------
    // Client metadata
    // -----------------------------------------------------------------------

    /**
     * Return the client's IP address.
     *
     * Prefers the X-Forwarded-For header (first entry) when the application
     * runs behind a reverse proxy.  Falls back to REMOTE_ADDR.
     *
     * NOTE: X-Forwarded-For can be spoofed.  If you need a trusted IP, ensure
     * the proxy is configured to strip and re-set this header, or use
     * REMOTE_ADDR directly.
     *
     * @return string
     */
    public function ip(): string
    {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;

        if ($forwarded !== null) {
            // X-Forwarded-For may be a comma-separated list; take the leftmost.
            $parts = explode(',', $forwarded);
            $ip    = trim($parts[0]);

            if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                return $ip;
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Decode the raw php://input stream as JSON.
     *
     * Returns an empty array on failure (invalid JSON, empty body, or
     * non-JSON content type).
     *
     * @return array<string, mixed>
     */
    private function parseJsonBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        // Only attempt JSON decoding when the client signals a JSON payload.
        if (!str_contains($contentType, 'application/json')) {
            return [];
        }

        $raw = file_get_contents('php://input');

        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
