<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use PDO;

/**
 * PermissionMiddleware — RBAC enforcement for individual route actions.
 *
 * Each route that requires a specific permission instantiates this
 * middleware with the target module and action:
 *
 *   new PermissionMiddleware('inventory', 'create')
 *   new PermissionMiddleware('reports',   'view')
 *
 * The middleware looks up the `role_permissions` table using the
 * $request->roleId set by AuthMiddleware (which must run first in the
 * pipeline).  If no matching row is found, or if `allowed = 0`, the
 * request is terminated with a 403 Forbidden response.
 *
 * Supported modules:  inventory | sales | customers | reports | settings
 * Supported actions:  view | create | update | delete | export
 *
 * Schema expected:
 *   role_permissions (role_id INT, module VARCHAR, action VARCHAR, allowed TINYINT)
 *   role_field_restrictions (role_id INT, field_key VARCHAR)
 *
 * Sensitive field_keys that may be restricted:
 *   cost_price | profit_margin | store_financials
 */
class PermissionMiddleware
{
    /** @var string The application module being accessed (e.g. 'inventory'). */
    private string $module;

    /** @var string The action being performed (e.g. 'create'). */
    private string $action;

    /**
     * Construct a new permission gate for the given module and action.
     *
     * @param string $module The application module (e.g. 'inventory', 'sales').
     * @param string $action The action within the module (e.g. 'view', 'create').
     */
    public function __construct(string $module, string $action)
    {
        $this->module = $module;
        $this->action = $action;
    }

    /**
     * Enforce the role-based permission check for this module/action pair.
     *
     * Requires that AuthMiddleware has already run and set $request->roleId.
     * Queries role_permissions with a prepared statement (PDO only — no
     * inline interpolation).  Terminates with 403 if the permission is
     * missing or explicitly denied.
     *
     * @param Request  $request The current HTTP request, hydrated by AuthMiddleware.
     * @param callable $next    The next handler in the middleware pipeline.
     *
     * @return void
     */
    public function handle(Request $request, callable $next): void
    {
        $roleId = $request->roleId ?? 0;

        if ($roleId === 0) {
            // No role means no access — AuthMiddleware should have prevented
            // reaching here, but we guard defensively.
            Response::forbidden();
            return;
        }

        $pdo = $this->getPdo();

        $stmt = $pdo->prepare(
            'SELECT allowed
               FROM role_permissions
              WHERE role_id = ?
                AND module  = ?
                AND action  = ?
              LIMIT 1'
        );

        $stmt->execute([$roleId, $this->module, $this->action]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // No row found → permission not defined → deny.
        // Row found with allowed = 0 → explicitly denied.
        if ($row === false || (int) $row['allowed'] !== 1) {
            Response::forbidden();
            return;
        }

        $next($request);
    }

    // -----------------------------------------------------------------------
    // Static utility helpers
    // -----------------------------------------------------------------------

    /**
     * Return the list of restricted field keys for a given role.
     *
     * Controllers use this to strip sensitive fields from API responses or
     * view data before they are rendered.  The result array may contain
     * any of: 'cost_price', 'profit_margin', 'store_financials'.
     *
     * @param int $roleId The RBAC role ID (from session or JWT payload).
     * @param PDO $pdo    An active PDO connection for the current tenant.
     *
     * @return string[] Array of field_key strings that must be hidden for this role.
     *                  Returns an empty array when the role has no restrictions.
     */
    public static function getRestrictedFields(int $roleId, PDO $pdo): array
    {
        if ($roleId === 0) {
            return [];
        }

        $stmt = $pdo->prepare(
            'SELECT field_key
               FROM role_field_restrictions
              WHERE role_id = ?
                AND hidden = 1'
        );

        $stmt->execute([$roleId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        // fetchAll returns an empty array when there are no rows, so this is
        // always safe to return directly.
        return is_array($rows) ? $rows : [];
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Resolve the PDO instance from the application's database configuration.
     *
     * Requires config/db.php to have been bootstrapped before the middleware
     * is invoked (done by public/index.php at request start).
     *
     * @return PDO Active database connection.
     */
    private function getPdo(): PDO
    {
        return \App\Core\Database::getInstance();
    }
}
