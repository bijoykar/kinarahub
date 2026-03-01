<?php
/**
 * views/sales/detail.php — Sale detail view.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $sale  (array)  — Sale record with items: sale_number, sale_date, customer_name, payment_method, subtotal, tax_amount, total_amount, notes, created_by_name, items[]
 */

$sale  = $sale ?? [];
$items = $sale['items'] ?? [];

$paymentBadges = [
    'cash'   => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
    'upi'    => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
    'card'   => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
    'credit' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
];
$payBadge = $paymentBadges[$sale['payment_method'] ?? ''] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400';
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <a href="/kinarahub/sales" class="inline-flex items-center justify-center rounded-lg p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors duration-150" aria-label="Back to sales">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($sale['sale_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($sale['sale_date'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="/kinarahub/sales/<?= (int)$sale['id'] ?>/receipt" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-gray-700 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print Receipt
        </a>
        <a href="/kinarahub/sales/<?= (int)$sale['id'] ?>/invoice.pdf" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 transition-colors duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Download Invoice
        </a>
    </div>
</div>

<!-- Sale metadata -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="rounded-xl bg-white dark:bg-gray-800 p-4 ring-1 ring-gray-200 dark:ring-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Customer</p>
        <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="rounded-xl bg-white dark:bg-gray-800 p-4 ring-1 ring-gray-200 dark:ring-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Payment</p>
        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize <?= $payBadge ?>">
            <?= htmlspecialchars($sale['payment_method'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </span>
    </div>
    <div class="rounded-xl bg-white dark:bg-gray-800 p-4 ring-1 ring-gray-200 dark:ring-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Entry mode</p>
        <p class="text-sm font-semibold text-gray-900 dark:text-white capitalize"><?= htmlspecialchars($sale['entry_mode'] ?? 'pos', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="rounded-xl bg-white dark:bg-gray-800 p-4 ring-1 ring-gray-200 dark:ring-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Recorded by</p>
        <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($sale['created_by_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</div>

<!-- Line items -->
<div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 mb-6">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">#</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">SKU</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Qty</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Unit Price</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Line Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <?php foreach ($items as $idx => $item): ?>
                <tr>
                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-500 dark:text-gray-400"><?= $idx + 1 ?></td>
                    <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($item['product_name_snapshot'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="whitespace-nowrap px-6 py-3"><span class="font-mono text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded"><?= htmlspecialchars($item['sku_snapshot'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300"><?= number_format((float)($item['quantity'] ?? 0), $item['quantity'] == (int)$item['quantity'] ? 0 : 3) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300"><?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format((float)($item['unit_price'] ?? 0), 2) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white"><?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format((float)($item['line_total'] ?? 0), 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Totals -->
    <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
        <div class="flex flex-col items-end space-y-1.5 max-w-xs ml-auto">
            <div class="flex w-full items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                <span class="font-medium text-gray-700 dark:text-gray-300"><?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format((float)($sale['subtotal'] ?? 0), 2) ?></span>
            </div>
            <?php if (!empty($sale['tax_amount']) && $sale['tax_amount'] > 0): ?>
            <div class="flex w-full items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Tax</span>
                <span class="font-medium text-gray-700 dark:text-gray-300"><?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format((float)$sale['tax_amount'], 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="flex w-full items-center justify-between text-base font-bold border-t border-gray-200 dark:border-gray-700 pt-2">
                <span class="text-gray-900 dark:text-white">Total</span>
                <span class="text-brand-600 dark:text-brand-400"><?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format((float)($sale['total_amount'] ?? 0), 2) ?></span>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($sale['notes'])): ?>
<div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Notes</h3>
    <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line"><?= htmlspecialchars($sale['notes'], ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php endif; ?>
