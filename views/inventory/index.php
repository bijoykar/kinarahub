<?php
/**
 * views/inventory/index.php — Inventory management page.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $products       (array)  — Paginated product records with stock_status computed.
 *   $categories     (array)  — [{id, name}, ...] for filter dropdown.
 *   $units          (array)  — [{id, name, abbreviation}, ...] UOM list.
 *   $pagination     (array)  — {page, per_page, total, total_pages}
 *   $filters        (array)  — {search, category_id, status} current filter values.
 *   $csrfToken      (string) — CSRF token.
 *   $hideCostPrice  (bool)   — Whether cost_price should be hidden for current role. Default: false
 */

$products      = $products ?? [];
$categories    = $categories ?? [];
$units         = $units ?? [];
$pagination    = $pagination ?? ['page' => 1, 'per_page' => 25, 'total' => 0, 'total_pages' => 1];
$filters       = $filters ?? ['search' => '', 'category_id' => '', 'status' => ''];
$csrfToken     = $csrfToken ?? '';
$hideCostPrice = $hideCostPrice ?? false;

$statusBadges = [
    'in_stock'     => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
    'low_stock'    => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
    'out_of_stock' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
];
$statusLabels = [
    'in_stock'     => 'In Stock',
    'low_stock'    => 'Low Stock',
    'out_of_stock' => 'Out of Stock',
];
$statusDots = [
    'in_stock'     => 'bg-green-500',
    'low_stock'    => 'bg-amber-500',
    'out_of_stock' => 'bg-red-500',
];
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Inventory</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            <?= number_format($pagination['total']) ?> product<?= $pagination['total'] !== 1 ? 's' : '' ?> total
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            onclick="openModal('modal-import')"
            class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-gray-700 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150"
            aria-label="Import products from CSV"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>
            Import CSV
        </button>
        <a
            href="<?= APP_URL ?>/inventory/export"
            class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-gray-700 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150"
            aria-label="Export inventory to CSV"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            Export CSV
        </a>
        <button
            type="button"
            id="btn-new-item"
            onclick="openProductModal()"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 transition-colors duration-150"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add product
        </button>
    </div>
</div>

