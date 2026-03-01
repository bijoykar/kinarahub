<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
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
        $stmt = $this->pdo->prepare(
            'SELECT id, name FROM categories WHERE store_id = ? ORDER BY name ASC'
        );
        $stmt->execute([$storeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a category by ID within a store.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id, int $storeId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM categories WHERE id = ? AND store_id = ? LIMIT 1'
        );
        $stmt->execute([$id, $storeId]);
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
        $stmt = $this->pdo->prepare(
            'SELECT * FROM categories WHERE name = ? AND store_id = ? LIMIT 1'
        );
        $stmt->execute([$name, $storeId]);
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
        $stmt = $this->pdo->prepare(
            'UPDATE categories SET name = ? WHERE id = ? AND store_id = ?'
        );
        $stmt->execute([$name, $id, $storeId]);
    }

    /**
     * Delete a category (only if no products use it).
     *
     * @return bool True if deleted.
     */
    public function delete(int $id, int $storeId): bool
    {
        // Check if any products reference this category.
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM products WHERE category_id = ? AND store_id = ?'
        );
        $stmt->execute([$id, $storeId]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'DELETE FROM categories WHERE id = ? AND store_id = ?'
        );
        $stmt->execute([$id, $storeId]);

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
