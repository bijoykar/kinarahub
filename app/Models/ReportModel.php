<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\TenantScope;
use PDO;

/**
 * ReportModel — Database queries for reporting.
 *
 * All queries use PDO prepared statements.
 * Tenant-scoped via TenantScope::appendWhere() and TenantScope::apply().
 */
class ReportModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // -----------------------------------------------------------------------
    // Top Sellers
    // -----------------------------------------------------------------------

    /**
     * Top selling products ranked by revenue for a date range.
     *
     * @return array<int, array{product_name: string, sku: string, qty_sold: float, revenue: float, cogs: float, gross_profit: float}>
     */
    public function topSellers(int $storeId, string $from, string $to): array
    {
        $sql = "SELECT
                    si.product_name_snapshot AS product_name,
                    si.sku_snapshot AS sku,
                    SUM(si.quantity) AS qty_sold,
                    SUM(si.line_total) AS revenue,
                    SUM(si.cost_price_snapshot * si.quantity) AS cogs,
                    (SUM(si.line_total) - SUM(si.cost_price_snapshot * si.quantity)) AS gross_profit
                FROM sale_items si
                JOIN sales s ON s.id = si.sale_id
                WHERE s.sale_date BETWEEN ? AND ?";
        $params = [$from, $to];
        $sql = TenantScope::appendWhere($sql, 'si');
        TenantScope::apply($params, $storeId);
        $sql .= ' GROUP BY si.product_name_snapshot, si.sku_snapshot ORDER BY revenue DESC LIMIT 50';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -----------------------------------------------------------------------
    // Inventory Aging
    // -----------------------------------------------------------------------

    /**
     * Products with zero sales in the last N days.
     *
     * @return array<int, array{name: string, sku: string, category: string|null, stock_quantity: float, cost_price: float, stock_value: float, last_sale_date: string|null}>
     */
    public function inventoryAging(int $storeId, int $days): array
    {
        // Find products that have had NO sales in the last $days days.
        // We LEFT JOIN to sales within the window; if s.id IS NULL, there were no sales.
        // A separate subquery finds the actual last_sale_date across all time.
        $sql = "SELECT p.name, p.sku,
                       COALESCE(c.name, 'Uncategorized') AS category,
                       p.stock_quantity, p.cost_price,
                       (p.stock_quantity * p.cost_price) AS stock_value,
                       last_sales.last_sale_date
                FROM products p
                LEFT JOIN sale_items si ON si.product_id = p.id AND si.store_id = p.store_id
                LEFT JOIN sales s ON s.id = si.sale_id AND s.sale_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN (
                    SELECT si2.product_id, si2.store_id, MAX(s2.sale_date) AS last_sale_date
                    FROM sale_items si2
                    JOIN sales s2 ON s2.id = si2.sale_id
                    GROUP BY si2.product_id, si2.store_id
                ) last_sales ON last_sales.product_id = p.id AND last_sales.store_id = p.store_id
                WHERE s.id IS NULL AND p.status = ?";
        $params = [$days, 'active'];
        $sql = TenantScope::appendWhere($sql, 'p');
        TenantScope::apply($params, $storeId);
        $sql .= ' GROUP BY p.id ORDER BY p.stock_quantity DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -----------------------------------------------------------------------
    // Profit & Loss
    // -----------------------------------------------------------------------

    /**
     * Revenue, COGS, and gross profit for a date range.
     *
     * @return array{summary: array{revenue: float, cogs: float, gross_profit: float, gross_margin_pct: float}, breakdown: array}
     */
    public function profitAndLoss(int $storeId, string $from, string $to): array
    {
        // Summary totals
        $summarySql = "SELECT
                          SUM(si.line_total) AS revenue,
                          SUM(si.cost_price_snapshot * si.quantity) AS cogs
                       FROM sale_items si
                       JOIN sales s ON s.id = si.sale_id
                       WHERE s.sale_date BETWEEN ? AND ?";
        $summaryParams = [$from, $to];
        $summarySql = TenantScope::appendWhere($summarySql, 'si');
        TenantScope::apply($summaryParams, $storeId);

        $stmt = $this->pdo->prepare($summarySql);
        $stmt->execute($summaryParams);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $revenue     = (float) ($row['revenue'] ?? 0);
        $cogs        = (float) ($row['cogs'] ?? 0);
        $grossProfit = $revenue - $cogs;
        $marginPct   = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

        $summary = [
            'revenue'          => $revenue,
            'cogs'             => $cogs,
            'gross_profit'     => $grossProfit,
            'gross_margin_pct' => $marginPct,
        ];

        // Breakdown by category
        $breakdownSql = "SELECT
                            COALESCE(c.name, 'Uncategorized') AS category,
                            SUM(si.line_total) AS revenue,
                            SUM(si.cost_price_snapshot * si.quantity) AS cogs
                         FROM sale_items si
                         JOIN sales s ON s.id = si.sale_id
                         JOIN products p ON p.id = si.product_id
                         LEFT JOIN categories c ON c.id = p.category_id
                         WHERE s.sale_date BETWEEN ? AND ?";
        $breakdownParams = [$from, $to];
        $breakdownSql = TenantScope::appendWhere($breakdownSql, 'si');
        TenantScope::apply($breakdownParams, $storeId);
        $breakdownSql .= ' GROUP BY c.name ORDER BY revenue DESC';

        $stmt = $this->pdo->prepare($breakdownSql);
        $stmt->execute($breakdownParams);
        $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compute gross_profit and margin_pct per category.
        foreach ($breakdown as &$cat) {
            $catRevenue     = (float) $cat['revenue'];
            $catCogs        = (float) $cat['cogs'];
            $cat['gross_profit'] = $catRevenue - $catCogs;
            $cat['margin_pct']   = $catRevenue > 0 ? (($catRevenue - $catCogs) / $catRevenue) * 100 : 0;
        }
        unset($cat);

        return ['summary' => $summary, 'breakdown' => $breakdown];
    }

    // -----------------------------------------------------------------------
    // Customer Dues
    // -----------------------------------------------------------------------

    /**
     * Customers with outstanding credit balances.
     *
     * @return array<int, array{id: int, name: string, mobile: string|null, credit_total: float, amount_paid: float, balance: float}>
     */
    public function customerDues(int $storeId): array
    {
        $sql = "SELECT c.id, c.name, c.mobile,
                       COALESCE(SUM(cc.amount_due), 0) AS credit_total,
                       COALESCE(SUM(cc.amount_paid), 0) AS amount_paid,
                       c.outstanding_balance AS balance
                FROM customers c
                LEFT JOIN customer_credits cc ON cc.customer_id = c.id AND cc.store_id = c.store_id
                WHERE c.outstanding_balance > 0 AND c.is_default = 0";
        $params = [];
        $sql = TenantScope::appendWhere($sql, 'c');
        TenantScope::apply($params, $storeId);
        $sql .= ' GROUP BY c.id ORDER BY c.outstanding_balance DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -----------------------------------------------------------------------
    // GST Summary
    // -----------------------------------------------------------------------

    /**
     * GST summary grouped by month for a date range.
     *
     * @return array{results: array, totals: array{total_sales: float, total_tax: float}}
     */
    public function gstSummary(int $storeId, string $from, string $to): array
    {
        // Monthly breakdown
        $sql = "SELECT
                    DATE_FORMAT(s.sale_date, '%Y-%m') AS period,
                    SUM(s.total_amount) AS total_sales,
                    SUM(s.tax_amount) AS tax_amount
                FROM sales s
                WHERE s.sale_date BETWEEN ? AND ?";
        $params = [$from, $to];
        $sql = TenantScope::appendWhere($sql, 's');
        TenantScope::apply($params, $storeId);
        $sql .= " GROUP BY DATE_FORMAT(s.sale_date, '%Y-%m') ORDER BY period ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format period labels (e.g. "2026-03" → "Mar 2026")
        foreach ($results as &$row) {
            $ts = strtotime($row['period'] . '-01');
            if ($ts !== false) {
                $row['period'] = date('M Y', $ts);
            }
        }
        unset($row);

        // Totals
        $totalSales = 0;
        $totalTax   = 0;
        foreach ($results as $row) {
            $totalSales += (float) $row['total_sales'];
            $totalTax   += (float) $row['tax_amount'];
        }

        return [
            'results' => $results,
            'totals'  => [
                'total_sales' => $totalSales,
                'total_tax'   => $totalTax,
            ],
        ];
    }
}