<!-- Filter bar -->
<div class="mb-4 flex flex-col sm:flex-row gap-3">
    <form method="GET" action="<?= APP_URL ?>/inventory" class="flex flex-1 flex-wrap gap-3" id="filter-form">
        <!-- Search -->
        <div class="relative flex-1 min-w-[200px]">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400 dark:text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </span>
            <input
                type="search"
                name="search"
                value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Search by name or SKU..."
                class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
            >
        </div>

        <!-- Category filter -->
        <select
            name="category_id"
            onchange="document.getElementById('filter-form').submit()"
            class="rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150 appearance-none min-w-[140px]"
            style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3E%3Cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27m6 8 4 4 4-4%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em;"
        >
            <option value="">All categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>" <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
            </option>
            <?php endforeach; ?>
        </select>

        <!-- Status filter -->
        <select
            name="status"
            onchange="document.getElementById('filter-form').submit()"
            class="rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150 appearance-none min-w-[140px]"
            style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3E%3Cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27m6 8 4 4 4-4%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em;"
        >
            <option value="">All statuses</option>
            <option value="in_stock" <?= ($filters['status'] ?? '') === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
            <option value="low_stock" <?= ($filters['status'] ?? '') === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
            <option value="out_of_stock" <?= ($filters['status'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
        </select>
    </form>
</div>

<?php if (empty($products)): ?>
<!-- Empty state -->
<div class="flex flex-col items-center justify-center py-16 text-center rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
        </svg>
    </div>
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No products found</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs mb-4">
        <?php if (!empty($filters['search']) || !empty($filters['category_id']) || !empty($filters['status'])): ?>
        Try adjusting your filters or search terms.
        <?php else: ?>
        Get started by adding your first product or importing from CSV.
        <?php endif; ?>
    </p>
</div>

<?php else: ?>
<!-- Products table -->
<div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">SKU</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Product</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Category</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Selling Price</th>
                    <?php if (!$hideCostPrice): ?>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Cost Price</th>
                    <?php endif; ?>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Stock</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <?php foreach ($products as $product):
                    $stockStatus = $product['stock_status'] ?? 'in_stock';
                    $badge = $statusBadges[$stockStatus] ?? $statusBadges['in_stock'];
                    $badgeLabel = $statusLabels[$stockStatus] ?? 'In Stock';
                    $dot = $statusDots[$stockStatus] ?? $statusDots['in_stock'];
                ?>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors duration-100" data-product-id="<?= (int)$product['id'] ?>">
                    <td class="whitespace-nowrap px-6 py-4">
                        <span class="font-mono text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-md">
                            <?= htmlspecialchars($product['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[200px]">
                                <?= htmlspecialchars($product['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <?php if (!empty($product['uom_abbreviation'])): ?>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($product['uom_abbreviation'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-white">
                        <?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format((float)($product['selling_price'] ?? 0), 2) ?>
                    </td>
                    <?php if (!$hideCostPrice): ?>
                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-600 dark:text-gray-400">
                        <?= CURRENCY_SYMBOL ?? '₹' ?><?= number_format((float)($product['cost_price'] ?? 0), 2) ?>
                    </td>
                    <?php endif; ?>
                    <td class="whitespace-nowrap px-6 py-4 text-right">
                        <span class="text-sm font-semibold <?= $stockStatus === 'out_of_stock' ? 'text-red-600 dark:text-red-400' : ($stockStatus === 'low_stock' ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white') ?>">
                            <?= number_format((float)($product['stock_quantity'] ?? 0), $product['stock_quantity'] == (int)$product['stock_quantity'] ? 0 : 3) ?>
                        </span>
                        <?php if (!empty($product['reorder_point'])): ?>
                        <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">/ <?= number_format((float)$product['reorder_point'], 0) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-center">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium <?= $badge ?>">
                            <span class="h-1.5 w-1.5 rounded-full <?= $dot ?>" aria-hidden="true"></span>
                            <?= $badgeLabel ?>
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button
                                type="button"
                                onclick='openProductModal(<?= json_encode($product, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 dark:hover:text-brand-400 dark:hover:bg-brand-900/20 transition-colors duration-150"
                                title="Edit product"
                                aria-label="Edit <?= htmlspecialchars($product['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                </svg>
                            </button>
                            <button
                                type="button"
                                onclick="deleteProduct(<?= (int)$product['id'] ?>, '<?= htmlspecialchars($product['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>')"
                                class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors duration-150"
                                title="Delete product"
                                aria-label="Delete <?= htmlspecialchars($product['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                </svg>
                            </button>
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
            <a href="?page=<?= $pagination['page'] - 1 ?>&search=<?= urlencode($filters['search'] ?? '') ?>&category_id=<?= urlencode($filters['category_id'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>"
               class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
               aria-label="Previous page">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                Prev
            </a>
            <?php endif; ?>

            <?php
            $start = max(1, $pagination['page'] - 2);
            $end = min($pagination['total_pages'], $pagination['page'] + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($filters['search'] ?? '') ?>&category_id=<?= urlencode($filters['category_id'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>"
               class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-medium <?= $i === $pagination['page'] ? 'bg-brand-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' ?> transition-colors duration-150 min-w-[36px]"
               <?= $i === $pagination['page'] ? 'aria-current="page"' : '' ?>>
                <?= $i ?>
            </a>
            <?php endfor; ?>

            <?php if ($pagination['page'] < $pagination['total_pages']): ?>
            <a href="?page=<?= $pagination['page'] + 1 ?>&search=<?= urlencode($filters['search'] ?? '') ?>&category_id=<?= urlencode($filters['category_id'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>"
               class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
               aria-label="Next page">
                Next
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>


<!-- ================================================================
     ADD/EDIT PRODUCT MODAL
     ================================================================ -->
<div
    id="modal-product"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-product-title"
    hidden
    aria-hidden="true"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <div class="absolute inset-0 bg-gray-900/60 dark:bg-gray-950/70" onclick="closeModal('modal-product')"></div>
    <div class="relative w-full max-w-2xl max-h-[90vh] flex flex-col rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-gray-200 dark:ring-gray-700">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex-shrink-0">
            <h3 id="modal-product-title" class="text-lg font-semibold text-gray-900 dark:text-white">Add product</h3>
            <button type="button" onclick="closeModal('modal-product')" class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors duration-150" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
            </button>
        </div>
        <!-- Body -->
        <div class="overflow-y-auto flex-1 px-6 py-5">
            <form id="product-form" method="POST" action="<?= APP_URL ?>/inventory" class="space-y-4">
                <?= \App\Middleware\CsrfMiddleware::field() ?>
                <input type="hidden" id="product-id" name="id" value="">
                <input type="hidden" id="product-method" name="_method" value="POST">
                <input type="hidden" id="product-version" name="version" value="0">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- SKU -->
                    <div>
                        <label for="prod-sku" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">SKU</label>
                        <input type="text" id="prod-sku" name="sku" required maxlength="50" oninput="this.value=this.value.toUpperCase()"
                               placeholder="e.g. RICE-5KG" class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150 font-mono uppercase">
                    </div>
                    <!-- Name -->
                    <div>
                        <label for="prod-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Product name</label>
                        <input type="text" id="prod-name" name="name" required maxlength="200"
                               placeholder="e.g. Basmati Rice 5kg" class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150">
                    </div>
                    <!-- Category -->
                    <div>
                        <label for="prod-category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category</label>
                        <select id="prod-category" name="category_id"
                                class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150 appearance-none"
                                style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3E%3Cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27m6 8 4 4 4-4%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em;">
                            <option value="">Select category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- UOM -->
                    <div>
                        <label for="prod-uom" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Unit of measure</label>
                        <select id="prod-uom" name="uom_id" required
                                class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150 appearance-none"
                                style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3E%3Cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27m6 8 4 4 4-4%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em;">
                            <option value="">Select unit</option>
                            <?php foreach ($units as $unit): ?>
                            <option value="<?= (int)$unit['id'] ?>"><?= htmlspecialchars($unit['name'] . ' (' . $unit['abbreviation'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Selling Price -->
                    <div>
                        <label for="prod-selling" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Selling price (<?= CURRENCY_SYMBOL ?? '₹' ?>)</label>
                        <input type="number" id="prod-selling" name="selling_price" required min="0" step="0.01"
                               placeholder="0.00" class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150">
                    </div>
                    <!-- Cost Price -->
                    <?php if (!$hideCostPrice): ?>
                    <div>
                        <label for="prod-cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Cost price (<?= CURRENCY_SYMBOL ?? '₹' ?>)</label>
                        <input type="number" id="prod-cost" name="cost_price" min="0" step="0.01"
                               placeholder="0.00" class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150">
                    </div>
                    <?php endif; ?>
                    <!-- Stock Quantity -->
                    <div>
                        <label for="prod-stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Stock quantity</label>
                        <input type="number" id="prod-stock" name="stock_quantity" required min="0" step="0.001"
                               placeholder="0" class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150">
                    </div>
                    <!-- Reorder Point -->
                    <div>
                        <label for="prod-reorder" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Reorder point</label>
                        <input type="number" id="prod-reorder" name="reorder_point" min="0" step="0.001"
                               placeholder="0" class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150">
                    </div>
                </div>

                <!-- Variants section -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Variants <span class="font-normal text-gray-400">(optional)</span></h4>
                        <button type="button" onclick="addVariantRow()" class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add variant
                        </button>
                    </div>
                    <div id="variants-container" class="space-y-3">
                        <!-- Variant rows injected by JS -->
                    </div>
                </div>
            </form>
        </div>
        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex-shrink-0">
            <button type="button" onclick="closeModal('modal-product')" class="rounded-xl px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">Cancel</button>
            <button type="submit" form="product-form" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                <span id="product-submit-text">Add product</span>
            </button>
        </div>
    </div>
</div>


<!-- ================================================================
     IMPORT CSV MODAL
     ================================================================ -->
<div
    id="modal-import"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-import-title"
    hidden
    aria-hidden="true"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <div class="absolute inset-0 bg-gray-900/60 dark:bg-gray-950/70" onclick="closeModal('modal-import')"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
            <h3 id="modal-import-title" class="text-lg font-semibold text-gray-900 dark:text-white">Import products from CSV</h3>
            <button type="button" onclick="closeModal('modal-import')" class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors duration-150" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
            </button>
        </div>
        <form id="import-form" method="POST" action="<?= APP_URL ?>/inventory/import" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
            <?= \App\Middleware\CsrfMiddleware::field() ?>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Upload a CSV file to bulk import or update products. Existing SKUs will be updated; new SKUs will be inserted.
            </p>

            <!-- Template download -->
            <button type="button" onclick="downloadCsvTemplate()" class="inline-flex items-center gap-2 text-sm font-medium text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Download CSV template
            </button>

            <!-- File upload -->
            <div>
                <label for="import-file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">CSV file</label>
                <input type="file" id="import-file" name="csv_file" required accept=".csv,text/csv"
                       class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 dark:file:bg-brand-900/30 dark:file:text-brand-300 hover:file:bg-brand-100 dark:hover:file:bg-brand-900/40 file:cursor-pointer file:transition-colors">
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
            <button type="button" onclick="closeModal('modal-import')" class="rounded-xl px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">Cancel</button>
            <button type="submit" form="import-form" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                Import
            </button>
        </div>
    </div>
</div>


<!-- ================================================================
     IMPORT RESULT MODAL
     ================================================================ -->
<div
    id="modal-import-result"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-import-result-title"
    hidden
    aria-hidden="true"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <div class="absolute inset-0 bg-gray-900/60 dark:bg-gray-950/70" onclick="closeModal('modal-import-result')"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-gray-200 dark:ring-gray-700 p-6">
        <h3 id="modal-import-result-title" class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Import results</h3>
        <div class="space-y-3" id="import-result-body">
            <!-- Filled dynamically -->
        </div>
        <div class="mt-5 flex justify-end">
            <button type="button" onclick="closeModal('modal-import-result'); location.reload();" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 transition-colors duration-150">Done</button>
        </div>
    </div>
</div>


<!-- ================================================================
     JAVASCRIPT
     ================================================================ -->
<script>
(function () {
    'use strict';

    var variantCounter = 0;

    // ------------------------------------------------------------------
    // Product modal
    // ------------------------------------------------------------------
    window.openProductModal = function (product) {
        var isEdit = product && product.id;
        document.getElementById('modal-product-title').textContent = isEdit ? 'Edit product' : 'Add product';
        document.getElementById('product-submit-text').textContent = isEdit ? 'Save changes' : 'Add product';

        var form = document.getElementById('product-form');
        if (isEdit) {
            form.action = '<?= APP_URL ?>/inventory/' + product.id;
            document.getElementById('product-id').value = product.id;
            document.getElementById('product-method').value = 'PUT';
            document.getElementById('product-version').value = product.version || 0;
            document.getElementById('prod-sku').value = product.sku || '';
            document.getElementById('prod-name').value = product.name || '';
            document.getElementById('prod-category').value = product.category_id || '';
            document.getElementById('prod-uom').value = product.uom_id || '';
            document.getElementById('prod-selling').value = product.selling_price || '';
            var costField = document.getElementById('prod-cost');
            if (costField) costField.value = product.cost_price || '';
            document.getElementById('prod-stock').value = product.stock_quantity || '';
            document.getElementById('prod-reorder').value = product.reorder_point || '';
        } else {
            form.action = '<?= APP_URL ?>/inventory';
            document.getElementById('product-id').value = '';
            document.getElementById('product-method').value = 'POST';
            document.getElementById('product-version').value = '0';
            form.reset();
        }

        // Clear variants and load if editing
        document.getElementById('variants-container').innerHTML = '';
        variantCounter = 0;
        if (isEdit && product.variants && product.variants.length) {
            product.variants.forEach(function (v) { addVariantRow(v); });
        }

        openModal('modal-product');
    };

    // ------------------------------------------------------------------
    // Variant rows
    // ------------------------------------------------------------------
    window.addVariantRow = function (data) {
        data = data || {};
        var idx = variantCounter++;
        var container = document.getElementById('variants-container');

        var row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-2 items-end p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 ring-1 ring-gray-200 dark:ring-gray-700';
        row.id = 'variant-row-' + idx;

        row.innerHTML = [
            '<div class="col-span-3">',
            '  <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">Name</label>',
            '  <input type="text" name="variants[' + idx + '][variant_name]" value="' + escAttr(data.variant_name || '') + '" placeholder="Red / L" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2.5 py-1.5 text-xs text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 focus:outline-none">',
            '</div>',
            '<div class="col-span-2">',
            '  <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">SKU</label>',
            '  <input type="text" name="variants[' + idx + '][sku]" value="' + escAttr(data.sku || '') + '" oninput="this.value=this.value.toUpperCase()" placeholder="SKU" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2.5 py-1.5 text-xs text-gray-900 dark:text-white font-mono uppercase focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 focus:outline-none">',
            '</div>',
            '<div class="col-span-2">',
            '  <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">Price</label>',
            '  <input type="number" name="variants[' + idx + '][selling_price]" value="' + escAttr(data.selling_price || '') + '" min="0" step="0.01" placeholder="0" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2.5 py-1.5 text-xs text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 focus:outline-none">',
            '</div>',
            '<div class="col-span-2">',
            '  <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">Stock</label>',
            '  <input type="number" name="variants[' + idx + '][stock_quantity]" value="' + escAttr(data.stock_quantity || '') + '" min="0" step="0.001" placeholder="0" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2.5 py-1.5 text-xs text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 focus:outline-none">',
            '</div>',
            '<div class="col-span-2">',
            '  <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">Reorder</label>',
            '  <input type="number" name="variants[' + idx + '][reorder_point]" value="' + escAttr(data.reorder_point || '') + '" min="0" step="0.001" placeholder="0" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2.5 py-1.5 text-xs text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 focus:outline-none">',
            '</div>',
            '<div class="col-span-1 flex justify-center">',
            '  <button type="button" onclick="removeVariantRow(' + idx + ')" class="rounded-lg p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" aria-label="Remove variant" title="Remove variant">',
            '    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>',
            '  </button>',
            '</div>',
        ].join('');

        if (data.id) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'variants[' + idx + '][id]';
            hidden.value = data.id;
            row.appendChild(hidden);
        }

        container.appendChild(row);
    };

    window.removeVariantRow = function (idx) {
        var row = document.getElementById('variant-row-' + idx);
        if (row) row.remove();
    };

    function escAttr(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML.replace(/"/g, '&quot;');
    }

    // ------------------------------------------------------------------
    // Delete with undo toast
    // ------------------------------------------------------------------
    window.deleteProduct = function (id, name) {
        var row = document.querySelector('[data-product-id="' + id + '"]');
        if (row) row.style.opacity = '0.4';

        if (typeof showUndoToast === 'function') {
            showUndoToast('"' + name + '" deleted.', function () {
                // Undo — restore row
                if (row) row.style.opacity = '1';
            }, 5000);
        }

        // Actually delete after 5 seconds
        setTimeout(function () {
            if (row && row.style.opacity === '0.4') {
                // Send delete request
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= APP_URL ?>/inventory/' + id + '/delete';
                form.style.display = 'none';

                var csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = 'csrf_token';
                csrf.value = '<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>';
                form.appendChild(csrf);

                var method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);

                document.body.appendChild(form);
                form.submit();
            }
        }, 5200);
    };

    // ------------------------------------------------------------------
    // CSV template download
    // ------------------------------------------------------------------
    window.downloadCsvTemplate = function () {
        var headers = 'sku,name,category,uom,selling_price,cost_price,stock_quantity,reorder_point\n';
        var example = 'RICE-5KG,Basmati Rice 5kg,Grains,Kg,350.00,280.00,50,10\n';
        var blob = new Blob([headers + example], { type: 'text/csv' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'kinarahub-inventory-template.csv';
        a.click();
        URL.revokeObjectURL(url);
    };

    // ------------------------------------------------------------------
    // Show import results (called by controller after redirect)
    // ------------------------------------------------------------------
    window.showImportResult = function (result) {
        var body = document.getElementById('import-result-body');
        var html = '';

        if (result.inserted > 0) {
            html += '<div class="flex items-center gap-3 rounded-xl bg-green-50 dark:bg-green-900/20 px-4 py-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg><span class="text-sm font-medium text-green-800 dark:text-green-200">' + result.inserted + ' product(s) inserted</span></div>';
        }
        if (result.updated > 0) {
            html += '<div class="flex items-center gap-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 px-4 py-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/></svg><span class="text-sm font-medium text-blue-800 dark:text-blue-200">' + result.updated + ' product(s) updated</span></div>';
        }
        if (result.failed > 0) {
            html += '<div class="rounded-xl bg-red-50 dark:bg-red-900/20 px-4 py-3"><div class="flex items-center gap-3 mb-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg><span class="text-sm font-medium text-red-800 dark:text-red-200">' + result.failed + ' row(s) failed</span></div>';
            if (result.errors && result.errors.length) {
                html += '<ul class="ml-8 space-y-1">';
                result.errors.forEach(function (err) {
                    html += '<li class="text-xs text-red-700 dark:text-red-300">Row ' + err.row + ': ' + escHtml(err.message) + '</li>';
                });
                html += '</ul>';
            }
            html += '</div>';
        }

        body.innerHTML = html;
        openModal('modal-import-result');
    };

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML;
    }
})();
</script>
