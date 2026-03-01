<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * ProductModel — Database queries for products and product_variants.
 *
 * All queries use PDO prepared statements. Tenant-scoped via store_id parameter.
 */
class ProductModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // -----------------------------------------------------------------------
    // Product listing & search
    // -----------------------------------------------------------------------

    /**
     * List products for a store with pagination and filtering.
     *
     * @param int    $storeId
     * @param int    $page
     * @param int    $perPage
     * @param string $search     Search by name or SKU.
     * @param int    $categoryId Filter by category (0 = all).
     * @param string $status     Filter: 'active', 'inactive', 'low_stock', 'out_of_stock', '' = all.
     * @return array{products: array, total: int}
     */
    public function listPaginated(int $storeId, int $page, int $perPage, string $search = '', int $categoryId = 0, string $status = ''): array
    {
        $where = ['p.store_id = ?'];
        $params = [$storeId];

        if ($search !== '') {
            $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($categoryId > 0) {
            $where[] = 'p.category_id = ?';
            $params[] = $categoryId;
        }

        // Status filter: 'active' and 'inactive' are DB fields;
        // 'low_stock' and 'out_of_stock' are computed.
        if ($status === 'active' || $status === 'inactive') {
            $where[] = 'p.status = ?';
            $params[] = $status;
        } elseif ($status === 'out_of_stock') {
            $where[] = 'p.stock_quantity = 0';
            $where[] = "p.status = 'active'";
        } elseif ($status === 'low_stock') {
            $where[] = 'p.stock_quantity > 0';
            $where[] = 'p.stock_quantity <= p.reorder_point';
            $where[] = "p.status = 'active'";
        }

        $whereClause = implode(' AND ', $where);

        // Count total.
        $countSql = "SELECT COUNT(*) FROM products p WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // Fetch paginated results.
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT p.*, c.name AS category_name, u.name AS uom_name, u.abbreviation AS uom_abbr
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN units_of_measure u ON u.id = p.uom_id
                WHERE {$whereClause}
                ORDER BY p.updated_at DESC
                LIMIT ? OFFSET ?";

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compute stock_status for each product.
        foreach ($products as &$product) {
            $product['stock_status'] = $this->computeStockStatus(
                (float) $product['stock_quantity'],
                (float) $product['reorder_point']
            );
        }
        unset($product);

        return ['products' => $products, 'total' => $total];
    }

    // -----------------------------------------------------------------------
    // Product CRUD
    // -----------------------------------------------------------------------

    /**
     * Find a product by ID within a store.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id, int $storeId): ?array
    {
        $sql = 'SELECT p.*, c.name AS category_name, u.name AS uom_name, u.abbreviation AS uom_abbr
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN units_of_measure u ON u.id = p.uom_id
                WHERE p.id = ? AND p.store_id = ?
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $storeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $row['stock_status'] = $this->computeStockStatus(
            (float) $row['stock_quantity'],
            (float) $row['reorder_point']
        );

        return $row;
    }

    /**
     * Find a product by SKU within a store.
     *
     * @return array<string, mixed>|null
     */
    public function findBySku(string $sku, int $storeId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM products WHERE sku = ? AND store_id = ? LIMIT 1'
        );
        $stmt->execute([strtoupper($sku), $storeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Create a new product.
     *
     * @return int The new product ID.
     */
    public function create(int $storeId, array $data): int
    {
        $sql = 'INSERT INTO products (store_id, sku, name, category_id, uom_id, selling_price, cost_price, stock_quantity, reorder_point, status, version)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $storeId,
            strtoupper($data['sku']),
            $data['name'],
            $data['category_id'] ?: null,
            $data['uom_id'] ?: null,
            $data['selling_price'],
            $data['cost_price'],
            $data['stock_quantity'],
            $data['reorder_point'],
            $data['status'] ?? 'active',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update a product with optimistic locking.
     *
     * @return bool True if updated (version matched), false if version mismatch (409).
     */
    public function update(int $id, int $storeId, array $data, int $expectedVersion): bool
    {
        $fields = [];
        $params = [];

        $allowedFields = ['sku', 'name', 'category_id', 'uom_id', 'selling_price', 'cost_price', 'stock_quantity', 'reorder_point', 'status'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'sku') {
                    $fields[] = "sku = ?";
                    $params[] = strtoupper($data['sku']);
                } elseif ($field === 'category_id' || $field === 'uom_id') {
                    $fields[] = "{$field} = ?";
                    $params[] = $data[$field] ?: null;
                } else {
                    $fields[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return true;
        }

        // Increment version.
        $fields[] = 'version = version + 1';

        $params[] = $id;
        $params[] = $storeId;
        $params[] = $expectedVersion;

        $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ? AND store_id = ? AND version = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a product (soft: set status to inactive, or hard delete if no sales reference it).
     */
    public function deactivate(int $id, int $storeId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE products SET status = ? WHERE id = ? AND store_id = ?'
        );
        $stmt->execute(['inactive', $id, $storeId]);
    }

    // -----------------------------------------------------------------------
    // Bulk operations (CSV import)
    // -----------------------------------------------------------------------

    /**
     * Upsert a product by SKU.
     * If SKU exists: update all fields. If new: insert.
     *
     * @return string 'inserted' | 'updated'
     */
    public function upsertBySku(int $storeId, array $data): string
    {
        $existing = $this->findBySku($data['sku'], $storeId);

        if ($existing !== null) {
            // Update existing product (increment version).
            $this->update((int) $existing['id'], $storeId, $data, (int) $existing['version']);
            return 'updated';
        }

        $this->create($storeId, $data);
        return 'inserted';
    }

    // -----------------------------------------------------------------------
    // CSV export
    // -----------------------------------------------------------------------

    /**
     * Get all active products for a store (for CSV export).
     *
     * @return array<int, array<string, mixed>>
     */
    public function allForExport(int $storeId): array
    {
        $sql = 'SELECT p.sku, p.name, c.name AS category, u.name AS uom,
                       p.selling_price, p.cost_price, p.stock_quantity, p.reorder_point, p.status
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN units_of_measure u ON u.id = p.uom_id
                WHERE p.store_id = ?
                ORDER BY p.name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$storeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -----------------------------------------------------------------------
    // UOM helper
    // -----------------------------------------------------------------------

    /**
     * List all units of measure (platform-wide).
     *
     * @return array<int, array{id: int, name: string, abbreviation: string}>
     */
    public function listUnitsOfMeasure(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, abbreviation FROM units_of_measure ORDER BY name ASC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a UOM by name.
     *
     * @return array<string, mixed>|null
     */
    public function findUomByName(string $name): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM units_of_measure WHERE name = ? LIMIT 1');
        $stmt->execute([$name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    // -----------------------------------------------------------------------
    // Stock decrement (used by SaleService)
    // -----------------------------------------------------------------------

    /**
     * Atomically decrement stock with optimistic locking.
     *
     * @return bool True if stock was decremented (version matched).
     */
    public function decrementStock(int $productId, int $storeId, float $quantity, int $expectedVersion): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE products
             SET stock_quantity = stock_quantity - ?,
                 version = version + 1
             WHERE id = ? AND store_id = ? AND version = ? AND stock_quantity >= ?'
        );
        $stmt->execute([$quantity, $productId, $storeId, $expectedVersion, $quantity]);

        return $stmt->rowCount() > 0;
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Compute the stock status label from quantity and reorder point.
     */
    public function computeStockStatus(float $qty, float $reorderPoint): string
    {
        if ($qty == 0) {
            return 'out_of_stock';
        }
        if ($qty <= $reorderPoint) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
