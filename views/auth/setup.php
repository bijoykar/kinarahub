<?php
/**
 * views/auth/setup.php — Post-verification store setup page.
 *
 * Rendered inside views/layouts/auth.php (uses wider max-w if needed).
 * Allows the store owner to upload a logo and set the store address.
 *
 * Expected variables:
 *   $errors    (array)  — Associative: field => error message. Default: []
 *   $old       (array)  — Associative: field => previous value. Default: []
 *   $csrfToken (string) — CSRF token for the form. Required.
 *   $storeName (string) — The store name for display. Default: ''
 */

$errors    = $errors ?? [];
$old       = $old ?? [];
$csrfToken = $csrfToken ?? '';
$storeName = $storeName ?? '';
?>

<h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Set up your store</h2>
<p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
    <?php if (!empty($storeName)): ?>
    Personalize <span class="font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?></span> with a logo and address
    <?php else: ?>
    Add a logo and address to personalize your store
    <?php endif; ?>
</p>

<form method="POST" action="<?= APP_URL ?>/setup" enctype="multipart/form-data" class="space-y-5" novalidate>
    <?= \App\Middleware\CsrfMiddleware::field() ?>

    <!-- Logo Upload -->
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Store logo <span class="text-gray-400 dark:text-gray-500 font-normal">(optional)</span>
        </label>
        <div class="flex items-center gap-4">
            <!-- Preview circle -->
            <div
                id="logo-preview-wrap"
                class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-700 ring-1 ring-gray-200 dark:ring-gray-600 overflow-hidden"
            >
                <img
                    id="logo-preview-img"
                    src="#"
                    alt="Logo preview"
                    class="h-full w-full object-cover hidden"
                >
                <svg id="logo-placeholder" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/>
                </svg>
            </div>

            <!-- Upload controls -->
            <div class="flex-1 min-w-0">
                <label
                    for="setup-logo"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                    Choose file
                </label>
                <input
                    type="file"
                    id="setup-logo"
                    name="logo"
                    accept="image/png,image/jpeg,image/webp"
                    class="sr-only"
                >
                <p id="logo-filename" class="mt-1.5 text-xs text-gray-400 dark:text-gray-500 truncate">PNG, JPG or WebP. Max 2 MB.</p>
            </div>
        </div>
        <?php if (!empty($errors['logo'])): ?>
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400"><?= htmlspecialchars($errors['logo'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <!-- Divider -->
    <div class="relative">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="bg-white dark:bg-gray-800 px-3 text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Store address</span>
        </div>
    </div>

    <!-- Street Address -->
    <div>
        <label for="setup-street" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
            Street address <span class="text-gray-400 dark:text-gray-500 font-normal">(optional)</span>
        </label>
        <input
            type="text"
            id="setup-street"
            name="address_street"
            value="<?= htmlspecialchars($old['address_street'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            maxlength="255"
            placeholder="Shop No. 12, Main Road"
            class="block w-full rounded-xl border <?= !empty($errors['address_street']) ? 'border-red-400 dark:border-red-500 ring-1 ring-red-400 dark:ring-red-500' : 'border-gray-300 dark:border-gray-600' ?> bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
        >
        <?php if (!empty($errors['address_street'])): ?>
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400"><?= htmlspecialchars($errors['address_street'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <!-- City + State row -->
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label for="setup-city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                City
            </label>
            <input
                type="text"
                id="setup-city"
                name="address_city"
                value="<?= htmlspecialchars($old['address_city'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                maxlength="100"
                placeholder="Mumbai"
                class="block w-full rounded-xl border <?= !empty($errors['address_city']) ? 'border-red-400 dark:border-red-500 ring-1 ring-red-400 dark:ring-red-500' : 'border-gray-300 dark:border-gray-600' ?> bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
            >
            <?php if (!empty($errors['address_city'])): ?>
            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400"><?= htmlspecialchars($errors['address_city'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
        <div>
            <label for="setup-state" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                State
            </label>
            <input
                type="text"
                id="setup-state"
                name="address_state"
                value="<?= htmlspecialchars($old['address_state'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                maxlength="100"
                placeholder="Maharashtra"
                class="block w-full rounded-xl border <?= !empty($errors['address_state']) ? 'border-red-400 dark:border-red-500 ring-1 ring-red-400 dark:ring-red-500' : 'border-gray-300 dark:border-gray-600' ?> bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
            >
            <?php if (!empty($errors['address_state'])): ?>
            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400"><?= htmlspecialchars($errors['address_state'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pincode -->
    <div>
        <label for="setup-pincode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
            Pincode
        </label>
        <input
            type="text"
            id="setup-pincode"
            name="address_pincode"
            value="<?= htmlspecialchars($old['address_pincode'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            maxlength="6"
            pattern="[0-9]{6}"
            inputmode="numeric"
            placeholder="400001"
            oninput="this.value=this.value.replace(/[^0-9]/g,'')"
            class="block w-full rounded-xl border <?= !empty($errors['address_pincode']) ? 'border-red-400 dark:border-red-500 ring-1 ring-red-400 dark:ring-red-500' : 'border-gray-300 dark:border-gray-600' ?> bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
        >
        <?php if (!empty($errors['address_pincode'])): ?>
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400"><?= htmlspecialchars($errors['address_pincode'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <!-- Buttons -->
    <div class="flex flex-col gap-3 pt-2">
        <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800 transition-colors duration-150"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            Save and continue
        </button>
        <a
            href="<?= APP_URL ?>/dashboard"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-white dark:bg-gray-700 px-4 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-300 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150"
        >
            Skip for now
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
            </svg>
        </a>
    </div>
</form>

<script>
(function () {
    'use strict';

    var fileInput   = document.getElementById('setup-logo');
    var previewImg  = document.getElementById('logo-preview-img');
    var placeholder = document.getElementById('logo-placeholder');
    var filename    = document.getElementById('logo-filename');

    if (!fileInput || !previewImg || !placeholder) return;

    fileInput.addEventListener('change', function () {
        var file = fileInput.files[0];
        if (!file) return;

        // Validate file type
        var allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];
        if (allowedTypes.indexOf(file.type) === -1) {
            if (typeof showToast === 'function') {
                showToast('Please select a PNG, JPG, or WebP image.', 'error');
            }
            fileInput.value = '';
            return;
        }

        // Validate file size (2 MB)
        if (file.size > 2 * 1024 * 1024) {
            if (typeof showToast === 'function') {
                showToast('Image must be smaller than 2 MB.', 'error');
            }
            fileInput.value = '';
            return;
        }

        // Show preview
        var reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            previewImg.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);

        // Show filename
        if (filename) {
            filename.textContent = file.name;
        }
    });
})();
</script>
