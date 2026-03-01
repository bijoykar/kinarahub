<?php
/**
 * views/admin/stores/detail.php — Store detail page for platform admin.
 *
 * Rendered inside views/layouts/admin.php.
 *
 * Expected variables:
 *   $store      (array)  — Store record: id, name, owner_name, email, mobile, address, city, state, pincode, logo, status, created_at, staff_count, product_count, total_sales
 *   $csrfToken  (string) — CSRF token.
 */

$store     = $store ?? [];
$csrfToken = $csrfToken ?? '';
$status    = $store['status'] ?? 'active';

$statusBadges = [
    'active'               => ['label' => 'Active',    'class' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300', 'dot' => 'bg-green-500'],
    'pending_verification' => ['label' => 'Pending',   'class' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300', 'dot' => 'bg-amber-500'],
    'suspended'            => ['label' => 'Suspended', 'class' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300', 'dot' => 'bg-red-500'],
];
$badge = $statusBadges[$status] ?? $statusBadges['active'];
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <a href="<?= APP_URL ?>/admin/stores" class="inline-flex items-center justify-center rounded-lg p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors" aria-label="Back to stores">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        </a>
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($store['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium <?= $badge['class'] ?>">
                    <span class="h-1.5 w-1.5 rounded-full <?= $badge['dot'] ?>" aria-hidden="true"></span>
                    <?= $badge['label'] ?>
                </span>
            </div>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Registered <?= htmlspecialchars(date('d M Y', strtotime($store['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <!-- Browse Store (View Only) — impersonation -->
        <form method="POST" action="<?= APP_URL ?>/admin/impersonate/<?= (int)$store['id'] ?>">
            <?= \App\Middleware\CsrfMiddleware::field() ?>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/30 hover:bg-blue-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Browse Store (View Only)
            </button>
        </form>

        <?php if ($status === 'active'): ?>
        <!-- Suspend -->
        <form method="POST" action="<?= APP_URL ?>/admin/stores/<?= (int)$store['id'] ?>/suspend" id="detail-suspend-form">
            <?= \App\Middleware\CsrfMiddleware::field() ?>
            <button
                type="button"
                onclick="confirmDetailAction('suspend')"
                class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-gray-700 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 ring-1 ring-inset ring-red-300 dark:ring-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                Suspend
            </button>
        </form>
        <?php elseif ($status === 'suspended'): ?>
        <!-- Activate -->
        <form method="POST" action="<?= APP_URL ?>/admin/stores/<?= (int)$store['id'] ?>/activate" id="detail-activate-form">
            <?= \App\Middleware\CsrfMiddleware::field() ?>
            <button
                type="button"
                onclick="confirmDetailAction('activate')"
                class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-green-600/30 hover:bg-green-700 transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Activate
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Store profile -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- Store info card -->
    <div class="lg:col-span-2 rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Store Information</h2>

        <div class="flex items-start gap-5 mb-6">
            <!-- Logo -->
            <?php if (!empty($store['logo'])): ?>
            <img src="<?= htmlspecialchars($store['logo'], ENT_QUOTES, 'UTF-8') ?>" alt="Store logo" class="h-16 w-16 rounded-xl object-cover ring-2 ring-gray-200 dark:ring-gray-600 flex-shrink-0">
            <?php else: ?>
            <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xl font-bold select-none">
                <?= htmlspecialchars(strtoupper(mb_substr($store['name'] ?? 'S', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php endif; ?>

            <div class="min-w-0 flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($store['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">ID: #<?= (int)($store['id'] ?? 0) ?></p>
            </div>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Owner Name</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($store['owner_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($store['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mobile</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($store['mobile'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</dt>
                <dd class="mt-1">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium <?= $badge['class'] ?>">
                        <span class="h-1.5 w-1.5 rounded-full <?= $badge['dot'] ?>" aria-hidden="true"></span>
                        <?= $badge['label'] ?>
                    </span>
                </dd>
            </div>
        </dl>

        <?php
            $hasAddress = !empty($store['address']) || !empty($store['city']) || !empty($store['state']) || !empty($store['pincode']);
        ?>
        <?php if ($hasAddress): ?>
        <div class="mt-5 pt-5 border-t border-gray-200 dark:border-gray-700">
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Address</dt>
            <dd class="text-sm text-gray-900 dark:text-white">
                <?php if (!empty($store['address'])): ?><?= htmlspecialchars($store['address'], ENT_QUOTES, 'UTF-8') ?><br><?php endif; ?>
                <?php
                    $parts = array_filter([
                        $store['city'] ?? '',
                        $store['state'] ?? '',
                        $store['pincode'] ?? '',
                    ]);
                    echo htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8');
                ?>
            </dd>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick stats sidebar -->
    <div class="space-y-4">
        <!-- Staff count -->
        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Staff Members</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= number_format((int)($store['staff_count'] ?? 0)) ?></p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                </div>
            </div>
        </div>

        <!-- Product count -->
        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Products</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= number_format((int)($store['product_count'] ?? 0)) ?></p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                </div>
            </div>
        </div>

        <!-- Total sales -->
        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Sales</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= number_format((int)($store['total_sales'] ?? 0)) ?></p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Confirmation modal -->
<div id="modal-confirm-action" role="dialog" aria-modal="true" hidden aria-hidden="true" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 dark:bg-gray-950/70" onclick="closeConfirmModal()"></div>
    <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="px-6 pt-6 pb-4 text-center">
            <div id="confirm-icon" class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full"></div>
            <h3 id="confirm-title" class="text-lg font-semibold text-gray-900 dark:text-white mb-1"></h3>
            <p id="confirm-message" class="text-sm text-gray-500 dark:text-gray-400"></p>
        </div>
        <div class="flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
            <button type="button" onclick="closeConfirmModal()" class="rounded-xl px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Cancel</button>
            <button type="button" id="confirm-submit-btn" onclick="submitConfirmAction()" class="rounded-xl px-4 py-2 text-sm font-semibold text-white transition-colors"></button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var pendingAction = null;
    var storeName = <?= json_encode($store['name'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    window.confirmDetailAction = function (action) {
        pendingAction = action;
        var icon = document.getElementById('confirm-icon');
        var title = document.getElementById('confirm-title');
        var message = document.getElementById('confirm-message');
        var btn = document.getElementById('confirm-submit-btn');

        if (action === 'suspend') {
            icon.className = 'mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30';
            icon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>';
            title.textContent = 'Suspend Store';
            message.innerHTML = 'Are you sure you want to suspend <strong class="text-gray-700 dark:text-gray-200">' + escHtml(storeName) + '</strong>?';
            btn.textContent = 'Suspend';
            btn.className = 'rounded-xl px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 transition-colors';
        } else {
            icon.className = 'mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30';
            icon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            title.textContent = 'Activate Store';
            message.innerHTML = 'Are you sure you want to activate <strong class="text-gray-700 dark:text-gray-200">' + escHtml(storeName) + '</strong>?';
            btn.textContent = 'Activate';
            btn.className = 'rounded-xl px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 transition-colors';
        }

        var modal = document.getElementById('modal-confirm-action');
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
    };

    window.submitConfirmAction = function () {
        if (pendingAction === 'suspend') {
            document.getElementById('detail-suspend-form').submit();
        } else if (pendingAction === 'activate') {
            document.getElementById('detail-activate-form').submit();
        }
    };

    window.closeConfirmModal = function () {
        var modal = document.getElementById('modal-confirm-action');
        modal.setAttribute('hidden', '');
        modal.setAttribute('aria-hidden', 'true');
        pendingAction = null;
    };

    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str)));
        return d.innerHTML;
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeConfirmModal();
    });
})();
</script>
