<?php
/**
 * views/admin/stores/index.php — Store listing page for platform admin.
 *
 * Rendered inside views/layouts/admin.php.
 *
 * Expected variables:
 *   $stores      (array)  — Paginated store records: id, name, owner_name, email, status, created_at
 *   $pagination  (array)  — {page, per_page, total, total_pages}
 *   $filters     (array)  — {search, status}
 *   $csrfToken   (string) — CSRF token.
 */

$stores     = $stores ?? [];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 25, 'total' => 0, 'total_pages' => 1];
$filters    = $filters ?? ['search' => '', 'status' => ''];
$csrfToken  = $csrfToken ?? '';

$statusBadges = [
    'active'               => ['label' => 'Active',   'class' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'],
    'pending_verification' => ['label' => 'Pending',  'class' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'],
    'suspended'            => ['label' => 'Suspended','class' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'],
];
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Stores</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= number_format($pagination['total']) ?> store<?= $pagination['total'] !== 1 ? 's' : '' ?> registered</p>
    </div>
</div>

<!-- Filters -->
<div class="flex flex-col sm:flex-row gap-3 mb-4">
    <form method="GET" action="/kinarahub/admin/stores" class="flex flex-col sm:flex-row gap-3 flex-1">
        <!-- Search -->
        <div class="relative flex-1 max-w-md">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            </span>
            <input
                type="search"
                name="search"
                value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Search by store name, owner, or email..."
                class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 shadow-sm focus:border-admin-500 focus:ring-2 focus:ring-admin-500/30 focus:outline-none transition-colors"
            >
        </div>

        <!-- Status filter -->
        <select
            name="status"
            onchange="this.form.submit()"
            class="rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white shadow-sm focus:border-admin-500 focus:ring-2 focus:ring-admin-500/30 focus:outline-none transition-colors appearance-none"
            style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3E%3Cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27m6 8 4 4 4-4%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em; padding-right: 2.5rem;"
        >
            <option value="">All statuses</option>
            <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="pending_verification" <?= ($filters['status'] ?? '') === 'pending_verification' ? 'selected' : '' ?>>Pending</option>
            <option value="suspended" <?= ($filters['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
        </select>
    </form>
</div>

<?php if (empty($stores)): ?>
<div class="flex flex-col items-center justify-center py-16 text-center rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/></svg>
    </div>
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No stores found</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs">No stores match your current filters.</p>
</div>
<?php else: ?>
<div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Store</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Owner</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Registered</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <?php foreach ($stores as $store):
                    $status   = $store['status'] ?? 'active';
                    $badge    = $statusBadges[$status] ?? $statusBadges['active'];
                    $initial  = strtoupper(mb_substr($store['name'] ?? 'S', 0, 1));
                ?>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors duration-100">
                    <!-- Store name -->
                    <td class="whitespace-nowrap px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm font-bold select-none">
                                <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($store['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </td>

                    <!-- Owner -->
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        <?= htmlspecialchars($store['owner_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                    </td>

                    <!-- Email -->
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        <?= htmlspecialchars($store['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                    </td>

                    <!-- Status -->
                    <td class="whitespace-nowrap px-6 py-4 text-center">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= $badge['class'] ?>">
                            <?= htmlspecialchars($badge['label'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>

                    <!-- Registered date -->
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <?= htmlspecialchars(date('d M Y', strtotime($store['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?>
                    </td>

                    <!-- Actions -->
                    <td class="whitespace-nowrap px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <!-- View -->
                            <a href="/kinarahub/admin/stores/<?= (int)$store['id'] ?>" class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 transition-colors" title="View details">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </a>

                            <?php if ($status === 'suspended'): ?>
                            <!-- Activate -->
                            <form method="POST" action="/kinarahub/admin/stores/<?= (int)$store['id'] ?>/activate" class="inline">
                                <?= \App\Middleware\CsrfMiddleware::field() ?>
                                <button type="submit" class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 dark:hover:text-green-400 dark:hover:bg-green-900/20 transition-colors" title="Activate store">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                            </form>
                            <?php elseif ($status === 'active'): ?>
                            <!-- Suspend -->
                            <button
                                type="button"
                                onclick="confirmSuspend(<?= (int)$store['id'] ?>, '<?= htmlspecialchars(addslashes($store['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')"
                                class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors"
                                title="Suspend store"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            </button>
                            <?php endif; ?>
                        </div>
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
            <?php
                $qParams = http_build_query(array_filter([
                    'search' => $filters['search'] ?? '',
                    'status' => $filters['status'] ?? '',
                ]));
                $qSep = $qParams ? '&' : '';
            ?>
            <?php if ($pagination['page'] > 1): ?>
            <a href="?page=<?= $pagination['page'] - 1 ?><?= $qSep . $qParams ?>" class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Prev</a>
            <?php endif; ?>
            <?php if ($pagination['page'] < $pagination['total_pages']): ?>
            <a href="?page=<?= $pagination['page'] + 1 ?><?= $qSep . $qParams ?>" class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Next</a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Suspend confirmation modal -->
<div id="modal-suspend" role="dialog" aria-modal="true" aria-labelledby="modal-suspend-title" hidden aria-hidden="true" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 dark:bg-gray-950/70" onclick="closeSuspendModal()"></div>
    <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="px-6 pt-6 pb-4 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <h3 id="modal-suspend-title" class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Suspend Store</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Are you sure you want to suspend <strong id="suspend-store-name" class="text-gray-700 dark:text-gray-200"></strong>? The store owner will not be able to use the platform.
            </p>
        </div>
        <div class="flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
            <button type="button" onclick="closeSuspendModal()" class="rounded-xl px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Cancel</button>
            <form id="suspend-form" method="POST" action="">
                <?= \App\Middleware\CsrfMiddleware::field() ?>
                <button type="submit" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors">Suspend</button>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    window.confirmSuspend = function (storeId, storeName) {
        document.getElementById('suspend-store-name').textContent = storeName;
        document.getElementById('suspend-form').action = '/kinarahub/admin/stores/' + storeId + '/suspend';
        var modal = document.getElementById('modal-suspend');
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
    };

    window.closeSuspendModal = function () {
        var modal = document.getElementById('modal-suspend');
        modal.setAttribute('hidden', '');
        modal.setAttribute('aria-hidden', 'true');
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSuspendModal();
        }
    });
})();
</script>
