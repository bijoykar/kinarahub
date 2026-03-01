<?php
/**
 * views/reports/aging.php — Inventory aging report.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $results  (array)  — [{sku, name, category, last_sale_date, stock_quantity, stock_value}]
 *   $days     (int)    — Selected aging period (30, 60, 90). Default: 30
 */

$results  = $results ?? [];
$days     = $days ?? 30;
$currency = CURRENCY_SYMBOL ?? '₹';
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <a href="/kinarahub/reports" class="inline-flex items-center justify-center rounded-lg p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors" aria-label="Back to reports">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Inventory Aging</h1>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Products with no sales in the last <?= $days ?> days</p>
        </div>
    </div>
    <?php if (!empty($results)): ?>
    <a href="/kinarahub/reports/aging/export/pdf?days=<?= $days ?>" class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
        Export PDF
    </a>
    <?php endif; ?>
</div>

<!-- Period selector -->
<form method="GET" action="/kinarahub/reports/aging" class="mb-6">
    <div class="flex items-center gap-4 rounded-xl bg-white dark:bg-gray-800 p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">No sales in last:</span>
        <div class="flex items-center gap-2" role="radiogroup">
            <?php foreach ([30, 60, 90] as $d): ?>
            <label class="cursor-pointer">
                <input type="radio" name="days" value="<?= $d ?>" <?= $days == $d ? 'checked' : '' ?> onchange="this.form.submit()" class="sr-only peer">
                <span class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium transition-colors peer-checked:bg-brand-600 peer-checked:text-white peer-checked:shadow-sm bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600"><?= $d ?> days</span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
</form>

<?php if (empty($results)): ?>
<div class="flex flex-col items-center justify-center py-16 text-center rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-green-100 dark:bg-green-900/30 mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">All products are active</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">No products have been idle for more than <?= $days ?> days.</p>
</div>
<?php else: ?>
<div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Last Sale</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Stock Qty</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Stock Value</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <?php
                    $totalValue = 0;
                    foreach ($results as $row):
                        $totalValue += (float)($row['stock_value'] ?? 0);
                ?>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="whitespace-nowrap px-6 py-3"><span class="font-mono text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded"><?= htmlspecialchars($row['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($row['category'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-500 dark:text-gray-400">
                        <?= !empty($row['last_sale_date']) ? htmlspecialchars(date('d M Y', strtotime($row['last_sale_date'])), ENT_QUOTES, 'UTF-8') : 'Never' ?>
                    </td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300"><?= number_format((float)($row['stock_quantity'] ?? 0), $row['stock_quantity'] == (int)$row['stock_quantity'] ? 0 : 3) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold text-amber-600 dark:text-amber-400"><?= $currency ?><?= number_format((float)($row['stock_value'] ?? 0), 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                    <td colspan="5" class="px-6 py-3 text-right text-sm font-bold text-gray-900 dark:text-white">Total Value Tied Up</td>
                    <td class="px-6 py-3 text-right text-sm font-bold text-amber-600 dark:text-amber-400"><?= $currency ?><?= number_format($totalValue, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>
