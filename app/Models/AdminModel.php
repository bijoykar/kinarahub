<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * AdminModel -- Database queries for the admins table and platform-wide stats.
 *
 * Used exclusively by the Admin panel controllers. All queries use PDO
 * prepared statements.
 */
class AdminModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // -----------------------------------------------------------------------
    // Admin authentication
    // -----------------------------------------------------------------------

    /**
     * Find an admin user by email address.
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Find an admin user by ID.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    // -----------------------------------------------------------------------
    // Platform statistics (for admin dashboard)
    // -----------------------------------------------------------------------

    /**
     * Return platform-wide store statistics.
     *
     * @return array{total_stores: int, active_stores: int, pending_verification: int, suspended_stores: int, stores_this_month: int}
     */
    public function storeStats(): array
    {
        $sql = "SELECT
                    COUNT(*)                                              AS total_stores,
                    SUM(status = 'active')                                AS active_stores,
                    SUM(status = 'pending_verification')                  AS pending_verification,
                    SUM(status = 'suspended')                             AS suspended_stores,
                    SUM(created_at >= DATE_FORMAT(NOW(), '%Y-%m-01'))     AS stores_this_month
                FROM stores";

        $stmt = $this->pdo->query($sql);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_stores'         => (int) ($row['total_stores'] ?? 0),
            'active_stores'        => (int) ($row['active_stores'] ?? 0),
            'pending_verification' => (int) ($row['pending_verification'] ?? 0),
            'suspended_stores'     => (int) ($row['suspended_stores'] ?? 0),
            'stores_this_month'    => (int) ($row['stores_this_month'] ?? 0),
        ];
    }

    /**
     * Return total sales volume and revenue across all stores.
     *
     * @return array{total_sales_volume: int, total_revenue: float}
     */
    public function salesStats(): array
    {
        $sql = "SELECT
                    COUNT(*)              AS total_sales_volume,
                    COALESCE(SUM(total_amount), 0) AS total_revenue
                FROM sales";

        $stmt = $this->pdo->query($sql);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_sales_volume' => (int) ($row['total_sales_volume'] ?? 0),
            'total_revenue'      => (float) ($row['total_revenue'] ?? 0),
        ];
    }

    // -----------------------------------------------------------------------
    // Store management (paginated listing, status changes)
    // -----------------------------------------------------------------------

    /**
     * Return a paginated list of stores with optional search/status filter.
     *
     * @param  int    $page    1-based page number.
     * @param  int    $perPage Items per page.
     * @param  string $search  Search by name, owner_name, or email.
     * @param  string $status  Filter by status (active, pending_verification, suspended).
     * @return array{stores: array, total: int}
     */
    public function allStores(int $page = 1, int $perPage = 25, string $search = '', string $status = ''): array
    {
        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[]  = '(s.name LIKE ? OR s.owner_name LIKE ? OR s.email LIKE ?)';
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($status !== '' && in_array($status, ['active', 'pending_verification', 'suspended'], true)) {
            $where[]  = 's.status = ?';
            $params[] = $status;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total
        $countSql = "SELECT COUNT(*) FROM stores s {$whereClause}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch page
        $offset = ($page - 1) * $perPage;
        $dataSql = "SELECT s.id, s.name, s.owner_name, s.email, s.mobile, s.status, s.created_at,
                           s.logo_path, s.address_city, s.address_state
                    FROM stores s
                    {$whereClause}
                    ORDER BY s.created_at DESC
                    LIMIT ? OFFSET ?";

        $dataParams   = array_merge($params, [$perPage, $offset]);
        $dataStmt     = $this->pdo->prepare($dataSql);
        $dataStmt->execute($dataParams);
        $stores = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        return ['stores' => $stores, 'total' => $total];
    }

    /**
     * Return a single store with extended info (counts of staff, products, sales).
     *
     * @return array<string, mixed>|null
     */
    public function storeDetail(int $storeId): ?array
    {
        $sql = "SELECT s.*,
                       (SELECT COUNT(*) FROM staff st WHERE st.store_id = s.id) AS staff_count,
                       (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id) AS product_count,
                       (SELECT COUNT(*) FROM sales sa WHERE sa.store_id = s.id) AS total_sales
                FROM stores s
                WHERE s.id = ?
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$storeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Update a store's status.
     */
    public function updateStoreStatus(int $storeId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE stores SET status = ? WHERE id = ?');
        $stmt->execute([$status, $storeId]);
    }
}
