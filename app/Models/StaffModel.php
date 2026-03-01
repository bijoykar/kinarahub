<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\TenantScope;
use PDO;

/**
 * StaffModel — Database queries for the staff table.
 *
 * All queries are tenant-scoped using TenantScope.
 */
class StaffModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * List all staff for a store, joined with role name.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForStore(int $storeId): array
    {
        $sql = 'SELECT s.id, s.name, s.email, s.mobile, s.status, s.created_at,
                       r.name AS role_name, r.id AS role_id
                FROM staff s
                LEFT JOIN roles r ON r.id = s.role_id';
        $params = [];
        $sql = TenantScope::appendWhere($sql, 's');
        TenantScope::apply($params, $storeId);
        $sql .= ' ORDER BY s.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a staff member by ID within a store.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $staffId, int $storeId): ?array
    {
        $sql = 'SELECT s.*, r.name AS role_name
                FROM staff s
                LEFT JOIN roles r ON r.id = s.role_id
                WHERE s.id = ?';
        $params = [$staffId];
        $sql = TenantScope::appendWhere($sql, 's');
        TenantScope::apply($params, $storeId);
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Create a new staff member.
     *
     * @return int The new staff ID.
     */
    public function create(int $storeId, string $name, string $email, string $mobile, string $passwordHash, int $roleId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO staff (store_id, name, email, mobile, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$storeId, $name, $email, $mobile, $passwordHash, $roleId, 'active']);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update a staff member's details (name, email, mobile, role_id).
     */
    public function update(int $staffId, int $storeId, array $data): void
    {
        $fields = [];
        $params = [];

        foreach (['name', 'email', 'mobile', 'role_id', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return;
        }

        $sql = 'UPDATE staff SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $params[] = $staffId;
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Update a staff member's password.
     */
    public function updatePassword(int $staffId, int $storeId, string $passwordHash): void
    {
        $sql = 'UPDATE staff SET password_hash = ? WHERE id = ?';
        $params = [$passwordHash, $staffId];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Toggle staff status (active/inactive).
     */
    public function toggleStatus(int $staffId, int $storeId): ?string
    {
        $staff = $this->findById($staffId, $storeId);
        if ($staff === null) {
            return null;
        }

        $newStatus = $staff['status'] === 'active' ? 'inactive' : 'active';

        $sql = 'UPDATE staff SET status = ? WHERE id = ?';
        $params = [$newStatus, $staffId];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $newStatus;
    }

    /**
     * Check if an email is already in use by another staff member in the same store.
     */
    public function emailExistsInStore(string $email, int $storeId, int $excludeId = 0): bool
    {
        $sql = 'SELECT COUNT(*) FROM staff WHERE email = ?';
        $params = [$email];

        if ($excludeId > 0) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
