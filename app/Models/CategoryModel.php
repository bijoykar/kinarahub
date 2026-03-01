<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\TenantScope;
use PDO;

/**
 * CategoryModel — Database queries for the categories table.
 */
class CategoryModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * List all categories for a store.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function listForStore(int $storeId): array
    {
        $sql = 'SELECT id, name FROM categories';
        $params = [];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);
        $sql .= ' ORDER BY name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a category by ID within a store.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id, int $storeId): ?array
    {
        $sql = 'SELECT * FROM categories WHERE id = ?';
        $params = [$id];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Find a category by name within a store.
     *
     * @return array<string, mixed>|null
     */
    public function findByName(string $name, int $storeId): ?array
    {
        $sql = 'SELECT * FROM categories WHERE name = ?';
        $params = [$name];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Create a new category.
     *
     * @return int The new category ID.
     */
    public function create(int $storeId, string $name): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categories (store_id, name) VALUES (?, ?)'
        );
        $stmt->execute([$storeId, $name]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update a category name.
     */
    public function update(int $id, int $storeId, string $name): void
    {
        $sql = 'UPDATE categories SET name = ? WHERE id = ?';
        $params = [$name, $id];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Delete a category (only if no products use it).
     *
     * @return bool True if deleted.
     */
    public function delete(int $id, int $storeId): bool
    {
        // Check if any products reference this category.
        $sql = 'SELECT COUNT(*) FROM products WHERE category_id = ?';
        $params = [$id];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }

        $sql = 'DELETE FROM categories WHERE id = ?';
        $params = [$id];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return true;
    }

    /**
     * Find or create a category by name (used for CSV import).
     *
     * @return int The category ID.
     */
    public function findOrCreate(int $storeId, string $name): int
    {
        $existing = $this->findByName($name, $storeId);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return $this->create($storeId, $name);
    }
}
