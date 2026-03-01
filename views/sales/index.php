<?php
/**
 * views/sales/index.php — Sales history listing page.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $sales       (array)  — Paginated sale records.
 *   $pagination  (array)  — {page, per_page, total, total_pages}
 *   $filters     (array)  — {search, from, to, payment_method}
 */

$sales      = $sales ?? [];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 25, 'total' => 0, 'total_pages' => 1];
$filters    = $filters ?? ['search' => '', 'from' => '', 'to' => '', 'payment_method' => ''];

$paymentBadges = [
    'cash'   => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
    'upi'    => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
    'card'   => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
    'credit' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
];
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sales History</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= number_format($pagination['total']) ?> transaction<?= $pagination['total'] !== 1 ? 's' : '' ?></p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="/kinarahub/pos" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 transition-colors duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New sale
        </a>
        <a href="/kinarahub/sales/bookkeeping" class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-gray-700 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
            Bookkeeping
        </a>
    </div>
</div>

<!-- Filter bar -->
<form method="GET" action="/kinarahub/sales" class="mb-4 flex flex-col sm:flex-row gap-3" id="sales-filter-form">
    <div class="relative flex-1 min-w-[180px]">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg></span>
        <input type="search" name="search" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by sale# or customer..." class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors">
    </div>
    <input type="date" name="from" value="<?= htmlspecialchars($filters['from'] ?? '', ENT_QUOTES, 'UTF-8') ?>" onchange="this.form.submit()" class="rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors" title="From date">
    <input type="date" name="to" value="<?= htmlspecialchars($filters['to'] ?? '', ENT_QUOTES, 'UTF-8') ?>" onchange="this.form.submit()" class="rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors" title="To date">
    <select name="payment_method" onchange="this.form.submit()" class="rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors appearance-none min-w-[120px]" style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3E%3Cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27m6 8 4 4 4-4%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em;">
        <option value="">All methods</option>
        <option value="cash" <?= ($filters['payment_method'] ?? '') === 'cash' ? 'selected' : '' ?>>Cash</option>
        <option value="upi" <?= ($filters['payment_method'] ?? '') === 'upi' ? 'selected' : '' ?>>UPI</option>
        <option value="card" <?= ($filters['payment_method'] ?? '') === 'card' ? 'selected' : '' ?>>Card</option>
        <option value="credit" <?= ($filters['payment_method'] ?? '') === 'credit' ? 'selected' : '' ?>>Credit</option>
    </select>
</form>

<?php if (empty($sales)): ?>
<div class="flex flex-col items-center justify-center py-16 text-center rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
    </div>
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No sales found</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs">Try adjusting your filters or record a new sale.</p>
</div>
<?php else: ?>
<div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sale #</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Payment</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <?php foreach ($sales as $sale):
                    $payBadge = $paymentBadges[$sale['payment_method'] ?? ''] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400';
                ?>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors duration-100">
                    <td class="whitespace-nowrap px-6 py-4">
                        <span class="text-sm font-semibold text-brand-600 dark:text-brand-400"><?= htmlspecialchars($sale['sale_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        <?= htmlspecialchars(date('d M Y', strtotime($sale['sale_date'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 truncate max-w-[160px]">
                        <?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-center">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize <?= $payBadge ?>">
                            <?= htmlspecialchars($sale['payment_method'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                        <?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format((float)($sale['total_amount'] ?? 0), 2) ?>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a href="/kinarahub/sales/<?= (int)$sale['id'] ?>" class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 dark:hover:text-brand-400 dark:hover:bg-brand-900/20 transition-colors duration-150" title="View details" aria-label="View sale <?= htmlspecialchars($sale['sale_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </a>
                            <a href="/kinarahub/sales/<?= (int)$sale['id'] ?>/receipt" class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 dark:hover:text-green-400 dark:hover:bg-green-900/20 transition-colors duration-150" title="Print receipt" aria-label="Print receipt">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 px-6 py-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Showing <span class="font-medium text-gray-700 dark:text-gray-300"><?= (($pagination['page'] - 1) * $pagination['per_page']) + 1 ?></span>
            to <span class="font-medium text-gray-700 dark:text-gray-300"><?= min($pagination['page'] * $pagination['per_page'], $pagination['total']) ?></span>
            of <span class="font-medium text-gray-700 dark:text-gray-300"><?= number_format($pagination['total']) ?></span>
        </p>
        <nav class="flex items-center gap-1" aria-label="Pagination">
            <?php if ($pagination['page'] > 1): ?>
            <a href="?page=<?= $pagination['page'] - 1 ?>&<?= http_build_query(array_diff_key($filters, ['page' => ''])) ?>" class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Prev</a>
            <?php endif; ?>
            <?php if ($pagination['page'] < $pagination['total_pages']): ?>
            <a href="?page=<?= $pagination['page'] + 1 ?>&<?= http_build_query(array_diff_key($filters, ['page' => ''])) ?>" class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Next</a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
