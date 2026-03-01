<?php
/**
 * views/admin/dashboard.php — Platform admin dashboard.
 *
 * Rendered inside views/layouts/admin.php.
 *
 * Expected variables:
 *   $stats (array) — Platform-wide statistics:
 *     total_stores, active_stores, pending_verification, suspended_stores,
 *     total_sales_volume, total_revenue, stores_this_month
 */

$stats = $stats ?? [
    'total_stores'          => 0,
    'active_stores'         => 0,
    'pending_verification'  => 0,
    'suspended_stores'      => 0,
    'total_sales_volume'    => 0,
    'total_revenue'         => 0,
    'stores_this_month'     => 0,
];
?>

<!-- Page header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Platform Overview</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All stores managed by Kinara Hub</p>
</div>

<!-- Stat cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

    <!-- Total Stores -->
    <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Stores</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/></svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= number_format($stats['total_stores']) ?></p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            <?= number_format($stats['stores_this_month']) ?> registered this month
        </p>
    </div>

    <!-- Active Stores -->
    <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Active</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-green-600 dark:text-green-400"><?= number_format($stats['active_stores']) ?></p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            <?php
                $pct = $stats['total_stores'] > 0
                    ? round(($stats['active_stores'] / $stats['total_stores']) * 100)
                    : 0;
            ?>
            <?= $pct ?>% of total stores
        </p>
    </div>

    <!-- Pending Verification -->
    <a href="/kinarahub/admin/stores?status=pending_verification" class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 hover:ring-amber-300 dark:hover:ring-amber-600 transition-all group">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Pending</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-amber-600 dark:text-amber-400"><?= number_format($stats['pending_verification']) ?></p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
            Awaiting email verification
        </p>
    </a>

    <!-- Suspended Stores -->
    <a href="/kinarahub/admin/stores?status=suspended" class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 hover:ring-red-300 dark:hover:ring-red-600 transition-all group">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Suspended</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-red-600 dark:text-red-400"><?= number_format($stats['suspended_stores']) ?></p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
            Currently suspended
        </p>
    </a>

</div>

<!-- Revenue summary -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">

    <!-- Total Sales Volume -->
    <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Sales (All Stores)</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= number_format($stats['total_sales_volume']) ?></p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Total transactions processed</p>
    </div>

    <!-- Total Revenue -->
    <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Revenue</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400"><?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format((float)$stats['total_revenue'], 2) ?></p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Across all stores, all time</p>
    </div>

</div>

<!-- Quick actions -->
<div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <a href="/kinarahub/admin/stores" class="flex items-center gap-3 rounded-xl bg-gray-50 dark:bg-gray-700/50 px-4 py-3.5 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Manage Stores</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">View, activate, or suspend</p>
            </div>
        </a>

        <a href="/kinarahub/admin/stores?status=pending_verification" class="flex items-center gap-3 rounded-xl bg-gray-50 dark:bg-gray-700/50 px-4 py-3.5 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Pending Stores</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Review unverified stores</p>
            </div>
        </a>

        <a href="/kinarahub/admin/stores?status=suspended" class="flex items-center gap-3 rounded-xl bg-gray-50 dark:bg-gray-700/50 px-4 py-3.5 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">Suspended Stores</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Review or reactivate</p>
            </div>
        </a>
    </div>
</div>
