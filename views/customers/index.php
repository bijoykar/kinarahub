<?php
/**
 * views/customers/index.php — Customer listing page.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $customers   (array)  — Paginated customer records (excl. Walk-in).
 *   $pagination  (array)  — {page, per_page, total, total_pages}
 *   $filters     (array)  — {search}
 *   $csrfToken   (string) — CSRF token.
 */

$customers  = $customers ?? [];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 25, 'total' => 0, 'total_pages' => 1];
$filters    = $filters ?? ['search' => ''];
$csrfToken  = $csrfToken ?? '';
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Customers</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= number_format($pagination['total']) ?> customer<?= $pagination['total'] !== 1 ? 's' : '' ?></p>
    </div>
    <button
        type="button"
        id="btn-new-item"
        onclick="openCustomerModal()"
        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 transition-colors duration-150"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/></svg>
        Add customer
    </button>
</div>

<!-- Search -->
<form method="GET" action="<?= APP_URL ?>/customers" class="mb-4">
    <div class="relative max-w-md">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg></span>
        <input type="search" name="search" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by name or mobile..." class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors">
    </div>
</form>

<?php if (empty($customers)): ?>
<div class="flex flex-col items-center justify-center py-16 text-center rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z"/></svg>
    </div>
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No customers yet</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs">Add your first customer or they'll be created automatically during credit sales.</p>
</div>
<?php else: ?>
<div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Mobile</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Outstanding</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <?php foreach ($customers as $cust):
                    $balance = (float)($cust['outstanding_balance'] ?? 0);
                    $hasBalance = $balance > 0;
                ?>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors duration-100">
                    <td class="whitespace-nowrap px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 text-xs font-semibold uppercase select-none">
                                <?= htmlspecialchars(strtoupper(mb_substr($cust['name'] ?? 'C', 0, 2)), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($cust['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($cust['mobile'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($cust['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="whitespace-nowrap px-6 py-4 text-right">
                        <span class="text-sm font-semibold <?= $hasBalance ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' ?>">
                            <?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format($balance, 2) ?>
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-right">
                        <a href="<?= APP_URL ?>/customers/<?= (int)$cust['id'] ?>" class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 dark:hover:text-brand-400 dark:hover:bg-brand-900/20 transition-colors duration-150" title="View details">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 px-6 py-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">Page <?= $pagination['page'] ?> of <?= $pagination['total_pages'] ?></p>
        <nav class="flex items-center gap-1">
            <?php if ($pagination['page'] > 1): ?><a href="?page=<?= $pagination['page'] - 1 ?>&search=<?= urlencode($filters['search'] ?? '') ?>" class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Prev</a><?php endif; ?>
            <?php if ($pagination['page'] < $pagination['total_pages']): ?><a href="?page=<?= $pagination['page'] + 1 ?>&search=<?= urlencode($filters['search'] ?? '') ?>" class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Next</a><?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Add Customer modal -->
<div id="modal-customer" role="dialog" aria-modal="true" aria-labelledby="modal-cust-title" hidden aria-hidden="true" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 dark:bg-gray-950/70" onclick="closeModal('modal-customer')"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
            <h3 id="modal-cust-title" class="text-lg font-semibold text-gray-900 dark:text-white">Add customer</h3>
            <button type="button" onclick="closeModal('modal-customer')" class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/customers" class="px-6 py-5 space-y-4">
            <?= \App\Middleware\CsrfMiddleware::field() ?>
            <div>
                <label for="cust-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Full name</label>
                <input type="text" id="cust-name" name="name" required maxlength="100" placeholder="Customer name" class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors">
            </div>
            <div>
                <label for="cust-mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Mobile</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-gray-400 select-none pointer-events-none">+91</span>
                    <input type="tel" id="cust-mobile" name="mobile" required maxlength="10" pattern="[0-9]{10}" inputmode="numeric" placeholder="9876543210" oninput="this.value=this.value.replace(/[^0-9]/g,'')" class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 pl-12 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors">
                </div>
            </div>
            <div>
                <label for="cust-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="font-normal text-gray-400">(optional)</span></label>
                <input type="email" id="cust-email" name="email" oninput="this.value=this.value.toLowerCase()" placeholder="email@example.com" class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-customer')" class="rounded-xl px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Cancel</button>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 transition-colors">Add customer</button>
            </div>
        </form>
    </div>
</div>

<script>
window.openCustomerModal = function () {
    openModal('modal-customer');
};
</script>
