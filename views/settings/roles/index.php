<?php
/**
 * views/settings/roles/index.php — Roles listing page.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $roles      (array)  — List of role records: id, name, description, permission_count, staff_count, is_system
 *   $csrfToken  (string) — CSRF token for delete forms.
 */

$roles     = $roles ?? [];
$csrfToken = $csrfToken ?? '';

// Role badge colors by name/index
$badgeColors = [
    'Owner'   => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 ring-purple-600/20 dark:ring-purple-400/20',
    'Manager' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 ring-blue-600/20 dark:ring-blue-400/20',
    'Staff'   => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 ring-green-600/20 dark:ring-green-400/20',
];
$defaultBadge = 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 ring-gray-600/20 dark:ring-gray-500/20';
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Roles</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage roles and their permissions for your store</p>
    </div>
    <a
        href="/kinarahub/settings/roles/create"
        id="btn-new-item"
        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 transition-colors duration-150"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Create role
    </a>
</div>

<?php if (empty($roles)): ?>
<!-- Empty state -->
<div class="flex flex-col items-center justify-center py-16 text-center">
    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
        </svg>
    </div>
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No roles yet</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs">Create your first role to control what staff members can access.</p>
</div>

<?php else: ?>
<!-- Roles table -->
<div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Role</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Description</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Permissions</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Staff</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <?php foreach ($roles as $role):
                    $badgeClass = $badgeColors[$role['name']] ?? $defaultBadge;
                    $isSystem = !empty($role['is_system']);
                ?>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors duration-100">
                    <td class="whitespace-nowrap px-6 py-4">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset <?= $badgeClass ?>">
                            <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php if ($isSystem): ?>
                        <span class="ml-1.5 text-[10px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">System</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">
                        <?= htmlspecialchars($role['description'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-center">
                        <span class="inline-flex items-center justify-center rounded-lg bg-brand-50 dark:bg-brand-900/20 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:text-brand-300">
                            <?= (int)($role['permission_count'] ?? 0) ?>
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                        <?= (int)($role['staff_count'] ?? 0) ?>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a
                                href="/kinarahub/settings/roles/<?= (int)$role['id'] ?>/edit"
                                class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 dark:hover:text-brand-400 dark:hover:bg-brand-900/20 transition-colors duration-150"
                                title="Edit role"
                                aria-label="Edit <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?> role"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                </svg>
                            </a>
                            <?php if (!$isSystem): ?>
                            <button
                                type="button"
                                onclick="confirmDeleteRole(<?= (int)$role['id'] ?>, '<?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>')"
                                class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors duration-150"
                                title="Delete role"
                                aria-label="Delete <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?> role"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                </svg>
                            </button>
                            <?php else: ?>
                            <span class="inline-flex items-center rounded-lg p-2 text-gray-300 dark:text-gray-600 cursor-not-allowed" title="System roles cannot be deleted" aria-label="Cannot delete system role">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                </svg>
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Delete confirmation modal -->
<div
    id="modal-delete-role"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-delete-title"
    hidden
    aria-hidden="true"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900/60 dark:bg-gray-950/70" onclick="closeModal('modal-delete-role')"></div>
    <!-- Panel -->
    <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-2xl ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 dark:text-red-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h3 id="modal-delete-title" class="text-base font-semibold text-gray-900 dark:text-white">Delete role</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Are you sure you want to delete <span id="delete-role-name" class="font-medium text-gray-700 dark:text-gray-200"></span>?
                    Staff assigned to this role will need to be reassigned.
                </p>
            </div>
        </div>
        <div class="mt-5 flex gap-3 justify-end">
            <button
                type="button"
                onclick="closeModal('modal-delete-role')"
                class="rounded-xl px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150"
            >
                Cancel
            </button>
            <form id="delete-role-form" method="POST" action="">
                <?= \App\Middleware\CsrfMiddleware::field() ?>
                <input type="hidden" name="_method" value="DELETE">
                <button
                    type="submit"
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 shadow-sm transition-colors duration-150"
                >
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDeleteRole(id, name) {
    document.getElementById('delete-role-name').textContent = name;
    document.getElementById('delete-role-form').action = '/kinarahub/settings/roles/' + id + '/delete';
    openModal('modal-delete-role');
}
</script>
