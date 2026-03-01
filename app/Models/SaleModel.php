<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\TenantScope;
use PDO;

/**
 * SaleModel — Database queries for sales and sale_items.
 *
 * All queries use PDO prepared statements.
 * Tenant-scoped via TenantScope::appendWhere() and TenantScope::apply().
 */
class SaleModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // -----------------------------------------------------------------------
    // Sale number generation
    // -----------------------------------------------------------------------

    /**
     * Generate the next sale number for a store (INV-00001 format).
     * Per-store sequence, not global.
     */
    public function generateSaleNumber(int $storeId): string
    {
        $sql = 'SELECT sale_number FROM sales WHERE store_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE';
        $params = [];
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $lastNumber = $stmt->fetchColumn();

        if ($lastNumber === false) {
            return 'INV-00001';
        }

        // Extract numeric part: INV-00001 -> 1
        $num = (int) substr($lastNumber, 4);
        $next = $num + 1;

        return 'INV-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    // -----------------------------------------------------------------------
    // Sale CRUD
    // -----------------------------------------------------------------------

    /**
     * Insert a sale record.
     *
     * @return int The new sale ID.
     */
    public function createSale(int $storeId, array $data): int
    {
        $sql = 'INSERT INTO sales (store_id, sale_number, sale_date, entry_mode, customer_id,
                                   payment_method, subtotal, tax_amount, total_amount, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $storeId,
            $data['sale_number'],
            $data['sale_date'],
            $data['entry_mode'],
            $data['customer_id'] ?: null,
            $data['payment_method'],
            $data['subtotal'],
            $data['tax_amount'],
            $data['total_amount'],
            $data['notes'] ?? null,
            $data['created_by'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a sale item with product snapshot.
     */
    public function createSaleItem(int $saleId, int $storeId, array $item): void
    {
        $sql = 'INSERT INTO sale_items (sale_id, store_id, product_id, variant_id,
                                        product_name_snapshot, sku_snapshot,
                                        quantity, unit_price, cost_price_snapshot, line_total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $saleId,
            $storeId,
            $item['product_id'] ?: null,
            $item['variant_id'] ?: null,
            $item['product_name_snapshot'],
            $item['sku_snapshot'],
            $item['quantity'],
            $item['unit_price'],
            $item['cost_price_snapshot'],
            $item['line_total'],
        ]);
    }

    // -----------------------------------------------------------------------
    // Sale listing & detail
    // -----------------------------------------------------------------------

    /**
     * List sales for a store with pagination and filters.
     *
     * @return array{sales: array, total: int}
     */
    public function listPaginated(int $storeId, int $page, int $perPage, string $search = '', string $from = '', string $to = '', string $paymentMethod = ''): array
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(s.sale_number LIKE ? OR c.name LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($from !== '') {
            $where[] = 's.sale_date >= ?';
            $params[] = $from;
        }

        if ($to !== '') {
            $where[] = 's.sale_date <= ?';
            $params[] = $to;
        }

        if ($paymentMethod !== '') {
            $where[] = 's.payment_method = ?';
            $params[] = $paymentMethod;
        }

        // Build base WHERE clause from filters, then append tenant scope.
        $filterClause = !empty($where) ? implode(' AND ', $where) : '1=1';

        // Count total.
        $countSql = "SELECT COUNT(*)
                     FROM sales s
                     LEFT JOIN customers c ON c.id = s.customer_id
                     WHERE {$filterClause}";
        $countSql = TenantScope::appendWhere($countSql, 's');
        $countParams = $params;
        TenantScope::apply($countParams, $storeId);

        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($countParams);
        $total = (int) $stmt->fetchColumn();

        // Fetch paginated results.
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT s.*, c.name AS customer_name, st.name AS created_by_name
                FROM sales s
                LEFT JOIN customers c ON c.id = s.customer_id
                LEFT JOIN staff st ON st.id = s.created_by
                WHERE {$filterClause}";
        $sql = TenantScope::appendWhere($sql, 's');
        $sql .= ' ORDER BY s.created_at DESC LIMIT ? OFFSET ?';

        $fetchParams = $params;
        TenantScope::apply($fetchParams, $storeId);
        $fetchParams[] = $perPage;
        $fetchParams[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($fetchParams);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['sales' => $sales, 'total' => $total];
    }

    /**
     * Get a sale by ID with its items, customer name, and creator name.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id, int $storeId): ?array
    {
        $sql = 'SELECT s.*, c.name AS customer_name, st.name AS created_by_name
                FROM sales s
                LEFT JOIN customers c ON c.id = s.customer_id
                LEFT JOIN staff st ON st.id = s.created_by
                WHERE s.id = ?';
        $params = [$id];
        $sql = TenantScope::appendWhere($sql, 's');
        TenantScope::apply($params, $storeId);
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sale === false) {
            return null;
        }

        // Fetch sale items.
        $itemsSql = 'SELECT * FROM sale_items WHERE sale_id = ?';
        $itemsParams = [$id];
        $itemsSql = TenantScope::appendWhere($itemsSql);
        TenantScope::apply($itemsParams, $storeId);
        $itemsSql .= ' ORDER BY id ASC';

        $itemsStmt = $this->pdo->prepare($itemsSql);
        $itemsStmt->execute($itemsParams);
        $sale['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        return $sale;
    }

    // -----------------------------------------------------------------------
    // Customer credit record
    // -----------------------------------------------------------------------

    /**
     * Create a customer credit record for a credit sale.
     */
    public function createCreditRecord(int $storeId, int $customerId, int $saleId, float $amount): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO customer_credits (store_id, customer_id, sale_id, amount_due, amount_paid, balance)
             VALUES (?, ?, ?, ?, 0.00, ?)'
        );
        $stmt->execute([$storeId, $customerId, $saleId, $amount, $amount]);

        // Update customer's outstanding balance.
        $sql = 'UPDATE customers SET outstanding_balance = outstanding_balance + ? WHERE id = ?';
        $params = [$amount, $customerId];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    // -----------------------------------------------------------------------
    // Default customer helper
    // -----------------------------------------------------------------------

    /**
     * Get the default Walk-in Customer for a store.
     *
     * @return int|null The customer ID, or null if not found.
     */
    public function getDefaultCustomerId(int $storeId): ?int
    {
        $sql = 'SELECT id FROM customers WHERE is_default = 1';
        $params = [];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    /**
     * List all customers for a store (for dropdowns).
     *
     * @return array<int, array{id: int, name: string, mobile: string|null}>
     */
    public function listCustomers(int $storeId): array
    {
        $sql = 'SELECT id, name, mobile FROM customers WHERE 1=1';
        $params = [];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);
        $sql .= ' ORDER BY is_default DESC, name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
