<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\TenantScope;
use PDO;

/**
 * RoleModel — Database queries for roles, role_permissions, and role_field_restrictions.
 */
class RoleModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // -----------------------------------------------------------------------
    // Role CRUD
    // -----------------------------------------------------------------------

    /**
     * List all roles for the current store, with permission count and staff count.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForStore(int $storeId): array
    {
        $sql = 'SELECT r.id, r.name, r.description, r.is_owner,
                       (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id AND rp.allowed = 1) AS permission_count,
                       (SELECT COUNT(*) FROM staff s WHERE s.role_id = r.id AND s.store_id = r.store_id) AS staff_count
                FROM roles r
                WHERE r.store_id = ?
                ORDER BY r.is_owner DESC, r.name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$storeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a role by ID within a store.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $roleId, int $storeId): ?array
    {
        $sql = 'SELECT * FROM roles WHERE id = ? AND store_id = ? LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$roleId, $storeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Create a new role for a store.
     *
     * @return int The new role ID.
     */
    public function create(int $storeId, string $name, string $description = ''): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO roles (store_id, name, description, is_owner) VALUES (?, ?, ?, 0)'
        );
        $stmt->execute([$storeId, $name, $description]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update a role's name and description.
     */
    public function update(int $roleId, int $storeId, string $name, string $description = ''): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE roles SET name = ?, description = ? WHERE id = ? AND store_id = ?'
        );
        $stmt->execute([$name, $description, $roleId, $storeId]);
    }

    /**
     * Delete a role (only if not is_owner and no staff assigned).
     *
     * @return bool True if deleted, false if the role cannot be deleted.
     */
    public function delete(int $roleId, int $storeId): bool
    {
        // Check: cannot delete Owner role or a role with assigned staff.
        $role = $this->findById($roleId, $storeId);
        if ($role === null || (int) $role['is_owner'] === 1) {
            return false;
        }

        $staffCount = $this->getStaffCount($roleId, $storeId);
        if ($staffCount > 0) {
            return false;
        }

        // Delete permissions and field restrictions first.
        $this->pdo->prepare('DELETE FROM role_field_restrictions WHERE role_id = ?')->execute([$roleId]);
        $this->pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$roleId]);
        $this->pdo->prepare('DELETE FROM roles WHERE id = ? AND store_id = ?')->execute([$roleId, $storeId]);

        return true;
    }

    // -----------------------------------------------------------------------
    // Permissions
    // -----------------------------------------------------------------------

    /**
     * Get all permissions for a role as an associative array [module][action] => bool.
     *
     * @return array<string, array<string, bool>>
     */
    public function getPermissions(int $roleId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT module, action, allowed FROM role_permissions WHERE role_id = ?'
        );
        $stmt->execute([$roleId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $permissions = [];
        foreach ($rows as $row) {
            $permissions[$row['module']][$row['action']] = (int) $row['allowed'] === 1;
        }

        return $permissions;
    }

    /**
     * Replace all permissions for a role.
     *
     * @param int $roleId
     * @param array<string, array<string, bool>> $permissions [module][action] => allowed
     */
    public function setPermissions(int $roleId, array $permissions): void
    {
        // Delete existing permissions.
        $this->pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$roleId]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO role_permissions (role_id, module, action, allowed) VALUES (?, ?, ?, ?)'
        );

        $modules = ['inventory', 'sales', 'customers', 'reports', 'settings'];
        $actions = ['create', 'read', 'update', 'delete'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $allowed = !empty($permissions[$module][$action]) ? 1 : 0;
                $stmt->execute([$roleId, $module, $action, $allowed]);
            }
        }
    }

    // -----------------------------------------------------------------------
    // Field restrictions
    // -----------------------------------------------------------------------

    /**
     * Get field restrictions for a role as [field_key => hidden].
     *
     * @return array<string, bool>
     */
    public function getFieldRestrictions(int $roleId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT field_key, hidden FROM role_field_restrictions WHERE role_id = ?'
        );
        $stmt->execute([$roleId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $restrictions = [];
        foreach ($rows as $row) {
            $restrictions[$row['field_key']] = (int) $row['hidden'] === 1;
        }

        return $restrictions;
    }

    /**
     * Replace all field restrictions for a role.
     *
     * @param int $roleId
     * @param array<string, bool> $restrictions [field_key => hidden]
     */
    public function setFieldRestrictions(int $roleId, array $restrictions): void
    {
        $this->pdo->prepare('DELETE FROM role_field_restrictions WHERE role_id = ?')->execute([$roleId]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO role_field_restrictions (role_id, field_key, hidden) VALUES (?, ?, ?)'
        );

        $validKeys = ['cost_price', 'profit_margin', 'store_financials'];

        foreach ($validKeys as $key) {
            $hidden = !empty($restrictions[$key]) ? 1 : 0;
            $stmt->execute([$roleId, $key, $hidden]);
        }
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Count staff members assigned to a role within a store.
     */
    public function getStaffCount(int $roleId, int $storeId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM staff WHERE role_id = ? AND store_id = ?'
        );
        $stmt->execute([$roleId, $storeId]);

        return (int) $stmt->fetchColumn();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
