<?php
/**
 * views/reports/pnl.php — Profit & Loss report.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $summary    (array) — {revenue, cogs, gross_profit, gross_margin_pct}
 *   $breakdown  (array) — [{category, revenue, cogs, gross_profit, margin_pct}]
 *   $filters    (array) — {from, to}
 *   $hideCostPrice (bool) — If true, cost/profit data hidden.
 */

$summary       = $summary ?? ['revenue' => 0, 'cogs' => 0, 'gross_profit' => 0, 'gross_margin_pct' => 0];
$breakdown     = $breakdown ?? [];
$filters       = $filters ?? ['from' => date('Y-m-01'), 'to' => date('Y-m-d')];
$hideCostPrice = $hideCostPrice ?? false;
$currency      = CURRENCY_SYMBOL ?? '₹';
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <a href="<?= APP_URL ?>/reports" class="inline-flex items-center justify-center rounded-lg p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors" aria-label="Back to reports">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Profit & Loss</h1>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Revenue, costs, and gross margin analysis</p>
        </div>
    </div>
    <?php if (!empty($breakdown)): ?>
    <a href="<?= APP_URL ?>/reports/pnl/export/pdf?from=<?= urlencode($filters['from']) ?>&to=<?= urlencode($filters['to']) ?>" class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
        Export PDF
    </a>
    <?php endif; ?>
</div>

<!-- Date range filter -->
<form method="GET" action="<?= APP_URL ?>/reports/pnl" class="mb-6">
    <div class="flex flex-wrap items-end gap-3 rounded-xl bg-white dark:bg-gray-800 p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div>
            <label for="pnl-from" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">From</label>
            <input type="date" id="pnl-from" name="from" value="<?= htmlspecialchars($filters['from'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors">
        </div>
        <div>
            <label for="pnl-to" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">To</label>
            <input type="date" id="pnl-to" name="to" value="<?= htmlspecialchars($filters['to'], ENT_QUOTES, 'UTF-8') ?>" required max="<?= date('Y-m-d') ?>" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors">
        </div>
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 transition-colors">Generate</button>
    </div>
</form>

<?php if ($hideCostPrice): ?>
<div class="mb-6 flex items-center gap-2.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 px-4 py-3 text-sm text-amber-700 dark:text-amber-300">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
    Cost and profit data is restricted for your role.
</div>
<?php else: ?>

<!-- Summary cards -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="rounded-xl bg-white dark:bg-gray-800 p-4 ring-1 ring-gray-200 dark:ring-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Revenue</p>
        <p class="text-xl font-bold text-gray-900 dark:text-white"><?= $currency ?><?= number_format((float)$summary['revenue'], 2) ?></p>
    </div>
    <div class="rounded-xl bg-white dark:bg-gray-800 p-4 ring-1 ring-gray-200 dark:ring-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">COGS</p>
        <p class="text-xl font-bold text-gray-900 dark:text-white"><?= $currency ?><?= number_format((float)$summary['cogs'], 2) ?></p>
    </div>
    <div class="rounded-xl bg-white dark:bg-gray-800 p-4 ring-1 ring-gray-200 dark:ring-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Gross Profit</p>
        <p class="text-xl font-bold <?= $summary['gross_profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>"><?= $currency ?><?= number_format((float)$summary['gross_profit'], 2) ?></p>
    </div>
    <div class="rounded-xl bg-white dark:bg-gray-800 p-4 ring-1 ring-gray-200 dark:ring-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Gross Margin</p>
        <p class="text-xl font-bold <?= $summary['gross_margin_pct'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>"><?= number_format((float)$summary['gross_margin_pct'], 1) ?>%</p>
    </div>
</div>

<!-- Category breakdown -->
<?php if (empty($breakdown)): ?>
<div class="flex flex-col items-center justify-center py-16 text-center rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No data for this period</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">Select a date range and click Generate.</p>
</div>
<?php else: ?>
<div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Breakdown by Category</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Category</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Revenue</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">COGS</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Gross Profit</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Margin</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <?php foreach ($breakdown as $cat): ?>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($cat['category'] ?? 'Uncategorized', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300"><?= $currency ?><?= number_format((float)($cat['revenue'] ?? 0), 2) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-400"><?= $currency ?><?= number_format((float)($cat['cogs'] ?? 0), 2) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold <?= ($cat['gross_profit'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>"><?= $currency ?><?= number_format((float)($cat['gross_profit'] ?? 0), 2) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-medium <?= ($cat['margin_pct'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>"><?= number_format((float)($cat['margin_pct'] ?? 0), 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>
