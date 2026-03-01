<?php
/**
 * views/reports/gst.php — GST Summary report.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $results    (array) — [{period, total_sales, tax_amount}]
 *   $totals     (array) — {total_sales, total_tax}
 *   $filters    (array) — {from, to}
 */

$results  = $results ?? [];
$totals   = $totals ?? ['total_sales' => 0, 'total_tax' => 0];
$filters  = $filters ?? ['from' => date('Y-m-01'), 'to' => date('Y-m-d')];
$currency = CURRENCY_SYMBOL ?? '₹';
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <a href="<?= APP_URL ?>/reports" class="inline-flex items-center justify-center rounded-lg p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors" aria-label="Back to reports">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">GST Summary</h1>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Tax collected and total sales by period</p>
        </div>
    </div>
    <?php if (!empty($results)): ?>
    <div class="flex items-center gap-2">
        <a href="<?= APP_URL ?>/reports/gst/export/pdf?from=<?= urlencode($filters['from']) ?>&to=<?= urlencode($filters['to']) ?>" class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            PDF
        </a>
        <a href="<?= APP_URL ?>/reports/gst/export/csv?from=<?= urlencode($filters['from']) ?>&to=<?= urlencode($filters['to']) ?>" class="inline-flex items-center gap-1.5 rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            CSV
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Date range filter -->
<form method="GET" action="<?= APP_URL ?>/reports/gst" class="mb-6">
    <div class="flex flex-wrap items-end gap-3 rounded-xl bg-white dark:bg-gray-800 p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div>
            <label for="gst-from" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">From</label>
            <input type="date" id="gst-from" name="from" value="<?= htmlspecialchars($filters['from'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors">
        </div>
        <div>
            <label for="gst-to" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">To</label>
            <input type="date" id="gst-to" name="to" value="<?= htmlspecialchars($filters['to'], ENT_QUOTES, 'UTF-8') ?>" required max="<?= date('Y-m-d') ?>" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors">
        </div>
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 transition-colors">Generate</button>
    </div>
</form>

<?php if (empty($results)): ?>
<div class="flex flex-col items-center justify-center py-16 text-center rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
    </div>
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No data for this period</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">Select a date range and click Generate.</p>
</div>
<?php else: ?>

<!-- Totals summary -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="rounded-xl bg-white dark:bg-gray-800 p-5 ring-1 ring-gray-200 dark:ring-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Sales</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $currency ?><?= number_format((float)$totals['total_sales'], 2) ?></p>
    </div>
    <div class="rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/40 p-5">
        <p class="text-xs font-medium text-blue-600 dark:text-blue-300 uppercase tracking-wider mb-1">Total GST Collected</p>
        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300"><?= $currency ?><?= number_format((float)$totals['total_tax'], 2) ?></p>
    </div>
</div>

<!-- Period breakdown table -->
<div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Period</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Sales</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">GST Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <?php foreach ($results as $row): ?>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="whitespace-nowrap px-6 py-3 text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($row['period'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300"><?= $currency ?><?= number_format((float)($row['total_sales'] ?? 0), 2) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold text-blue-600 dark:text-blue-400"><?= $currency ?><?= number_format((float)($row['tax_amount'] ?? 0), 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                    <td class="px-6 py-3 text-sm font-bold text-gray-900 dark:text-white">Total</td>
                    <td class="px-6 py-3 text-right text-sm font-bold text-gray-900 dark:text-white"><?= $currency ?><?= number_format((float)$totals['total_sales'], 2) ?></td>
                    <td class="px-6 py-3 text-right text-sm font-bold text-blue-600 dark:text-blue-400"><?= $currency ?><?= number_format((float)$totals['total_tax'], 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>
