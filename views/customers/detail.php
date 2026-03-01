<?php
/**
 * views/customers/detail.php — Customer detail + credit history.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $customer    (array)  — Customer record: id, name, mobile, email, outstanding_balance, created_at
 *   $credits     (array)  — Credit history: sale_number, amount_due, amount_paid, balance, due_date, sale_id
 *   $payments    (array)  — Payment history: amount_paid, payment_method, payment_date, notes
 *   $csrfToken   (string) — CSRF token.
 */

$customer = $customer ?? [];
$credits  = $credits ?? [];
$payments = $payments ?? [];
$csrfToken = $csrfToken ?? '';
$balance = (float)($customer['outstanding_balance'] ?? 0);
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <a href="/kinarahub/customers" class="inline-flex items-center justify-center rounded-lg p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors" aria-label="Back to customers">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($customer['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Customer since <?= htmlspecialchars(date('M Y', strtotime($customer['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>
    <?php if ($balance > 0): ?>
    <button
        type="button"
        onclick="openModal('modal-payment')"
        class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-green-600/30 hover:bg-green-700 transition-colors duration-150"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75"/></svg>
        Record payment
    </button>
    <?php endif; ?>
</div>

<!-- Customer profile card -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="rounded-xl bg-white dark:bg-gray-800 p-4 ring-1 ring-gray-200 dark:ring-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Mobile</p>
        <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($customer['mobile'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="rounded-xl bg-white dark:bg-gray-800 p-4 ring-1 ring-gray-200 dark:ring-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Email</p>
        <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($customer['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="col-span-2 rounded-xl p-4 ring-1 <?= $balance > 0 ? 'bg-red-50 dark:bg-red-900/20 ring-red-200 dark:ring-red-700/50' : 'bg-green-50 dark:bg-green-900/20 ring-green-200 dark:ring-green-700/50' ?>">
        <p class="text-xs font-medium <?= $balance > 0 ? 'text-red-500 dark:text-red-400' : 'text-green-500 dark:text-green-400' ?> uppercase tracking-wider mb-1">Outstanding balance</p>
        <p class="text-2xl font-bold <?= $balance > 0 ? 'text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300' ?>">
            <?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format($balance, 2) ?>
        </p>
    </div>
</div>

<!-- Credit history -->
<div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 mb-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Credit history</h2>
    </div>
    <?php if (empty($credits)): ?>
    <div class="px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No credit transactions</div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sale Ref</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Amount Due</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Paid</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Due Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <?php foreach ($credits as $credit): ?>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="whitespace-nowrap px-6 py-3">
                        <a href="/kinarahub/sales/<?= (int)($credit['sale_id'] ?? 0) ?>" class="text-sm font-semibold text-brand-600 dark:text-brand-400 hover:underline"><?= htmlspecialchars($credit['sale_number'] ?? '-', ENT_QUOTES, 'UTF-8') ?></a>
                    </td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300"><?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format((float)($credit['amount_due'] ?? 0), 2) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-green-600 dark:text-green-400"><?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format((float)($credit['amount_paid'] ?? 0), 2) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold <?= ((float)($credit['balance'] ?? 0)) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' ?>"><?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format((float)($credit['balance'] ?? 0), 2) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-600 dark:text-gray-400"><?= !empty($credit['due_date']) ? htmlspecialchars(date('d M Y', strtotime($credit['due_date'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Payment history -->
<div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Payment history</h2>
    </div>
    <?php if (empty($payments)): ?>
    <div class="px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No payments recorded</div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Amount</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Method</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <?php foreach ($payments as $pmt): ?>
                <tr>
                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars(date('d M Y', strtotime($pmt['payment_date'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold text-green-600 dark:text-green-400"><?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format((float)($pmt['amount_paid'] ?? 0), 2) ?></td>
                    <td class="whitespace-nowrap px-6 py-3 text-center">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($pmt['payment_method'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate"><?= htmlspecialchars($pmt['notes'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Record Payment modal -->
<div id="modal-payment" role="dialog" aria-modal="true" aria-labelledby="modal-pay-title" hidden aria-hidden="true" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 dark:bg-gray-950/70" onclick="closeModal('modal-payment')"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
            <h3 id="modal-pay-title" class="text-lg font-semibold text-gray-900 dark:text-white">Record payment</h3>
            <button type="button" onclick="closeModal('modal-payment')" class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
            </button>
        </div>
        <form method="POST" action="/kinarahub/customers/<?= (int)($customer['id'] ?? 0) ?>/payments" class="px-6 py-5 space-y-4">
            <?= \App\Middleware\CsrfMiddleware::field() ?>

            <div class="rounded-xl bg-gray-50 dark:bg-gray-700/30 p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-0.5">Outstanding balance</p>
                <p class="text-xl font-bold text-red-600 dark:text-red-400"><?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format($balance, 2) ?></p>
            </div>

            <div>
                <label for="pay-amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Amount (<?= CURRENCY_SYMBOL ?? '₹' ?>)</label>
                <input type="number" id="pay-amount" name="amount" required min="0.01" max="<?= $balance ?>" step="0.01" value="<?= number_format($balance, 2, '.', '') ?>"
                       class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors">
            </div>
            <div>
                <label for="pay-method" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Payment method</label>
                <select id="pay-method" name="payment_method" required
                        class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors appearance-none"
                        style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3E%3Cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27m6 8 4 4 4-4%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em;">
                    <option value="cash">Cash</option>
                    <option value="upi">UPI</option>
                    <option value="card">Card</option>
                </select>
            </div>
            <div>
                <label for="pay-notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes <span class="font-normal text-gray-400">(optional)</span></label>
                <textarea id="pay-notes" name="notes" rows="2" maxlength="255" placeholder="Payment reference or note..." class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors resize-none"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-payment')" class="rounded-xl px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Cancel</button>
                <button type="submit" class="rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 transition-colors">Record payment</button>
            </div>
        </form>
    </div>
</div>
