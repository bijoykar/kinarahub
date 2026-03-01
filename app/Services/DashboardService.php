<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\TenantScope;
use PDO;

/**
 * DashboardService — Aggregate queries for the dashboard.
 *
 * All queries are tenant-scoped via store_id.
 * Uses indexed columns: store_id, sale_date on sales table.
 */
class DashboardService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // -----------------------------------------------------------------------
    // Revenue KPIs
    // -----------------------------------------------------------------------

    public function todayRevenue(int $storeId): float
    {
        return $this->revenueForDate($storeId, date('Y-m-d'));
    }

    public function yesterdayRevenue(int $storeId): float
    {
        return $this->revenueForDate($storeId, date('Y-m-d', strtotime('-1 day')));
    }

    public function weekRevenue(int $storeId): float
    {
        $start = date('Y-m-d', strtotime('monday this week'));
        $end = date('Y-m-d');

        return $this->revenueForRange($storeId, $start, $end);
    }

    public function monthRevenue(int $storeId): float
    {
        $start = date('Y-m-01');
        $end = date('Y-m-d');

        return $this->revenueForRange($storeId, $start, $end);
    }

    // -----------------------------------------------------------------------
    // Stock KPIs
    // -----------------------------------------------------------------------

    public function totalStockValue(int $storeId): float
    {
        $sql = 'SELECT COALESCE(SUM(cost_price * stock_quantity), 0) FROM products WHERE status = ?';
        $params = ['active'];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (float) $stmt->fetchColumn();
    }

    public function outOfStockCount(int $storeId): int
    {
        $sql = 'SELECT COUNT(*) FROM products WHERE status = ? AND stock_quantity = 0';
        $params = ['active'];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function lowStockCount(int $storeId): int
    {
        $sql = 'SELECT COUNT(*) FROM products WHERE status = ? AND stock_quantity > 0 AND stock_quantity <= reorder_point';
        $params = ['active'];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    // -----------------------------------------------------------------------
    // Top products & recent sales
    // -----------------------------------------------------------------------

    /**
     * Top 5 products sold today by quantity.
     *
     * @return array<int, array{product_name: string, units_sold: float, revenue: float}>
     */
    public function top5ProductsToday(int $storeId): array
    {
        $sql = 'SELECT si.product_name_snapshot AS product_name,
                    SUM(si.quantity) AS units_sold,
                    SUM(si.line_total) AS revenue
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             WHERE s.sale_date = CURDATE()';
        $params = [];
        $sql = TenantScope::appendWhere($sql, 's');
        TenantScope::apply($params, $storeId);
        $sql .= ' GROUP BY si.product_id, si.product_name_snapshot
             ORDER BY units_sold DESC
             LIMIT 5';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recent sales with customer name and payment method.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentSales(int $storeId, int $limit = 10): array
    {
        $sql = 'SELECT s.sale_number, s.sale_date, s.payment_method, s.total_amount,
                    c.name AS customer_name
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id';
        $params = [];
        $sql = TenantScope::appendWhere($sql, 's');
        TenantScope::apply($params, $storeId);
        $sql .= ' ORDER BY s.created_at DESC LIMIT ?';
        $params[] = $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -----------------------------------------------------------------------
    // Chart data
    // -----------------------------------------------------------------------

    /**
     * Sales trend data for Chart.js line chart.
     *
     * @return array{labels: string[], amounts: float[]}
     */
    public function salesTrend(int $storeId, string $period = 'week'): array
    {
        switch ($period) {
            case 'day':
                // Hourly breakdown for today.
                return $this->salesTrendHourly($storeId);
            case 'week':
                // Daily breakdown for current week.
                return $this->salesTrendDaily($storeId, 7);
            case 'month':
                // Daily breakdown for current month.
                $daysInMonth = (int) date('t');
                return $this->salesTrendDaily($storeId, $daysInMonth);
            case 'year':
                // Monthly breakdown for current year.
                return $this->salesTrendMonthly($storeId);
            default:
                return $this->salesTrendDaily($storeId, 7);
        }
    }

    /**
     * Payment method breakdown for donut chart.
     *
     * @return array{labels: string[], amounts: float[]}
     */
    public function paymentMethodBreakdown(int $storeId, string $period = 'month'): array
    {
        [$start, $end] = $this->periodRange($period);

        $sql = 'SELECT payment_method, COALESCE(SUM(total_amount), 0) AS total
             FROM sales
             WHERE sale_date BETWEEN ? AND ?';
        $params = [$start, $end];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);
        $sql .= ' GROUP BY payment_method ORDER BY total DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $amounts = [];
        foreach ($rows as $row) {
            $labels[] = ucfirst($row['payment_method']);
            $amounts[] = (float) $row['total'];
        }

        return ['labels' => $labels, 'amounts' => $amounts];
    }

    /**
     * Stock status distribution for donut chart.
     *
     * @return array{labels: string[], counts: int[]}
     */
    public function stockStatusDistribution(int $storeId): array
    {
        $outOfStock = $this->outOfStockCount($storeId);
        $lowStock = $this->lowStockCount($storeId);

        // In stock = active products that are neither out_of_stock nor low_stock.
        $sql = 'SELECT COUNT(*) FROM products WHERE status = ? AND stock_quantity > reorder_point';
        $params = ['active'];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $inStock = (int) $stmt->fetchColumn();

        return [
            'labels' => ['In Stock', 'Low Stock', 'Out of Stock'],
            'counts' => [$inStock, $lowStock, $outOfStock],
        ];
    }

    /**
     * Get all dashboard data in a single call to reduce round trips.
     *
     * @return array<string, mixed>
     */
    public function getAllStats(int $storeId): array
    {
        $todayRev = $this->todayRevenue($storeId);
        $yesterdayRev = $this->yesterdayRevenue($storeId);

        // Percentage change.
        $percentChange = 0.0;
        if ($yesterdayRev > 0) {
            $percentChange = round((($todayRev - $yesterdayRev) / $yesterdayRev) * 100, 1);
        } elseif ($todayRev > 0) {
            $percentChange = 100.0;
        }

        return [
            'today_revenue'     => $todayRev,
            'yesterday_revenue' => $yesterdayRev,
            'percent_change'    => $percentChange,
            'week_revenue'      => $this->weekRevenue($storeId),
            'month_revenue'     => $this->monthRevenue($storeId),
            'stock_value'       => $this->totalStockValue($storeId),
            'out_of_stock'      => $this->outOfStockCount($storeId),
            'low_stock'         => $this->lowStockCount($storeId),
            'top_products'      => $this->top5ProductsToday($storeId),
            'recent_sales'      => $this->recentSales($storeId, 10),
            'sales_trend'       => $this->salesTrend($storeId, 'week'),
            'payment_breakdown' => $this->paymentMethodBreakdown($storeId, 'month'),
            'stock_distribution' => $this->stockStatusDistribution($storeId),
        ];
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function revenueForDate(int $storeId, string $date): float
    {
        $sql = 'SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE sale_date = ?';
        $params = [$date];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (float) $stmt->fetchColumn();
    }

    private function revenueForRange(int $storeId, string $start, string $end): float
    {
        $sql = 'SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE sale_date BETWEEN ? AND ?';
        $params = [$start, $end];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (float) $stmt->fetchColumn();
    }

    private function salesTrendHourly(int $storeId): array
    {
        $sql = 'SELECT HOUR(created_at) AS hr, COALESCE(SUM(total_amount), 0) AS total
             FROM sales
             WHERE sale_date = CURDATE()';
        $params = [];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);
        $sql .= ' GROUP BY HOUR(created_at) ORDER BY hr';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $labels = [];
        $amounts = [];
        for ($h = 0; $h < 24; $h++) {
            $labels[] = str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00';
            $amounts[] = (float) ($rows[$h] ?? 0);
        }

        return ['labels' => $labels, 'amounts' => $amounts];
    }

    private function salesTrendDaily(int $storeId, int $days): array
    {
        $start = date('Y-m-d', strtotime("-" . ($days - 1) . " days"));
        $end = date('Y-m-d');

        $sql = 'SELECT sale_date, COALESCE(SUM(total_amount), 0) AS total
             FROM sales
             WHERE sale_date BETWEEN ? AND ?';
        $params = [$start, $end];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);
        $sql .= ' GROUP BY sale_date ORDER BY sale_date';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $labels = [];
        $amounts = [];
        $current = new \DateTime($start);
        $endDate = new \DateTime($end);
        $endDate->modify('+1 day');

        while ($current < $endDate) {
            $dateStr = $current->format('Y-m-d');
            $labels[] = $current->format('d M');
            $amounts[] = (float) ($rows[$dateStr] ?? 0);
            $current->modify('+1 day');
        }

        return ['labels' => $labels, 'amounts' => $amounts];
    }

    private function salesTrendMonthly(int $storeId): array
    {
        $year = date('Y');
        $sql = 'SELECT MONTH(sale_date) AS m, COALESCE(SUM(total_amount), 0) AS total
             FROM sales
             WHERE YEAR(sale_date) = ?';
        $params = [$year];
        $sql = TenantScope::appendWhere($sql);
        TenantScope::apply($params, $storeId);
        $sql .= ' GROUP BY MONTH(sale_date) ORDER BY m';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $labels = [];
        $amounts = [];
        $currentMonth = (int) date('n');

        for ($m = 1; $m <= $currentMonth; $m++) {
            $labels[] = $monthNames[$m - 1];
            $amounts[] = (float) ($rows[$m] ?? 0);
        }

        return ['labels' => $labels, 'amounts' => $amounts];
    }

    /**
     * Convert period string to start/end dates.
     *
     * @return array{0: string, 1: string}
     */
    private function periodRange(string $period): array
    {
        $end = date('Y-m-d');
        switch ($period) {
            case 'day':
                return [$end, $end];
            case 'week':
                return [date('Y-m-d', strtotime('monday this week')), $end];
            case 'year':
                return [date('Y-01-01'), $end];
            case 'month':
            default:
                return [date('Y-m-01'), $end];
        }
    }
}
