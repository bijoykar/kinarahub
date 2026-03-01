<?php
/**
 * views/dashboard/index.php — Dashboard stub (Phase 8 will populate with real widgets).
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $storeName (string) — Current store name from session.
 */

$storeName = htmlspecialchars($_SESSION['store_name'] ?? 'My Store', ENT_QUOTES, 'UTF-8');
$staffName = htmlspecialchars($_SESSION['staff_name'] ?? 'Staff', ENT_QUOTES, 'UTF-8');
?>

<!-- Welcome banner -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Welcome back, <?= $staffName ?>
    </h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Here's an overview of <?= $storeName ?> today.
    </p>
</div>

<!-- Placeholder KPI grid — will be replaced with real data in Phase 8 -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <?php
    $placeholderCards = [
        ['label' => 'Sales Today',       'value' => '--', 'icon' => 'currency-rupee', 'color' => 'brand'],
        ['label' => 'Sales This Week',    'value' => '--', 'icon' => 'chart-bar',      'color' => 'emerald'],
        ['label' => 'Out of Stock',       'value' => '--', 'icon' => 'exclamation',    'color' => 'red'],
        ['label' => 'Low Stock Items',    'value' => '--', 'icon' => 'bell',           'color' => 'amber'],
    ];

    foreach ($placeholderCards as $card):
        $bgClass = match($card['color']) {
            'brand'   => 'bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400',
            'emerald' => 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400',
            'red'     => 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400',
            'amber'   => 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400',
            default   => 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
        };
    ?>
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center gap-3 mb-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl <?= $bgClass ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $card['value'] ?></p>
    </div>
    <?php endforeach; ?>
</div>

<!-- Placeholder notice -->
<div class="rounded-2xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700/50 px-5 py-4">
    <div class="flex items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-blue-500 dark:text-blue-400 mt-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
        </svg>
        <div>
            <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Dashboard under construction</p>
            <p class="mt-1 text-sm text-blue-600 dark:text-blue-300">
                Full dashboard widgets with sales data, charts, and stock alerts will be available once inventory and sales modules are active.
            </p>
        </div>
    </div>
</div>
