<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\TenantScope;
use PDO;

/**
 * ReportService -- Business report queries for the reports module.
 *
 * All queries are tenant-scoped via TenantScope.
 * Reports: Top Sellers, Inventory Aging, Profit & Loss, Customer Dues, GST Summary.
 */
class ReportService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // -----------------------------------------------------------------------
    // 1. Top Sellers Report
    // -----------------------------------------------------------------------

    /**
     * Products ranked by quantity sold, revenue, and profit for a date range.
     *
     * @return array<int, array{product_name: string, sku: string, qty_sold: float, revenue: float, cogs: float, gross_profit: float, margin_pct: float}>
     */
    public function topSellers(int $storeId, string $from, string $to): array
    {
        $sql = 'SELECT
                    si.product_name_snapshot AS product_name,
                    si.sku_snapshot AS sku,
                    SUM(si.quantity) AS qty_sold,
                    SUM(si.line_total) AS revenue,
                    SUM(si.cost_price_snapshot * si.quantity) AS cogs
                FROM sale_items si
                JOIN sales s ON s.id = si.sale_id
                WHERE s.sale_date BETWEEN ? AND ?';
        $params = [$from, $to];
        $sql = TenantScope::appendWhere($sql, 's');
        TenantScope::apply($params, $storeId);
        $sql .= ' GROUP BY si.product_id, si.product_name_snapshot, si.sku_snapshot
                  ORDER BY qty_sold DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compute gross_profit and margin_pct.
        foreach ($rows as &$row) {
            $revenue = (float) $row['revenue'];
            $cogs = (float) $row['cogs'];
            $row['gross_profit'] = $revenue - $cogs;
            $row['margin_pct'] = $revenue > 0
                ? round((($revenue - $cogs) / $revenue) * 100, 1)
                : 0.0;
        }
        unset($row);

        return $rows;
    }

    // -----------------------------------------------------------------------
    // 2. Inventory Aging (Slow Movers)
    // -----------------------------------------------------------------------

    /**
     * Active products with zero sales in the last N days.
     *
     * @return array<int, array{sku: string, name: string, category: string, last_sale_date: ?string, stock_quantity: float, stock_value: float}>
     */
    public function inventoryAging(int $storeId, int $days = 30): array
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));

        // Subquery to get last sale date per product.
        $sql = "SELECT
                    p.sku,
                    p.name,
                    COALESCE(c.name, 'Uncategorized') AS category,
                    MAX(s.sale_date) AS last_sale_date,
                    p.stock_quantity,
                    (p.cost_price * p.stock_quantity) AS stock_value
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN sale_items si ON si.product_id = p.id
                LEFT JOIN sales s ON s.id = si.sale_id
                WHERE p.status = ?";
        $params = ['active'];
        $sql = TenantScope::appendWhere($sql, 'p');
        TenantScope::apply($params, $storeId);
        $sql .= ' GROUP BY p.id, p.sku, p.name, c.name, p.stock_quantity, p.cost_price
                  HAVING MAX(s.sale_date) IS NULL OR MAX(s.sale_date) < ?
                  ORDER BY stock_value DESC';
        $params[] = $cutoffDate;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -----------------------------------------------------------------------
    // 3. Profit & Loss Summary
    // -----------------------------------------------------------------------

    /**
     * Revenue, COGS, gross profit, and margin for a date range.
     *
     * @return array{summary: array{revenue: float, cogs: float, gross_profit: float, gross_margin_pct: float}, breakdown: array}
     */
    public function profitAndLoss(int $storeId, string $from, string $to): array
    {
        // Overall summary.
        $sql = 'SELECT
                    COALESCE(SUM(si.line_total), 0) AS revenue,
                    COALESCE(SUM(si.cost_price_snapshot * si.quantity), 0) AS cogs
                FROM sale_items si
                JOIN sales s ON s.id = si.sale_id
                WHERE s.sale_date BETWEEN ? AND ?';
        $params = [$from, $to];
        $sql = TenantScope::appendWhere($sql, 's');
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);

        $revenue = (float) $totals['revenue'];
        $cogs = (float) $totals['cogs'];
        $grossProfit = $revenue - $cogs;
        $grossMarginPct = $revenue > 0 ? round(($grossProfit / $revenue) * 100, 1) : 0.0;

        $summary = [
            'revenue'          => $revenue,
            'cogs'             => $cogs,
            'gross_profit'     => $grossProfit,
            'gross_margin_pct' => $grossMarginPct,
        ];

        // Breakdown by category.
        $sql = "SELECT
                    COALESCE(c.name, 'Uncategorized') AS category,
                    COALESCE(SUM(si.line_total), 0) AS revenue,
                    COALESCE(SUM(si.cost_price_snapshot * si.quantity), 0) AS cogs
                FROM sale_items si
                JOIN sales s ON s.id = si.sale_id
                LEFT JOIN products p ON p.id = si.product_id
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE s.sale_date BETWEEN ? AND ?";
        $params = [$from, $to];
        $sql = TenantScope::appendWhere($sql, 's');
        TenantScope::apply($params, $storeId);
        $sql .= " GROUP BY COALESCE(c.name, 'Uncategorized')
                  ORDER BY revenue DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($breakdown as &$cat) {
            $catRevenue = (float) $cat['revenue'];
            $catCogs = (float) $cat['cogs'];
            $cat['gross_profit'] = $catRevenue - $catCogs;
            $cat['margin_pct'] = $catRevenue > 0
                ? round((($catRevenue - $catCogs) / $catRevenue) * 100, 1)
                : 0.0;
        }
        unset($cat);

        return ['summary' => $summary, 'breakdown' => $breakdown];
    }

    // -----------------------------------------------------------------------
    // 4. Customer Dues
    // -----------------------------------------------------------------------

    /**
     * Customers with outstanding credit balances.
     *
     * @return array{results: array, totalDue: float}
     */
    public function customerDues(int $storeId): array
    {
        $sql = 'SELECT
                    cu.id,
                    cu.name,
                    cu.mobile,
                    COALESCE(SUM(cc.amount_due), 0) AS credit_total,
                    COALESCE(SUM(cc.amount_paid), 0) AS amount_paid,
                    COALESCE(SUM(cc.balance), 0) AS balance
                FROM customers cu
                JOIN customer_credits cc ON cc.customer_id = cu.id
                WHERE cu.is_default = 0 AND cc.balance > 0';
        $params = [];
        $sql = TenantScope::appendWhere($sql, 'cu');
        TenantScope::apply($params, $storeId);
        $sql .= ' GROUP BY cu.id, cu.name, cu.mobile
                  HAVING balance > 0
                  ORDER BY balance DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalDue = 0.0;
        foreach ($results as $row) {
            $totalDue += (float) $row['balance'];
        }

        return ['results' => $results, 'totalDue' => $totalDue];
    }

    // -----------------------------------------------------------------------
    // 5. GST Tax Summary
    // -----------------------------------------------------------------------

    /**
     * Total taxable sales and GST collected by month for a date range.
     *
     * @return array{results: array, totals: array{total_sales: float, total_tax: float}}
     */
    public function gstSummary(int $storeId, string $from, string $to): array
    {
        $sql = "SELECT
                    DATE_FORMAT(s.sale_date, '%Y-%m') AS period_key,
                    DATE_FORMAT(s.sale_date, '%b %Y') AS period,
                    COALESCE(SUM(s.total_amount), 0) AS total_sales,
                    COALESCE(SUM(s.tax_amount), 0) AS tax_amount
                FROM sales s
                WHERE s.sale_date BETWEEN ? AND ?";
        $params = [$from, $to];
        $sql = TenantScope::appendWhere($sql, 's');
        TenantScope::apply($params, $storeId);
        $sql .= " GROUP BY period_key, period
                  ORDER BY period_key ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalSales = 0.0;
        $totalTax = 0.0;
        foreach ($results as $row) {
            $totalSales += (float) $row['total_sales'];
            $totalTax += (float) $row['tax_amount'];
        }

        return [
            'results' => $results,
            'totals'  => ['total_sales' => $totalSales, 'total_tax' => $totalTax],
        ];
    }

    // -----------------------------------------------------------------------
    // CSV Export Helpers
    // -----------------------------------------------------------------------

    /**
     * Stream a CSV download from an array of rows.
     *
     * @param string $filename  The download filename (e.g. "top-sellers.csv").
     * @param array  $headers   Column headers.
     * @param array  $rows      Array of associative arrays.
     * @param array  $keys      Keys to extract from each row.
     */
    public function streamCsv(string $filename, array $headers, array $rows, array $keys): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache');

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);

        foreach ($rows as $row) {
            $line = [];
            foreach ($keys as $key) {
                $line[] = $row[$key] ?? '';
            }
            fputcsv($output, $line);
        }

        fclose($output);
        exit;
    }
}
