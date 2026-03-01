<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RoleModel;

/**
 * RoleService — Business logic for RBAC role management.
 */
class RoleService
{
    private RoleModel $model;

    public function __construct()
    {
        $this->model = new RoleModel();
    }

    /**
     * List all roles for a store (enriched with permission_count, staff_count).
     * Maps is_owner to is_system for view compatibility.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listRoles(int $storeId): array
    {
        $roles = $this->model->listForStore($storeId);

        // Map is_owner -> is_system for the frontend view.
        foreach ($roles as &$role) {
            $role['is_system'] = (int) ($role['is_owner'] ?? 0);
        }
        unset($role);

        return $roles;
    }

    /**
     * Get a role with its permissions and field restrictions.
     *
     * @return array{role: array<string, mixed>, permissions: array<string, array<string, bool>>, fieldRestrictions: array<string, bool>}|null
     */
    public function getRoleWithPermissions(int $roleId, int $storeId): ?array
    {
        $role = $this->model->findById($roleId, $storeId);
        if ($role === null) {
            return null;
        }

        // Map is_owner -> is_system for the view.
        $role['is_system'] = (int) ($role['is_owner'] ?? 0);

        $permissions = $this->model->getPermissions($roleId);
        $fieldRestrictions = $this->model->getFieldRestrictions($roleId);

        return [
            'role'              => $role,
            'permissions'       => $permissions,
            'fieldRestrictions' => $fieldRestrictions,
        ];
    }

    /**
     * Create a new role with permissions and field restrictions.
     *
     * @return array{success: bool, errors: string[], role_id: int|null}
     */
    public function createRole(int $storeId, string $name, string $description, array $permissions, array $fieldRestrictions): array
    {
        $errors = $this->validateRole($name);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'role_id' => null];
        }

        $pdo = $this->model->getPdo();
        $pdo->beginTransaction();

        try {
            $roleId = $this->model->create($storeId, $name, $description);
            $this->model->setPermissions($roleId, $permissions);
            $this->model->setFieldRestrictions($roleId, $fieldRestrictions);

            $pdo->commit();

            return ['success' => true, 'errors' => [], 'role_id' => $roleId];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[RoleService] createRole failed: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to create role.'], 'role_id' => null];
        }
    }

    /**
     * Update an existing role's name, description, permissions, and field restrictions.
     *
     * @return array{success: bool, errors: string[]}
     */
    public function updateRole(int $roleId, int $storeId, string $name, string $description, array $permissions, array $fieldRestrictions): array
    {
        $role = $this->model->findById($roleId, $storeId);
        if ($role === null) {
            return ['success' => false, 'errors' => ['Role not found.']];
        }

        // Owner role: name cannot be changed.
        if ((int) $role['is_owner'] === 1) {
            $name = $role['name'];
        }

        $errors = $this->validateRole($name);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $pdo = $this->model->getPdo();
        $pdo->beginTransaction();

        try {
            $this->model->update($roleId, $storeId, $name, $description);
            $this->model->setPermissions($roleId, $permissions);
            $this->model->setFieldRestrictions($roleId, $fieldRestrictions);

            $pdo->commit();

            return ['success' => true, 'errors' => []];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[RoleService] updateRole failed: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to update role.']];
        }
    }

    /**
     * Delete a role.
     *
     * @return array{success: bool, error: string|null}
     */
    public function deleteRole(int $roleId, int $storeId): array
    {
        $role = $this->model->findById($roleId, $storeId);
        if ($role === null) {
            return ['success' => false, 'error' => 'Role not found.'];
        }

        if ((int) $role['is_owner'] === 1) {
            return ['success' => false, 'error' => 'The Owner role cannot be deleted.'];
        }

        $deleted = $this->model->delete($roleId, $storeId);
        if (!$deleted) {
            return ['success' => false, 'error' => 'Cannot delete a role that has staff members assigned.'];
        }

        return ['success' => true, 'error' => null];
    }

    /**
     * Check if a user has a specific permission.
     */
    public function hasPermission(int $roleId, string $module, string $action): bool
    {
        $permissions = $this->model->getPermissions($roleId);

        return !empty($permissions[$module][$action]);
    }

    /**
     * Get hidden field keys for a role.
     *
     * @return string[]
     */
    public function getHiddenFields(int $roleId): array
    {
        $restrictions = $this->model->getFieldRestrictions($roleId);
        $hidden = [];

        foreach ($restrictions as $key => $isHidden) {
            if ($isHidden) {
                $hidden[] = $key;
            }
        }

        return $hidden;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function validateRole(string $name): array
    {
        $errors = [];

        if (empty($name) || strlen(trim($name)) < 2) {
            $errors[] = 'Role name is required (minimum 2 characters).';
        }

        if (strlen($name) > 100) {
            $errors[] = 'Role name must not exceed 100 characters.';
        }

        return $errors;
    }
}
