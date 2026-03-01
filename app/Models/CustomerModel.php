<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\TenantScope;
use PDO;

/**
 * CustomerModel — Database queries for customers, customer_credits, and credit_payments.
 *
 * All queries use PDO prepared statements.
 * Tenant-scoped via TenantScope::appendWhere() and TenantScope::apply().
 */
class CustomerModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // -----------------------------------------------------------------------
    // Customer CRUD
    // -----------------------------------------------------------------------

    /**
     * List customers for a store with pagination and search.
     * Excludes the default Walk-in Customer (is_default=1).
     *
     * @return array{customers: array, total: int}
     */
    public function listPaginated(int $storeId, int $page, int $perPage, string $search = ''): array
    {
        $where = ['c.is_default = 0'];
        $params = [];

        if ($search !== '') {
            $where[] = '(c.name LIKE ? OR c.mobile LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $filterClause = implode(' AND ', $where);

        // Count total.
        $countSql = "SELECT COUNT(*) FROM customers c WHERE {$filterClause}";
        $countSql = TenantScope::appendWhere($countSql, 'c');
        $countParams = $params;
        TenantScope::apply($countParams, $storeId);

        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($countParams);
        $total = (int) $stmt->fetchColumn();

        // Fetch paginated results.
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT c.*
                FROM customers c
                WHERE {$filterClause}";
        $sql = TenantScope::appendWhere($sql, 'c');
        $sql .= ' ORDER BY c.name ASC LIMIT ? OFFSET ?';

        $fetchParams = $params;
        TenantScope::apply($fetchParams, $storeId);
        $fetchParams[] = $perPage;
        $fetchParams[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($fetchParams);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['customers' => $customers, 'total' => $total];
    }

    /**
     * Find a customer by ID (excluding Walk-in).
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id, int $storeId): ?array
    {
        $sql = 'SELECT * FROM customers WHERE id = ? AND is_default = 0';
        $params = [$id];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        return $customer !== false ? $customer : null;
    }

    /**
     * Create a new customer.
     *
     * @return int The new customer ID.
     */
    public function create(int $storeId, array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO customers (store_id, name, mobile, email) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $storeId,
            $data['name'],
            $data['mobile'] ?: null,
            $data['email'] ?: null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Check if a mobile number already exists for a store.
     */
    public function mobileExistsInStore(int $storeId, string $mobile, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM customers WHERE mobile = ? AND is_default = 0';
        $params = [$mobile];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    // -----------------------------------------------------------------------
    // Credit history
    // -----------------------------------------------------------------------

    /**
     * Get credit history for a customer (with sale number join).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCreditHistory(int $customerId, int $storeId): array
    {
        $sql = 'SELECT cc.*, s.sale_number
                FROM customer_credits cc
                LEFT JOIN sales s ON s.id = cc.sale_id
                WHERE cc.customer_id = ?';
        $params = [$customerId];
        $sql = TenantScope::appendWhere($sql, 'cc');
        TenantScope::apply($params, $storeId);
        $sql .= ' ORDER BY cc.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get payment history for a customer.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPaymentHistory(int $customerId, int $storeId): array
    {
        $sql = 'SELECT * FROM credit_payments WHERE customer_id = ?';
        $params = [$customerId];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);
        $sql .= ' ORDER BY payment_date DESC, created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -----------------------------------------------------------------------
    // Record payment
    // -----------------------------------------------------------------------

    /**
     * Record a payment against a customer's outstanding credit.
     *
     * Applies payment to oldest unpaid credits first (FIFO).
     * Updates customer_credits rows, inserts credit_payments row,
     * and decrements the customer's outstanding_balance.
     *
     * Must be called within a transaction.
     */
    public function recordPayment(int $storeId, int $customerId, float $amount, string $method, ?string $notes): void
    {
        // 1. Get unpaid credit records (oldest first) for FIFO allocation.
        $sql = 'SELECT id, balance FROM customer_credits WHERE customer_id = ? AND balance > 0';
        $params = [$customerId];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);
        $sql .= ' ORDER BY created_at ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $remaining = $amount;

        foreach ($credits as $credit) {
            if ($remaining <= 0) {
                break;
            }

            $creditBalance = (float) $credit['balance'];
            $apply = min($remaining, $creditBalance);

            // Update credit record.
            $updateSql = 'UPDATE customer_credits SET amount_paid = amount_paid + ?, balance = balance - ? WHERE id = ?';
            $updateParams = [$apply, $apply, $credit['id']];
            $updateSql = TenantScope::appendWhere($updateSql);
            TenantScope::apply($updateParams, $storeId);

            $updateStmt = $this->pdo->prepare($updateSql);
            $updateStmt->execute($updateParams);

            // Insert credit_payment linked to this credit record.
            $payStmt = $this->pdo->prepare(
                'INSERT INTO credit_payments (store_id, customer_id, credit_id, amount_paid, payment_method, payment_date, notes)
                 VALUES (?, ?, ?, ?, ?, CURDATE(), ?)'
            );
            $payStmt->execute([$storeId, $customerId, $credit['id'], $apply, $method, $notes]);

            $remaining -= $apply;
        }

        // 2. Decrement customer outstanding_balance (with guard against negative balance).
        $balanceSql = 'UPDATE customers SET outstanding_balance = outstanding_balance - ? WHERE id = ? AND outstanding_balance >= ?';
        $balanceParams = [$amount, $customerId, $amount];
        $balanceSql = TenantScope::appendWhere($balanceSql);
        TenantScope::apply($balanceParams, $storeId);

        $stmt = $this->pdo->prepare($balanceSql);
        $stmt->execute($balanceParams);

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException("Failed to decrement outstanding balance for customer {$customerId}: insufficient balance or concurrent modification.");
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
