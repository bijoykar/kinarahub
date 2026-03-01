<?php
/**
 * views/settings/staff/index.php — Staff listing page.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $staff      (array)  — List of staff records: id, name, email, mobile, role_name, status, created_at
 *   $roles      (array)  — List of roles for the dropdown: id, name
 *   $csrfToken  (string) — CSRF token.
 */

$staff     = $staff ?? [];
$roles     = $roles ?? [];
$csrfToken = $csrfToken ?? '';

$roleBadgeColors = [
    'Owner'   => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 ring-purple-600/20 dark:ring-purple-400/20',
    'Manager' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 ring-blue-600/20 dark:ring-blue-400/20',
    'Staff'   => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 ring-green-600/20 dark:ring-green-400/20',
];
$defaultRoleBadge = 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 ring-gray-600/20 dark:ring-gray-500/20';
?>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Staff</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your store team members and their roles</p>
    </div>
    <button
        type="button"
        id="btn-new-item"
        onclick="openStaffModal()"
        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 transition-colors duration-150"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
        </svg>
        Add staff
    </button>
</div>

<?php if (empty($staff)): ?>
<!-- Empty state -->
<div class="flex flex-col items-center justify-center py-16 text-center">
    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
        </svg>
    </div>
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No staff members yet</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs">Add your first team member to get started with role-based access control.</p>
</div>

