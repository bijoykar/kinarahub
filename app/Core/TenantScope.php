<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * TenantScope — multi-tenancy guard for store-scoped database queries.
 *
 * All tables that belong to a tenant (store) carry a `store_id` column.
 * This class centralises the injection of `store_id` into PDO parameter
 * arrays and SQL strings so that no tenant-scoped query can accidentally
 * leak data across stores.
 *
 * Current strategy — shared database, row-level isolation:
 *   Every SELECT / UPDATE / DELETE for a tenant-scoped table appends an
 *   `AND store_id = ?` clause, and `?` is filled by calling apply().
 *
 * Future: when store has its own DB, this class switches the PDO connection
 * instead of appending store_id filter.
 *
 * Usage example:
 *
 *   $sql    = 'SELECT * FROM products WHERE status = ?';
 *   $params = ['active'];
 *
 *   $sql = TenantScope::appendWhere($sql);              // adds 'AND store_id = ?'
 *   TenantScope::apply($params);                        // pushes store_id value
 *
 *   $stmt = $pdo->prepare($sql);
 *   $stmt->execute($params);
 */
class TenantScope
{
    // -----------------------------------------------------------------------
    // Store ID resolution
    // -----------------------------------------------------------------------

    /**
     * Return the store_id for the current request.
     *
     * Reads `$_SESSION['store_id']`.  When processing an API request the
     * authentication middleware is responsible for extracting the store_id
     * from the JWT payload and writing it to the session (or to a request-
     * scoped attribute read here).
     *
     * @return int  The active store ID.
     *
     * @throws RuntimeException When no store context has been established.
     */
    public static function currentStoreId(): int
    {
        // Start session if it hasn't been started yet (e.g. in CLI/test context).
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $storeId = $_SESSION['store_id'] ?? null;

        if ($storeId === null) {
            throw new RuntimeException(
                'No active store context. '
                . 'Ensure the session contains a valid store_id before calling TenantScope::currentStoreId().'
            );
        }

        if (!is_numeric($storeId) || (int) $storeId <= 0) {
            throw new RuntimeException(
                sprintf(
                    'Invalid store_id "%s" in session. Expected a positive integer.',
                    $storeId
                )
            );
        }

        return (int) $storeId;
    }

    // -----------------------------------------------------------------------
    // PDO parameter injection
    // -----------------------------------------------------------------------

    /**
     * Append the current (or supplied) store_id to a PDO positional-parameter array.
     *
     * Call this **after** you have assembled all other `?` placeholders in $params
     * so that the store_id binding aligns with the `AND store_id = ?` clause
     * appended by appendWhere().
     *
     * @param  array<mixed> &$params  The PDO parameter array — modified in place.
     * @param  int|null      $storeId  Explicit store ID; defaults to currentStoreId().
     * @return int                     The store_id that was appended.
     *
     * @throws RuntimeException When $storeId is null and no session store context exists.
     */
    public static function apply(array &$params, int|null $storeId = null): int
    {
        $storeId ??= self::currentStoreId();
        $params[]  = $storeId;

        return $storeId;
    }

    // -----------------------------------------------------------------------
    // SQL clause injection
    // -----------------------------------------------------------------------

    /**
     * Append an `AND store_id = ?` (or `WHERE store_id = ?`) clause to $sql.
     *
     * Detects whether a WHERE clause already exists in the query (case-
     * insensitive) and uses AND or WHERE accordingly.
     *
     * An optional $alias can be provided when the column is qualified with a
     * table alias (e.g. "p." for `p.store_id = ?`).
     *
     * @param  string $sql    The SQL string to augment.  Must not end with a
     *                        semicolon or ORDER BY / LIMIT clauses, as the
     *                        store_id filter is appended verbatim.
     * @param  string $alias  Optional table alias with dot, e.g. 'p.' or 'prod.'.
     * @return string         The modified SQL string.
     *
     * @example
     *   // Without alias
     *   $sql = 'SELECT * FROM products WHERE status = ?';
     *   $sql = TenantScope::appendWhere($sql);
     *   // → 'SELECT * FROM products WHERE status = ? AND store_id = ?'
     *
     *   // With alias
     *   $sql = 'SELECT p.* FROM products p';
     *   $sql = TenantScope::appendWhere($sql, 'p.');
     *   // → 'SELECT p.* FROM products p WHERE p.store_id = ?'
     */
    public static function appendWhere(string $sql, string $alias = ''): string
    {
        // Ensure alias ends with a dot when provided.
        if ($alias !== '' && !str_ends_with($alias, '.')) {
            $alias .= '.';
        }

        $storeIdColumn = $alias . 'store_id';

        // Case-insensitive check for an existing WHERE clause.
        // We look for the word "WHERE" surrounded by word boundaries (not part
        // of a column name or string literal in a simple statement).
        if (self::sqlHasWhere($sql)) {
            return rtrim($sql) . " AND {$storeIdColumn} = ?";
        }

        return rtrim($sql) . " WHERE {$storeIdColumn} = ?";
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Determine whether a SQL string already contains a top-level WHERE clause.
     *
     * Strips all parenthesised subexpressions (subqueries, function calls) before
     * checking, so that a WHERE inside a correlated subquery is not mistaken for
     * a WHERE in the outer query.
     *
     * @param  string $sql
     * @return bool
     */
    private static function sqlHasWhere(string $sql): bool
    {
        // Walk the string and collect only characters at nesting depth 0.
        $depth    = 0;
        $outerSql = '';
        $len      = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            if ($c === '(') {
                $depth++;
            } elseif ($c === ')') {
                $depth--;
            } elseif ($depth === 0) {
                $outerSql .= $c;
            }
        }

        return (bool) preg_match('/\bWHERE\b/i', $outerSql);
    }
}
