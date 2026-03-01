<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Router — HTTP request dispatcher.
 *
 * Stores named routes with optional middleware chains, matches incoming
 * requests against registered patterns, extracts URL parameters, and invokes
 * the appropriate controller method or callable handler.
 *
 * Route patterns use a ":paramName" placeholder syntax:
 *
 *   $router->get('/products/:id',        'ProductController@show');
 *   $router->post('/stores/:storeId/products', 'ProductController@store', [AuthMiddleware::class]);
 *
 * Middleware must be fully-qualified class names of classes that expose a
 * public `handle(Request $request, callable $next): void` method.
 *
 * Controller handlers use the format "ClassName@methodName".  The class is
 * resolved from the App\Controllers namespace automatically; there is no need
 * to prefix it.  Raw callables are also accepted.
 */
class Router
{
    /**
     * Registered routes.
     *
     * Each entry is a tuple:
     *   [string $method, string $pattern, string|callable $handler, string[] $middlewares]
     *
     * @var list<array{0: string, 1: string, 2: string|callable, 3: list<string>}>
     */
    private array $routes = [];

    // -----------------------------------------------------------------------
    // Route registration
    // -----------------------------------------------------------------------

    /**
     * Register a GET route.
     *
     * @param  string          $path        URL pattern (e.g. '/products/:id').
     * @param  string|callable $handler     'ControllerClass@method' or callable.
     * @param  list<string>    $middlewares FQCN list of middleware classes.
     * @return void
     */
    public function get(string $path, string|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Register a POST route.
     *
     * @param  string          $path
     * @param  string|callable $handler
     * @param  list<string>    $middlewares
     * @return void
     */
    public function post(string $path, string|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Register a PUT route.
     *
     * @param  string          $path
     * @param  string|callable $handler
     * @param  list<string>    $middlewares
     * @return void
     */
    public function put(string $path, string|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    /**
     * Register a DELETE route.
     *
     * @param  string          $path
     * @param  string|callable $handler
     * @param  list<string>    $middlewares
     * @return void
     */
    public function delete(string $path, string|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    /**
     * Register a PATCH route.
     *
     * @param  string          $path
     * @param  string|callable $handler
     * @param  list<string>    $middlewares
     * @return void
     */
    public function patch(string $path, string|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middlewares);
    }

    // -----------------------------------------------------------------------
    // Dispatch
    // -----------------------------------------------------------------------

    /**
     * Match the incoming request against registered routes and execute the handler.
     *
     * Steps:
     *  1. Iterate routes registered for the request's HTTP method.
     *  2. Convert the route pattern to a regex and test it against the path.
     *  3. On match, extract named URL parameters and attach them to $request->params.
     *  4. Build the middleware pipeline around the final handler callable.
     *  5. Invoke the pipeline.
     *  6. If no route matches, call Response::notFound().
     *
     * @param  Request $request  The current HTTP request.
     * @return void
     */
    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path   = $request->path();

        foreach ($this->routes as [$routeMethod, $pattern, $handler, $middlewares]) {
            if ($routeMethod !== $method) {
                continue;
            }

            $params = [];
            if (!$this->matchPattern($pattern, $path, $params)) {
                continue;
            }

            // Attach extracted URL params to the request object.
            $request->params = $params;

            // Build the handler callable.
            $handlerCallable = $this->resolveHandler($handler);

            // Wrap with middleware pipeline and invoke.
            $pipeline = $this->buildPipeline($middlewares, $handlerCallable, $request);
            $pipeline();

            return;
        }

        // No route matched.
        Response::notFound();
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Store a route definition.
     *
     * @param  string          $method
     * @param  string          $path
     * @param  string|callable $handler
     * @param  list<string>    $middlewares
     * @return void
     */
    private function addRoute(string $method, string $path, string|callable $handler, array $middlewares): void
    {
        $this->routes[] = [strtoupper($method), $path, $handler, $middlewares];
    }

    /**
     * Convert a route pattern into a regex and attempt to match it.
     *
     * Named placeholders ":paramName" are converted to named capture groups
     * that allow letters, digits, hyphens, and underscores.
     *
     * A literal "*" at the end of a pattern matches any remaining path
     * (useful for catch-all routes).
     *
     * @param  string               $pattern  Route pattern (e.g. '/products/:id').
     * @param  string               $path     Actual request path.
     * @param  array<string,string> &$params  Populated with named captures on match.
     * @return bool                           TRUE when the pattern matches.
     */
    private function matchPattern(string $pattern, string $path, array &$params): bool
    {
        // Build a full regex from the route pattern.  Named ":paramName"
        // placeholders become (?P<paramName>[^/]+) capture groups.
        $regex = $this->buildRegex($pattern);

        if (preg_match($regex, $path, $matches) !== 1) {
            return false;
        }

        // Collect only the string-keyed (named) captures.
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return true;
    }

    /**
     * Build a full anchored regex from a route pattern.
     *
     * Algorithm:
     *  1. Split the pattern on ":paramName" placeholders using preg_split
     *     so we get purely literal segments between placeholders.
     *  2. Escape each literal segment with preg_quote.
     *  3. Re-join with named capture groups (?P<paramName>[^/]+).
     *
     * Example:
     *   pattern '/products/:id/reviews/:reviewId'
     *   result  '#^/products/(?P<id>[^/]+)/reviews/(?P<reviewId>[^/]+)/?$#u'
     *
     * @param  string $pattern  Route pattern (e.g. '/products/:id').
     * @return string           Full anchored PCRE regex.
     */
    private function buildRegex(string $pattern): string
    {
        // Match all :paramName tokens to collect names in order.
        $placeholderPattern = '/:([a-zA-Z_][a-zA-Z0-9_]*)/';
        preg_match_all($placeholderPattern, $pattern, $nameMatches);
        $paramNames = $nameMatches[1]; // e.g. ['id', 'reviewId']

        // Split the pattern on the placeholders; result is purely literal parts.
        $literalParts = preg_split($placeholderPattern, $pattern);

        // Reassemble: escape each literal part, then append the capture group
        // for the corresponding placeholder (if any).
        $regex = '';
        foreach ($literalParts as $index => $literal) {
            $regex .= preg_quote($literal, '#');

            if (isset($paramNames[$index])) {
                $regex .= '(?P<' . $paramNames[$index] . '>[^/]+)';
            }
        }

        // Strip a trailing slash from the compiled regex body (but keep the
        // root "/" intact) then allow an optional trailing slash in requests.
        if ($regex !== preg_quote('/', '#')) {
            $regex = rtrim($regex, '/');
        }

        return '#^' . $regex . '/?$#u';
    }

    /**
     * Resolve a "ClassName@method" string or callable into an invokable.
     *
     * When $handler is a string in "Class@method" format the class is first
     * looked up in App\Controllers; if not found there the string is treated
     * as a FQCN.
     *
     * @param  string|callable $handler
     * @return callable
     *
     * @throws RuntimeException When the controller class or method cannot be found.
     */
    private function resolveHandler(string|callable $handler): callable
    {
        if (is_callable($handler)) {
            return $handler;
        }

        if (!is_string($handler) || !str_contains($handler, '@')) {
            throw new RuntimeException(
                "Invalid route handler '{$handler}'. Expected 'ControllerClass@method' or a callable."
            );
        }

        [$class, $method] = explode('@', $handler, 2);

        // Try App\Controllers namespace first, then treat as FQCN.
        $fqcn = class_exists("App\\Controllers\\{$class}")
            ? "App\\Controllers\\{$class}"
            : $class;

        if (!class_exists($fqcn)) {
            throw new RuntimeException("Controller class '{$fqcn}' not found.");
        }

        $instance = new $fqcn();

        if (!method_exists($instance, $method)) {
            throw new RuntimeException(
                "Method '{$method}' not found on controller '{$fqcn}'."
            );
        }

        return [$instance, $method];
    }

    /**
     * Build a middleware pipeline that wraps the final handler.
     *
     * Each middleware receives the Request and a $next callable.  Calling
     * $next() advances to the next middleware (or the final handler).
     *
     * The pipeline is built from inside-out so the first middleware in the
     * array is the outermost layer (first to run, last to finish).
     *
     * @param  list<string> $middlewares  FQCN list of middleware classes.
     * @param  callable     $handler      The final route handler.
     * @param  Request      $request      The current request.
     * @return callable                   A zero-argument closure that runs the whole chain.
     */
    private function buildPipeline(array $middlewares, callable $handler, Request $request): callable
    {
        // Start with the innermost layer: just call the handler.
        $next = static function () use ($handler, $request): void {
            $handler($request);
        };

        // Wrap with middleware from right to left so the first middleware
        // in the array ends up being the outermost (runs first).
        // Each entry can be a class name (string) or a pre-constructed instance (object).
        foreach (array_reverse($middlewares) as $middleware) {
            if (is_object($middleware)) {
                $middlewareInstance = $middleware;
            } elseif (is_string($middleware) && class_exists($middleware)) {
                $middlewareInstance = new $middleware();
            } else {
                $label = is_string($middleware) ? $middleware : get_class($middleware);
                throw new RuntimeException("Middleware '{$label}' not found.");
            }

            if (!method_exists($middlewareInstance, 'handle')) {
                $label = get_class($middlewareInstance);
                throw new RuntimeException(
                    "Middleware '{$label}' must implement a public handle(Request, callable): void method."
                );
            }

            $currentNext = $next; // capture for closure

            $next = static function () use ($middlewareInstance, $request, $currentNext): void {
                $middlewareInstance->handle($request, $currentNext);
            };
        }

        return $next;
    }
}