<?php else: ?>
<!-- Staff table -->
<div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Role</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <?php foreach ($staff as $member):
                    $roleBadge = $roleBadgeColors[$member['role_name'] ?? ''] ?? $defaultRoleBadge;
                    $isActive = ($member['status'] ?? 'active') === 'active';
                ?>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors duration-100">
                    <td class="whitespace-nowrap px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 text-xs font-semibold uppercase select-none" aria-hidden="true">
                                <?= htmlspecialchars(strtoupper(mb_substr($member['name'] ?? 'S', 0, 2)), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate"><?= htmlspecialchars($member['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if (!empty($member['mobile'])): ?>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($member['mobile'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        <?= htmlspecialchars($member['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset <?= $roleBadge ?>">
                            <?= htmlspecialchars($member['role_name'] ?? 'Staff', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-center">
                        <?php if ($isActive): ?>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-1 text-xs font-medium text-green-700 dark:text-green-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-green-500" aria-hidden="true"></span>
                            Active
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400" aria-hidden="true"></span>
                            Inactive
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button
                                type="button"
                                onclick='openStaffModal(<?= json_encode($member, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 dark:hover:text-brand-400 dark:hover:bg-brand-900/20 transition-colors duration-150"
                                title="Edit staff member"
                                aria-label="Edit <?= htmlspecialchars($member['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                </svg>
                            </button>
                            <button
                                type="button"
                                onclick="confirmDeleteStaff(<?= (int)$member['id'] ?>, '<?= htmlspecialchars($member['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>')"
                                class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors duration-150"
                                title="Remove staff member"
                                aria-label="Remove <?= htmlspecialchars($member['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
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
</div>
<?php endif; ?>

<!-- Staff create/edit modal -->
<div
    id="modal-staff"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-staff-title"
    hidden
    aria-hidden="true"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900/60 dark:bg-gray-950/70" onclick="closeModal('modal-staff')"></div>
    <!-- Panel -->
    <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-gray-200 dark:ring-gray-700">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
            <h3 id="modal-staff-title" class="text-lg font-semibold text-gray-900 dark:text-white">Add staff member</h3>
            <button
                type="button"
                onclick="closeModal('modal-staff')"
                class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors duration-150"
                aria-label="Close"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                </svg>
            </button>
        </div>
        <!-- Body -->
        <form id="staff-form" method="POST" action="/kinarahub/settings/staff" class="px-6 py-5 space-y-4">
            <?= \App\Middleware\CsrfMiddleware::field() ?>
            <input type="hidden" id="staff-id" name="id" value="">
            <input type="hidden" id="staff-method" name="_method" value="POST">

            <!-- Name -->
            <div>
                <label for="staff-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Full name</label>
                <input
                    type="text"
                    id="staff-name"
                    name="name"
                    required
                    maxlength="100"
                    placeholder="Staff member's name"
                    class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
                >
            </div>

            <!-- Email -->
            <div>
                <label for="staff-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email address</label>
                <input
                    type="email"
                    id="staff-email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="staff@example.com"
                    oninput="this.value=this.value.toLowerCase()"
                    class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
                >
            </div>

            <!-- Mobile -->
            <div>
                <label for="staff-mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Mobile number</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-gray-400 dark:text-gray-500 select-none pointer-events-none">+91</span>
                    <input
                        type="tel"
                        id="staff-mobile"
                        name="mobile"
                        maxlength="10"
                        pattern="[0-9]{10}"
                        inputmode="numeric"
                        placeholder="9876543210"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                        class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 pl-12 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
                    >
                </div>
            </div>

            <!-- Password (create only) -->
            <div id="staff-password-wrap">
                <label for="staff-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Password <span id="staff-pw-optional" class="font-normal text-gray-400 hidden">(leave blank to keep current)</span>
                </label>
                <input
                    type="password"
                    id="staff-password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Min. 8 characters"
                    class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
                >
            </div>

            <!-- Role -->
            <div>
                <label for="staff-role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Role</label>
                <select
                    id="staff-role"
                    name="role_id"
                    required
                    class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150 appearance-none"
                    style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3E%3Cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27m6 8 4 4 4-4%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em;"
                >
                    <option value="">Select a role</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= (int)$role['id'] ?>"><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status (edit mode only) -->
            <div id="staff-status-wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        id="staff-status-toggle"
                        role="switch"
                        aria-checked="true"
                        onclick="toggleStaffStatus()"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-green-500 transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                    >
                        <span class="translate-x-5 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                        </span>
                    </button>
                    <input type="hidden" id="staff-status" name="status" value="active">
                    <span id="staff-status-label" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                </div>
            </div>
        </form>
        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
            <button
                type="button"
                onclick="closeModal('modal-staff')"
                class="rounded-xl px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150"
            >
                Cancel
            </button>
            <button
                type="submit"
                form="staff-form"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 transition-colors duration-150"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                <span id="staff-submit-text">Add staff</span>
            </button>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div
    id="modal-delete-staff"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-delete-staff-title"
    hidden
    aria-hidden="true"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <div class="absolute inset-0 bg-gray-900/60 dark:bg-gray-950/70" onclick="closeModal('modal-delete-staff')"></div>
    <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-2xl ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 dark:text-red-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h3 id="modal-delete-staff-title" class="text-base font-semibold text-gray-900 dark:text-white">Remove staff member</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Are you sure you want to remove <span id="delete-staff-name" class="font-medium text-gray-700 dark:text-gray-200"></span>? This action cannot be undone.
                </p>
            </div>
        </div>
        <div class="mt-5 flex gap-3 justify-end">
            <button type="button" onclick="closeModal('modal-delete-staff')" class="rounded-xl px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">Cancel</button>
            <form id="delete-staff-form" method="POST" action="">
                <?= \App\Middleware\CsrfMiddleware::field() ?>
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="rounded-xl px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 shadow-sm transition-colors duration-150">Remove</button>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    // Staff modal controller
    window.openStaffModal = function (member) {
        var isEdit = member && member.id;
        var title = document.getElementById('modal-staff-title');
        var submitText = document.getElementById('staff-submit-text');
        var form = document.getElementById('staff-form');
        var statusWrap = document.getElementById('staff-status-wrap');
        var pwOptional = document.getElementById('staff-pw-optional');
        var pwInput = document.getElementById('staff-password');

        if (isEdit) {
            title.textContent = 'Edit staff member';
            submitText.textContent = 'Save changes';
            form.action = '/kinarahub/settings/staff/' + member.id;
            document.getElementById('staff-id').value = member.id;
            document.getElementById('staff-method').value = 'PUT';
            document.getElementById('staff-name').value = member.name || '';
            document.getElementById('staff-email').value = member.email || '';
            document.getElementById('staff-mobile').value = member.mobile || '';
            document.getElementById('staff-role').value = member.role_id || '';
            pwInput.value = '';
            pwInput.removeAttribute('required');
            pwOptional.classList.remove('hidden');
            statusWrap.classList.remove('hidden');
            setStaffStatusUI(member.status === 'active');
        } else {
            title.textContent = 'Add staff member';
            submitText.textContent = 'Add staff';
            form.action = '/kinarahub/settings/staff';
            document.getElementById('staff-id').value = '';
            document.getElementById('staff-method').value = 'POST';
            document.getElementById('staff-name').value = '';
            document.getElementById('staff-email').value = '';
            document.getElementById('staff-mobile').value = '';
            document.getElementById('staff-password').value = '';
            document.getElementById('staff-role').value = '';
            pwInput.setAttribute('required', '');
            pwOptional.classList.add('hidden');
            statusWrap.classList.add('hidden');
        }

        openModal('modal-staff');
    };

    // Status toggle
    window.toggleStaffStatus = function () {
        var toggle = document.getElementById('staff-status-toggle');
        var input = document.getElementById('staff-status');
        var label = document.getElementById('staff-status-label');
        var isActive = toggle.getAttribute('aria-checked') === 'true';

        if (isActive) {
            toggle.setAttribute('aria-checked', 'false');
            toggle.classList.remove('bg-green-500');
            toggle.classList.add('bg-gray-300', 'dark:bg-gray-600');
            toggle.querySelector('span').classList.remove('translate-x-5');
            toggle.querySelector('span').classList.add('translate-x-0');
            input.value = 'inactive';
            label.textContent = 'Inactive';
        } else {
            toggle.setAttribute('aria-checked', 'true');
            toggle.classList.add('bg-green-500');
            toggle.classList.remove('bg-gray-300', 'dark:bg-gray-600');
            toggle.querySelector('span').classList.add('translate-x-5');
            toggle.querySelector('span').classList.remove('translate-x-0');
            input.value = 'active';
            label.textContent = 'Active';
        }
    };

    function setStaffStatusUI(active) {
        var toggle = document.getElementById('staff-status-toggle');
        var input = document.getElementById('staff-status');
        var label = document.getElementById('staff-status-label');
        if (active) {
            toggle.setAttribute('aria-checked', 'true');
            toggle.classList.add('bg-green-500');
            toggle.classList.remove('bg-gray-300', 'dark:bg-gray-600');
            toggle.querySelector('span').classList.add('translate-x-5');
            toggle.querySelector('span').classList.remove('translate-x-0');
            input.value = 'active';
            label.textContent = 'Active';
        } else {
            toggle.setAttribute('aria-checked', 'false');
            toggle.classList.remove('bg-green-500');
            toggle.classList.add('bg-gray-300', 'dark:bg-gray-600');
            toggle.querySelector('span').classList.remove('translate-x-5');
            toggle.querySelector('span').classList.add('translate-x-0');
            input.value = 'inactive';
            label.textContent = 'Inactive';
        }
    }

    // Delete staff
    window.confirmDeleteStaff = function (id, name) {
        document.getElementById('delete-staff-name').textContent = name;
        document.getElementById('delete-staff-form').action = '/kinarahub/settings/staff/' + id + '/delete';
        openModal('modal-delete-staff');
    };
})();
</script>
