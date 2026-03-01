<?php
/**
 * views/auth/register.php — Store registration form view.
 *
 * Rendered inside views/layouts/auth.php.
 *
 * Expected variables:
 *   $errors    (array)  — Associative: field => error message. Default: []
 *   $old       (array)  — Associative: field => previous value. Default: []
 *   $csrfToken (string) — CSRF token for the form. Required.
 */

$errors    = $errors ?? [];
$old       = $old ?? [];
$csrfToken = $csrfToken ?? '';
?>

<h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Create your store</h2>
<p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Set up your Kinara Store Hub account in seconds</p>

<form method="POST" action="/kinarahub/register" class="space-y-4" novalidate>
    <?= \App\Middleware\CsrfMiddleware::field() ?>

    <!-- Store Name -->
    <div>
        <label for="reg-store-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
            Store name
        </label>
        <input
            type="text"
            id="reg-store-name"
            name="store_name"
            value="<?= htmlspecialchars($old['store_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            required
            autofocus
            maxlength="100"
            placeholder="e.g. Sharma Kirana Store"
            class="block w-full rounded-xl border <?= !empty($errors['store_name']) ? 'border-red-400 dark:border-red-500 ring-1 ring-red-400 dark:ring-red-500' : 'border-gray-300 dark:border-gray-600' ?> bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
        >
        <?php if (!empty($errors['store_name'])): ?>
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400"><?= htmlspecialchars($errors['store_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <!-- Owner Name -->
    <div>
        <label for="reg-owner-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
            Owner name
        </label>
        <input
            type="text"
            id="reg-owner-name"
            name="owner_name"
            value="<?= htmlspecialchars($old['owner_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            required
            maxlength="100"
            placeholder="Your full name"
            class="block w-full rounded-xl border <?= !empty($errors['owner_name']) ? 'border-red-400 dark:border-red-500 ring-1 ring-red-400 dark:ring-red-500' : 'border-gray-300 dark:border-gray-600' ?> bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
        >
        <?php if (!empty($errors['owner_name'])): ?>
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400"><?= htmlspecialchars($errors['owner_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <!-- Email -->
    <div>
        <label for="reg-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
            Email address
        </label>
        <input
            type="email"
            id="reg-email"
            name="email"
            value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            required
            autocomplete="email"
            placeholder="you@example.com"
            oninput="this.value=this.value.toLowerCase()"
            class="block w-full rounded-xl border <?= !empty($errors['email']) ? 'border-red-400 dark:border-red-500 ring-1 ring-red-400 dark:ring-red-500' : 'border-gray-300 dark:border-gray-600' ?> bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
        >
        <?php if (!empty($errors['email'])): ?>
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <!-- Mobile -->
    <div>
        <label for="reg-mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
            Mobile number
        </label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-gray-400 dark:text-gray-500 select-none pointer-events-none">+91</span>
            <input
                type="tel"
                id="reg-mobile"
                name="mobile"
                value="<?= htmlspecialchars($old['mobile'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                required
                maxlength="10"
                pattern="[0-9]{10}"
                inputmode="numeric"
                placeholder="9876543210"
                oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                class="block w-full rounded-xl border <?= !empty($errors['mobile']) ? 'border-red-400 dark:border-red-500 ring-1 ring-red-400 dark:ring-red-500' : 'border-gray-300 dark:border-gray-600' ?> bg-white dark:bg-gray-700/50 pl-12 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
            >
        </div>
        <?php if (!empty($errors['mobile'])): ?>
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400"><?= htmlspecialchars($errors['mobile'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <!-- Password -->
    <div>
        <label for="reg-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
            Password
        </label>
        <div class="relative">
            <input
                type="password"
                id="reg-password"
                name="password"
                required
                minlength="8"
                autocomplete="new-password"
                placeholder="Min. 8 characters"
                class="block w-full rounded-xl border <?= !empty($errors['password']) ? 'border-red-400 dark:border-red-500 ring-1 ring-red-400 dark:ring-red-500' : 'border-gray-300 dark:border-gray-600' ?> bg-white dark:bg-gray-700/50 px-4 py-2.5 pr-10 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
            >
            <button
                type="button"
                onclick="togglePasswordVisibility('reg-password', this)"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                aria-label="Toggle password visibility"
            >
                <svg class="h-4.5 w-4.5 eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <svg class="h-4.5 w-4.5 eye-closed hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                </svg>
            </button>
        </div>
        <?php if (!empty($errors['password'])): ?>
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <!-- Password strength indicator -->
        <div class="mt-2 space-y-1.5" id="pw-strength-wrap" style="display:none">
            <div class="flex gap-1">
                <div class="h-1 flex-1 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden"><div id="pw-bar-1" class="h-full w-0 rounded-full transition-all duration-300"></div></div>
                <div class="h-1 flex-1 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden"><div id="pw-bar-2" class="h-full w-0 rounded-full transition-all duration-300"></div></div>
                <div class="h-1 flex-1 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden"><div id="pw-bar-3" class="h-full w-0 rounded-full transition-all duration-300"></div></div>
                <div class="h-1 flex-1 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden"><div id="pw-bar-4" class="h-full w-0 rounded-full transition-all duration-300"></div></div>
            </div>
            <p id="pw-strength-label" class="text-xs text-gray-500 dark:text-gray-400"></p>
        </div>
    </div>

    <!-- Confirm Password -->
    <div>
        <label for="reg-password-confirm" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
            Confirm password
        </label>
        <input
            type="password"
            id="reg-password-confirm"
            name="password_confirmation"
            required
            minlength="8"
            autocomplete="new-password"
            placeholder="Re-enter your password"
            class="block w-full rounded-xl border <?= !empty($errors['password_confirmation']) ? 'border-red-400 dark:border-red-500 ring-1 ring-red-400 dark:ring-red-500' : 'border-gray-300 dark:border-gray-600' ?> bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
        >
        <?php if (!empty($errors['password_confirmation'])): ?>
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400"><?= htmlspecialchars($errors['password_confirmation'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <!-- Submit -->
    <button
        type="submit"
        class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800 transition-colors duration-150 mt-6"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/>
        </svg>
        Create store account
    </button>
