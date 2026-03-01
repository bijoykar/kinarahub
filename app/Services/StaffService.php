<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StaffModel;
use App\Models\RoleModel;

/**
 * StaffService — Business logic for staff member management.
 */
class StaffService
{
    private StaffModel $model;
    private RoleModel $roleModel;

    public function __construct()
    {
        $this->model = new StaffModel();
        $this->roleModel = new RoleModel();
    }

    /**
     * List all staff for a store.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listStaff(int $storeId): array
    {
        return $this->model->listForStore($storeId);
    }

    /**
     * Get all roles for a store (used for dropdowns).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listRoles(int $storeId): array
    {
        return $this->roleModel->listForStore($storeId);
    }

    /**
     * Create a new staff member.
     *
     * @return array{success: bool, errors: string[], staff_id: int|null}
     */
    public function createStaff(int $storeId, array $data): array
    {
        $errors = $this->validateStaff($data, $storeId);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'staff_id' => null];
        }

        // Verify role belongs to this store.
        $role = $this->roleModel->findById((int) $data['role_id'], $storeId);
        if ($role === null) {
            return ['success' => false, 'errors' => ['Selected role does not exist.'], 'staff_id' => null];
        }

        // Check email uniqueness within store.
        if ($this->model->emailExistsInStore($data['email'], $storeId)) {
            return ['success' => false, 'errors' => ['A staff member with this email already exists in your store.'], 'staff_id' => null];
        }

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $staffId = $this->model->create(
            $storeId,
            $data['name'],
            $data['email'],
            $data['mobile'] ?? '',
            $passwordHash,
            (int) $data['role_id']
        );

        return ['success' => true, 'errors' => [], 'staff_id' => $staffId];
    }

    /**
     * Update a staff member's details.
     *
     * @return array{success: bool, errors: string[]}
     */
    public function updateStaff(int $staffId, int $storeId, array $data): array
    {
        $staff = $this->model->findById($staffId, $storeId);
        if ($staff === null) {
            return ['success' => false, 'errors' => ['Staff member not found.']];
        }

        $errors = [];

        if (isset($data['name']) && strlen(trim($data['name'])) < 2) {
            $errors[] = 'Name is required (minimum 2 characters).';
        }

        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email address is required.';
            } elseif ($this->model->emailExistsInStore($data['email'], $storeId, $staffId)) {
                $errors[] = 'A staff member with this email already exists in your store.';
            }
        }

        if (isset($data['role_id'])) {
            $role = $this->roleModel->findById((int) $data['role_id'], $storeId);
            if ($role === null) {
                $errors[] = 'Selected role does not exist.';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->model->update($staffId, $storeId, $data);

        // Update password if provided.
        if (!empty($data['password']) && strlen($data['password']) >= 8) {
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
            $this->model->updatePassword($staffId, $storeId, $passwordHash);
        }

        return ['success' => true, 'errors' => []];
    }

    /**
     * Toggle a staff member's active/inactive status.
     *
     * @return array{success: bool, error: string|null, new_status: string|null}
     */
    public function toggleStatus(int $staffId, int $storeId, int $currentUserId): array
    {
        // Prevent self-deactivation.
        if ($staffId === $currentUserId) {
            return ['success' => false, 'error' => 'You cannot deactivate your own account.', 'new_status' => null];
        }

        $newStatus = $this->model->toggleStatus($staffId, $storeId);
        if ($newStatus === null) {
            return ['success' => false, 'error' => 'Staff member not found.', 'new_status' => null];
        }

        return ['success' => true, 'error' => null, 'new_status' => $newStatus];
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function validateStaff(array $data, int $storeId): array
    {
        $errors = [];

        if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
            $errors[] = 'Name is required (minimum 2 characters).';
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if (empty($data['role_id'])) {
            $errors[] = 'Role is required.';
        }

        return $errors;
    }
}
