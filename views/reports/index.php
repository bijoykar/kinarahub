<?php
/**
 * views/reports/index.php — Reports hub with cards linking to each report.
 *
 * Rendered inside views/layouts/app.php.
 */
?>

<!-- Page header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reports</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Generate and export business reports</p>
</div>

<!-- Report cards grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

    <!-- Top Sellers -->
    <a href="/kinarahub/reports/top-sellers" class="group rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 hover:ring-brand-300 dark:hover:ring-brand-600 transition-all">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-100 dark:bg-brand-900/30 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M18.75 4.236c.982.143 1.954.317 2.916.52A6.003 6.003 0 0016.27 9.728M18.75 4.236V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.003 6.003 0 01-3.77 1.322 6.003 6.003 0 01-3.77-1.322"/></svg>
        </div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors mb-1">Top Sellers</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Products ranked by quantity sold, revenue, and profit margin for a date range.</p>
    </a>

    <!-- Inventory Aging -->
    <a href="/kinarahub/reports/aging" class="group rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 hover:ring-amber-300 dark:hover:ring-amber-600 transition-all">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors mb-1">Inventory Aging</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Products with no sales in the last 30, 60, or 90 days. Identify dead stock.</p>
    </a>

    <!-- Profit & Loss -->
    <a href="/kinarahub/reports/pnl" class="group rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 hover:ring-emerald-300 dark:hover:ring-emerald-600 transition-all">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/30 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
        </div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors mb-1">Profit & Loss</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Revenue, cost of goods sold, gross profit, and margin by category.</p>
    </a>

    <!-- Customer Dues -->
    <a href="/kinarahub/reports/customer-dues" class="group rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 hover:ring-red-300 dark:hover:ring-red-600 transition-all">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-100 dark:bg-red-900/30 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
        </div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors mb-1">Customer Dues</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Customers with outstanding credit balances. Export for follow-up.</p>
    </a>

    <!-- GST Summary -->
    <a href="/kinarahub/reports/gst" class="group rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 hover:ring-blue-300 dark:hover:ring-blue-600 transition-all">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/30 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
        </div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors mb-1">GST Summary</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Tax collected and total sales by period for GST filing reference.</p>
    </a>

</div>