</form>

<!-- Login link -->
<div class="mt-6 text-center">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Already have an account?
        <a href="/kinarahub/login" class="font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors">
            Sign in
        </a>
    </p>
</div>

<script>
function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    var open = btn.querySelector('.eye-open');
    var closed = btn.querySelector('.eye-closed');
    if (open && closed) {
        open.classList.toggle('hidden', isPassword);
        closed.classList.toggle('hidden', !isPassword);
    }
}

(function () {
    'use strict';
    var pw = document.getElementById('reg-password');
    var wrap = document.getElementById('pw-strength-wrap');
    var label = document.getElementById('pw-strength-label');
    var bars = [
        document.getElementById('pw-bar-1'),
        document.getElementById('pw-bar-2'),
        document.getElementById('pw-bar-3'),
        document.getElementById('pw-bar-4')
    ];

    var levels = [
        { min: 0, color: 'bg-red-500',    text: 'Weak' },
        { min: 1, color: 'bg-amber-500',  text: 'Fair' },
        { min: 2, color: 'bg-yellow-500', text: 'Good' },
        { min: 3, color: 'bg-green-500',  text: 'Strong' },
    ];

    function calcStrength(val) {
        var score = 0;
        if (val.length >= 8) score++;
        if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^a-zA-Z0-9]/.test(val)) score++;
        return score;
    }

    if (pw) {
        pw.addEventListener('input', function () {
            var val = pw.value;
            if (!val) {
                wrap.style.display = 'none';
                return;
            }
            wrap.style.display = '';
            var score = calcStrength(val);
            var lvl = levels[Math.min(score, levels.length - 1)];

            bars.forEach(function (bar, i) {
                if (i < score) {
                    bar.style.width = '100%';
                    bar.className = 'h-full rounded-full transition-all duration-300 ' + lvl.color;
                } else {
                    bar.style.width = '0';
                    bar.className = 'h-full w-0 rounded-full transition-all duration-300';
                }
            });
            label.textContent = lvl.text;
        });
    }

    // Confirm password match check
    var confirm = document.getElementById('reg-password-confirm');
    if (confirm && pw) {
        confirm.addEventListener('input', function () {
            if (confirm.value && confirm.value !== pw.value) {
                confirm.setCustomValidity('Passwords do not match');
            } else {
                confirm.setCustomValidity('');
            }
        });
    }
})();
</script>
