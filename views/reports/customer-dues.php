<?php
/**
 * views/reports/customer-dues.php — Customer outstanding dues report.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $results     (array) — [{id, name, mobile, credit_total, amount_paid, balance}]
 *   $totalDue    (float) — Sum of all outstanding balances.
 */

$results  = $results ?? [];
$totalDue = $totalDue ?? 0;
$currency = CURRENCY_SYMBOL ?? '₹';
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <a href="<?= APP_URL ?>/reports" class="inline-flex items-center justify-center rounded-lg p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors" aria-label="Back to reports">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Customer Dues</h1>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Customers with outstanding credit balances</p>
        </div>
    </div>
    <?php if (!empty($results)): ?>
    <a href="<?= APP_URL ?>/reports/customer-dues/export/csv" class="inline-flex items-center gap-1.5 rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
        Export CSV
    </a>
    <?php endif; ?>
</div>

<!-- Total outstanding summary -->
<?php if (!empty($results)): ?>
<div class="mb-6 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 p-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-red-800 dark:text-red-200">Total Outstanding</p>
                <p class="text-xs text-red-600 dark:text-red-300"><?= count($results) ?> customer<?= count($results) !== 1 ? 's' : '' ?> with dues</p>
            </div>
        </div>
        <p class="text-2xl font-bold text-red-700 dark:text-red-300"><?= $currency ?><?= number_format((float)$totalDue, 2) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if (empty($results)): ?>
<div class="flex flex-col items-center justify-center py-16 text-center rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-green-100 dark:bg-green-900/30 mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">All clear</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">No customers have outstanding dues.</p>
</div>
<?php else: ?>
<div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Mobile</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Credit Total</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Paid</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Balance Due</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <?php foreach ($results as $cust): ?>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="whitespace-nowrap px-6 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 text-xs font-semibold uppercase select-none">
                                <?= htmlspecialchars(strtoupper(mb_substr($cust['name'] ?? 'C', 0, 2)), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($cust['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($cust['mobile'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300"><?= $currency ?><?= number_format((float)($cust['credit_total'] ?? 0), 2) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-green-600 dark:text-green-400"><?= $currency ?><?= number_format((float)($cust['amount_paid'] ?? 0), 2) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-bold text-red-600 dark:text-red-400"><?= $currency ?><?= number_format((float)($cust['balance'] ?? 0), 2) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right">
                        <a href="<?= APP_URL ?>/customers/<?= (int)$cust['id'] ?>" class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 dark:hover:text-brand-400 dark:hover:bg-brand-900/20 transition-colors" title="View customer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
