<?php
/**
 * views/dashboard/index.php — Full dashboard with KPIs, charts, and tables.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $stats (array) — All dashboard data from DashboardService::getAllStats()
 */

$stats     = $stats ?? [];
$storeName = htmlspecialchars($_SESSION['store_name'] ?? 'My Store', ENT_QUOTES, 'UTF-8');
$staffName = htmlspecialchars($_SESSION['staff_name'] ?? 'Staff', ENT_QUOTES, 'UTF-8');
$currency  = CURRENCY_SYMBOL ?? '₹';

$todayRev      = (float) ($stats['today_revenue'] ?? 0);
$percentChange = (float) ($stats['percent_change'] ?? 0);
$weekRev       = (float) ($stats['week_revenue'] ?? 0);
$monthRev      = (float) ($stats['month_revenue'] ?? 0);
$stockValue    = (float) ($stats['stock_value'] ?? 0);
$outOfStock    = (int) ($stats['out_of_stock'] ?? 0);
$lowStock      = (int) ($stats['low_stock'] ?? 0);
$topProducts   = $stats['top_products'] ?? [];
$recentSales   = $stats['recent_sales'] ?? [];
$salesTrend    = $stats['sales_trend'] ?? ['labels' => [], 'amounts' => []];
$paymentBreakdown = $stats['payment_breakdown'] ?? ['labels' => [], 'amounts' => []];
$stockDist     = $stats['stock_distribution'] ?? ['labels' => [], 'counts' => []];
?>

<!-- Welcome banner -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Welcome back, <?= $staffName ?>
    </h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Here's an overview of <?= $storeName ?> today.
    </p>
</div>

<!-- KPI stat cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <!-- Sales Today -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center gap-3 mb-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sales Today</p>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $currency ?><?= number_format($todayRev, 2) ?></p>
        <?php if ($percentChange != 0): ?>
        <p class="mt-1 text-xs font-medium <?= $percentChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
            <?= $percentChange >= 0 ? '&#9650;' : '&#9660;' ?> <?= abs($percentChange) ?>% vs yesterday
        </p>
        <?php endif; ?>
    </div>

    <!-- Sales This Week -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center gap-3 mb-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sales This Week</p>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $currency ?><?= number_format($weekRev, 2) ?></p>
    </div>

    <!-- Sales This Month -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center gap-3 mb-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sales This Month</p>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $currency ?><?= number_format($monthRev, 2) ?></p>
    </div>

    <!-- Total Stock Value -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center gap-3 mb-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Stock Value</p>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $currency ?><?= number_format($stockValue, 2) ?></p>
    </div>

    <!-- Out of Stock -->
    <a href="<?= APP_URL ?>/inventory?status=out_of_stock" class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 hover:ring-red-300 dark:hover:ring-red-600/50 transition-all group">
        <div class="flex items-center gap-3 mb-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">Out of Stock</p>
        </div>
        <p class="text-2xl font-bold <?= $outOfStock > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' ?>"><?= $outOfStock ?></p>
    </a>

    <!-- Low Stock -->
    <a href="<?= APP_URL ?>/inventory?status=low_stock" class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 hover:ring-amber-300 dark:hover:ring-amber-600/50 transition-all group">
        <div class="flex items-center gap-3 mb-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Low Stock</p>
        </div>
        <p class="text-2xl font-bold <?= $lowStock > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' ?>"><?= $lowStock ?></p>
    </a>
</div>

<!-- Charts row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <!-- Sales Trend -->
    <div class="lg:col-span-2 rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sales Trend</h2>
            <div class="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-0.5" id="trend-toggle">
                <button type="button" data-period="day" class="trend-btn rounded-md px-3 py-1 text-xs font-medium transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Day</button>
                <button type="button" data-period="week" class="trend-btn rounded-md px-3 py-1 text-xs font-medium transition-colors bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm">Week</button>
                <button type="button" data-period="month" class="trend-btn rounded-md px-3 py-1 text-xs font-medium transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Month</button>
                <button type="button" data-period="year" class="trend-btn rounded-md px-3 py-1 text-xs font-medium transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Year</button>
            </div>
        </div>
        <div class="h-64">
            <canvas id="chart-sales-trend"></canvas>
        </div>
    </div>

    <!-- Payment Method Breakdown -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Sales by Payment Method</h2>
        <div class="h-56 flex items-center justify-center">
            <canvas id="chart-payment-method"></canvas>
        </div>
    </div>

    <!-- Stock Status Distribution -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Stock Status</h2>
        <div class="h-56 flex items-center justify-center">
            <canvas id="chart-stock-status"></canvas>
        </div>
    </div>
</div>

