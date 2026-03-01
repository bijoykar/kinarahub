<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * StoreModel — Database queries for the stores, staff, and related auth tables.
 *
 * All queries use PDO prepared statements. Tenant-scoped queries use
 * TenantScope where applicable, but most methods in this model operate
 * on the stores table itself (not tenant-scoped since a store IS a tenant).
 */
class StoreModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // -----------------------------------------------------------------------
    // Store CRUD
    // -----------------------------------------------------------------------

    /**
     * Insert a new store record (pending_verification).
     *
     * DB columns: name, owner_name, email, mobile, password_hash, status,
     *             verification_token, verification_token_expires_at
     *
     * @param array{name: string, owner_name: string, email: string, mobile: string, password_hash: string, verification_token: string, verification_token_expires_at: string} $data
     * @return int The new store ID.
     */
    public function createStore(array $data): int
    {
        $sql = 'INSERT INTO stores (name, owner_name, email, mobile, password_hash, status, verification_token, verification_token_expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['owner_name'],
            $data['email'],
            $data['mobile'],
            $data['password_hash'],
            'pending_verification',
            $data['verification_token'],
            $data['verification_token_expires_at'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Find a store by email address.
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM stores WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Find a store by its verification token.
     *
     * @return array<string, mixed>|null
     */
    public function findByVerificationToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM stores WHERE verification_token = ? LIMIT 1'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Find a store by ID.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM stores WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Activate a store: set status to 'active' and clear the verification token.
     */
    public function activateStore(int $storeId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE stores SET status = ?, verification_token = NULL, verification_token_expires_at = NULL WHERE id = ?'
        );
        $stmt->execute(['active', $storeId]);
    }

    /**
     * Update store setup fields (logo, address).
     *
     * @param int $storeId
     * @param array{address_street?: string, address_city?: string, address_state?: string, address_pincode?: string, logo_path?: string} $data
     */
    public function updateSetup(int $storeId, array $data): void
    {
        $fields = [];
        $params = [];

        foreach (['address_street', 'address_city', 'address_state', 'address_pincode', 'logo_path'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return;
        }

        $params[] = $storeId;
        $sql = 'UPDATE stores SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    // -----------------------------------------------------------------------
    // Role operations (used during verification to seed Owner role)
    // -----------------------------------------------------------------------

    /**
     * Create a role for a store.
     *
     * @return int The new role ID.
     */
    public function createRole(int $storeId, string $name, string $description = '', bool $isOwner = false): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO roles (store_id, name, description, is_owner) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$storeId, $name, $description, $isOwner ? 1 : 0]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Bulk-insert permissions for a role.
     *
     * @param int $roleId
     * @param array<int, array{module: string, action: string, allowed: int}> $permissions
     */
    public function insertRolePermissions(int $roleId, array $permissions): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO role_permissions (role_id, module, action, allowed) VALUES (?, ?, ?, ?)'
        );

        foreach ($permissions as $perm) {
            $stmt->execute([
                $roleId,
                $perm['module'],
                $perm['action'],
                $perm['allowed'],
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // Staff operations (used during verification to create the owner user)
    // -----------------------------------------------------------------------

    /**
     * Create a staff member for a store.
     *
     * @return int The new staff ID.
     */
    public function createStaff(int $storeId, string $name, string $email, string $mobile, string $passwordHash, int $roleId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO staff (store_id, name, email, mobile, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$storeId, $name, $email, $mobile, $passwordHash, $roleId, 'active']);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Find a staff member by email across all stores (used for login).
     * Joins with the stores table to check store status and get store name.
     *
     * @return array<string, mixed>|null
     */
    public function findStaffByEmailGlobal(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, st.status AS store_status, st.name AS store_name
             FROM staff s
             JOIN stores st ON st.id = s.store_id
             WHERE s.email = ? AND s.status = ?
             LIMIT 1'
        );
        $stmt->execute([$email, 'active']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    // -----------------------------------------------------------------------
    // Customer operations (used during verification to seed Walk-in Customer)
    // -----------------------------------------------------------------------

    /**
     * Seed the default Walk-in Customer for a store.
     */
    public function seedWalkInCustomer(int $storeId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO customers (store_id, name, is_default) VALUES (?, ?, ?)'
        );
        $stmt->execute([$storeId, 'Walk-in Customer', 1]);
    }

    /**
     * Return the PDO instance (for transactions).
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
