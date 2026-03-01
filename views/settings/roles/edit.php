<?php
/**
 * views/settings/roles/edit.php — Role permission matrix editor.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $role            (array)  — Role record: id, name, description, is_system
 *   $permissions     (array)  — Assoc [module][action] => bool (e.g. $permissions['inventory']['create'] = true)
 *   $fieldRestrictions (array) — Assoc [field_key] => bool hidden (e.g. $fieldRestrictions['cost_price'] = true)
 *   $csrfToken       (string) — CSRF token.
 *   $errors          (array)  — Validation errors. Default: []
 *   $isNew           (bool)   — Whether creating a new role. Default: false
 */

$role              = $role ?? ['id' => 0, 'name' => '', 'description' => ''];
$permissions       = $permissions ?? [];
$fieldRestrictions = $fieldRestrictions ?? [];
$csrfToken         = $csrfToken ?? '';
$errors            = $errors ?? [];
$isNew             = $isNew ?? false;
$isSystem          = !empty($role['is_system']);

$modules = ['inventory', 'sales', 'customers', 'reports', 'settings'];
$actions = ['create', 'read', 'update', 'delete'];
$sensitiveFields = [
    'cost_price'       => 'Cost Price',
    'profit_margin'    => 'Profit Margin',
    'store_financials' => 'Store Financials',
];

$moduleIcons = [
    'inventory' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>',
    'sales'     => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    'customers' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z"/></svg>',
    'reports'   => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625z"/></svg>',
    'settings'  => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
];
?>

<!-- Page header -->
<div class="flex items-center gap-3 mb-6">
    <a
        href="<?= APP_URL ?>/settings/roles"
        class="inline-flex items-center justify-center rounded-lg p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors duration-150"
        aria-label="Back to roles"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <?= $isNew ? 'Create role' : 'Edit role: ' . htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Configure permissions and field-level access</p>
    </div>
</div>

<form
    method="POST"
    action="<?= $isNew ? APP_URL . '/settings/roles' : APP_URL . '/settings/roles/' . (int)$role['id'] ?>"
    class="space-y-6"
>
    <?= \App\Middleware\CsrfMiddleware::field() ?>
    <?php if (!$isNew): ?>
    <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <!-- Role name + description -->
    <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Role details</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="role-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Role name</label>
                <input
                    type="text"
                    id="role-name"
                    name="name"
                    value="<?= htmlspecialchars($role['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                    maxlength="50"
                    <?= $isSystem ? 'readonly' : '' ?>
                    class="block w-full rounded-xl border <?= !empty($errors['name']) ? 'border-red-400 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' ?> bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150 <?= $isSystem ? 'bg-gray-50 dark:bg-gray-700 cursor-not-allowed' : '' ?>"
                    placeholder="e.g. Cashier"
                >
                <?php if (!empty($errors['name'])): ?>
                <p class="mt-1.5 text-xs text-red-600 dark:text-red-400"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label for="role-desc" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description <span class="font-normal text-gray-400">(optional)</span></label>
                <input
                    type="text"
                    id="role-desc"
                    name="description"
                    value="<?= htmlspecialchars($role['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    maxlength="200"
                    class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
                    placeholder="Brief description of this role"
                >
            </div>
        </div>
    </div>

    <!-- Permission matrix -->
    <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Module permissions</h2>
            <?php if (!$isSystem): ?>
            <button type="button" onclick="toggleAllPermissions()" class="text-xs font-medium text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors">
                Toggle all
            </button>
            <?php endif; ?>
        </div>

        <div class="overflow-x-auto -mx-6 px-6">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="pb-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 min-w-[140px]">Module</th>
                        <?php foreach ($actions as $action): ?>
                        <th class="pb-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 w-24"><?= ucfirst($action) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <?php foreach ($modules as $module): ?>
                    <tr class="group">
                        <td class="py-3 pr-4">
                            <div class="flex items-center gap-2.5">
                                <span class="text-gray-400 dark:text-gray-500 group-hover:text-brand-500 transition-colors" aria-hidden="true">
                                    <?= $moduleIcons[$module] ?? '' ?>
                                </span>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize"><?= $module ?></span>
                            </div>
                        </td>
                        <?php foreach ($actions as $action):
                            $checked = !empty($permissions[$module][$action]);
                            $inputName = 'permissions[' . $module . '][' . $action . ']';
                        ?>
                        <td class="py-3 text-center">
                            <label class="inline-flex items-center justify-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="<?= $inputName ?>"
                                    value="1"
                                    <?= $checked ? 'checked' : '' ?>
                                    <?= $isSystem ? 'checked disabled' : '' ?>
                                    class="perm-checkbox h-4.5 w-4.5 rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-500 focus:ring-offset-0 dark:bg-gray-700 transition-colors duration-150 <?= $isSystem ? 'cursor-not-allowed opacity-50' : '' ?>"
                                >
                                <span class="sr-only"><?= ucfirst($action) ?> <?= $module ?></span>
                            </label>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Field-level restrictions -->
    <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Field restrictions</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Hide sensitive fields from users with this role</p>

        <div class="space-y-3">
            <?php foreach ($sensitiveFields as $fieldKey => $fieldLabel):
                $isHidden = !empty($fieldRestrictions[$fieldKey]);
            ?>
            <label class="flex items-center gap-3 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-700/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors duration-150 cursor-pointer group">
                <input
                    type="checkbox"
                    name="field_restrictions[<?= $fieldKey ?>]"
                    value="1"
                    <?= $isHidden ? 'checked' : '' ?>
                    <?= $isSystem ? 'disabled' : '' ?>
                    class="h-4.5 w-4.5 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500 focus:ring-offset-0 dark:bg-gray-700 transition-colors duration-150"
                >
                <div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($fieldLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="ml-2 inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-0.5 text-[10px] font-semibold text-red-700 dark:text-red-300 <?= $isHidden ? '' : 'hidden' ?>" data-badge="<?= $fieldKey ?>">
                        Hidden
                    </span>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end gap-3 pt-2">
        <a
            href="<?= APP_URL ?>/settings/roles"
            class="rounded-xl px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150"
        >
            Cancel
        </a>
        <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 transition-colors duration-150"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            <?= $isNew ? 'Create role' : 'Save changes' ?>
        </button>
    </div>
</form>

<script>
function toggleAllPermissions() {
    var checkboxes = document.querySelectorAll('.perm-checkbox:not(:disabled)');
    if (!checkboxes.length) return;
    // If all checked, uncheck all; else check all
    var allChecked = Array.from(checkboxes).every(function (cb) { return cb.checked; });
    checkboxes.forEach(function (cb) { cb.checked = !allChecked; });
}

// Toggle hidden badge visibility based on checkbox state
document.querySelectorAll('input[name^="field_restrictions"]').forEach(function (cb) {
    cb.addEventListener('change', function () {
        var key = cb.name.match(/\[(.+)\]/)[1];
        var badge = document.querySelector('[data-badge="' + key + '"]');
        if (badge) badge.classList.toggle('hidden', !cb.checked);
    });
});
</script>