<!-- Bottom tables row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <!-- Top 5 Products Today -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Top Products Today</h2>
        </div>
        <?php if (empty($topProducts)): ?>
        <div class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No sales recorded today</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/50">
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Product</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Units</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <?php foreach ($topProducts as $prod): ?>
                    <tr>
                        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($prod['product_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-gray-600 dark:text-gray-400"><?= number_format((float) ($prod['units_sold'] ?? 0), 1) ?></td>
                        <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white"><?= $currency ?><?= number_format((float) ($prod['revenue'] ?? 0), 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent 10 Sales -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Recent Sales</h2>
        </div>
        <?php if (empty($recentSales)): ?>
        <div class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No sales recorded yet</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/50">
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sale #</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer</th>
                        <th class="px-5 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Payment</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <?php foreach ($recentSales as $sale):
                        $badgeColor = match($sale['payment_method'] ?? '') {
                            'cash'   => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                            'upi'    => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
                            'card'   => 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300',
                            'credit' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                            default  => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
                        };
                    ?>
                    <tr>
                        <td class="whitespace-nowrap px-5 py-3 text-sm font-semibold text-brand-600 dark:text-brand-400"><?= htmlspecialchars($sale['sale_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="whitespace-nowrap px-5 py-3 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="whitespace-nowrap px-5 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize <?= $badgeColor ?>"><?= htmlspecialchars($sale['payment_method'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white"><?= $currency ?><?= number_format((float) ($sale['total_amount'] ?? 0), 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    var CURRENCY = '<?= $currency ?>';
    var isDark = document.documentElement.classList.contains('dark');

    var gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    var textColor = isDark ? '#9ca3af' : '#6b7280';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";

    // -- Sales Trend line chart --
    var trendCtx = document.getElementById('chart-sales-trend').getContext('2d');
    var trendGradient = trendCtx.createLinearGradient(0, 0, 0, 250);
    trendGradient.addColorStop(0, isDark ? 'rgba(99,102,241,0.3)' : 'rgba(99,102,241,0.15)');
    trendGradient.addColorStop(1, 'rgba(99,102,241,0)');

    var salesTrendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($salesTrend['labels'], JSON_HEX_TAG) ?>,
            datasets: [{
                label: 'Revenue',
                data: <?= json_encode($salesTrend['amounts'], JSON_HEX_TAG) ?>,
                borderColor: '#6366f1',
                backgroundColor: trendGradient,
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: isDark ? '#1f2937' : '#ffffff',
                pointBorderWidth: 2,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) { return CURRENCY + ctx.parsed.y.toFixed(2); }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 15 } },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: {
                        callback: function (v) { return CURRENCY + v.toLocaleString(); }
                    }
                }
            }
        }
    });

    // -- Period toggle --
    document.getElementById('trend-toggle').addEventListener('click', function (e) {
        var btn = e.target.closest('.trend-btn');
        if (!btn) return;
        var period = btn.dataset.period;

        // Update active button style.
        document.querySelectorAll('.trend-btn').forEach(function (b) {
            b.className = 'trend-btn rounded-md px-3 py-1 text-xs font-medium transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200';
        });
        btn.className = 'trend-btn rounded-md px-3 py-1 text-xs font-medium transition-colors bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm';

        // AJAX fetch new data.
        fetch('<?= APP_URL ?>/dashboard/chart-data?type=sales_trend&period=' + period)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                salesTrendChart.data.labels = data.labels;
                salesTrendChart.data.datasets[0].data = data.amounts;
                salesTrendChart.update();
            });
    });

    // -- Payment Method donut --
    var payLabels = <?= json_encode($paymentBreakdown['labels'], JSON_HEX_TAG) ?>;
    var payAmounts = <?= json_encode($paymentBreakdown['amounts'], JSON_HEX_TAG) ?>;
    var payColors = ['#22c55e', '#3b82f6', '#8b5cf6', '#ef4444', '#f59e0b'];

    if (payLabels.length > 0) {
        new Chart(document.getElementById('chart-payment-method'), {
            type: 'doughnut',
            data: {
                labels: payLabels,
                datasets: [{
                    data: payAmounts,
                    backgroundColor: payColors.slice(0, payLabels.length),
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8 } },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) { return ctx.label + ': ' + CURRENCY + ctx.parsed.toFixed(2); }
                        }
                    }
                }
            }
        });
    }

    // -- Stock Status donut --
    var stockLabels = <?= json_encode($stockDist['labels'], JSON_HEX_TAG) ?>;
    var stockCounts = <?= json_encode($stockDist['counts'], JSON_HEX_TAG) ?>;
    var stockColors = ['#22c55e', '#f59e0b', '#ef4444'];

    if (stockCounts.reduce(function (a, b) { return a + b; }, 0) > 0) {
        new Chart(document.getElementById('chart-stock-status'), {
            type: 'doughnut',
            data: {
                labels: stockLabels,
                datasets: [{
                    data: stockCounts,
                    backgroundColor: stockColors,
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8 } }
                }
            }
        });
    }
})();
</script>
