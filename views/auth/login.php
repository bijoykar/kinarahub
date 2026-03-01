<?php
/**
 * views/auth/login.php — Login form view.
 *
 * Rendered inside views/layouts/auth.php.
 *
 * Expected variables:
 *   $errors       (array)  — Associative: field => error message. Default: []
 *   $old          (array)  — Associative: field => previous value. Default: []
 *   $csrfToken    (string) — CSRF token for the form. Required.
 *   $errorMessage (string) — General error message (e.g. "Invalid credentials"). Default: ''
 */

$errors       = $errors ?? [];
$old          = $old ?? [];
$csrfToken    = $csrfToken ?? '';
$errorMessage = $errorMessage ?? '';
?>

<!-- General error banner -->
<?php if (!empty($errorMessage)): ?>
<div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 dark:border-red-700/50 bg-red-50 dark:bg-red-900/20 px-4 py-3" role="alert">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-red-500 dark:text-red-400 mt-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
    </svg>
    <p class="text-sm font-medium text-red-800 dark:text-red-200"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php endif; ?>

<h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Welcome back</h2>
<p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Sign in to your store account</p>

<form method="POST" action="/kinarahub/login" class="space-y-5" novalidate>
    <?= \App\Middleware\CsrfMiddleware::field() ?>

    <!-- Email -->
    <div>
        <label for="login-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
            Email address
        </label>
        <input
            type="email"
            id="login-email"
            name="email"
            value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            required
            autofocus
            autocomplete="email"
            placeholder="you@example.com"
            oninput="this.value=this.value.toLowerCase()"
            class="block w-full rounded-xl border <?= !empty($errors['email']) ? 'border-red-400 dark:border-red-500 ring-1 ring-red-400 dark:ring-red-500' : 'border-gray-300 dark:border-gray-600' ?> bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
        >
        <?php if (!empty($errors['email'])): ?>
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <!-- Password -->
    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label for="login-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Password
            </label>
            <a href="/kinarahub/forgot-password" class="text-xs font-medium text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors">
                Forgot password?
            </a>
        </div>
        <div class="relative">
            <input
                type="password"
                id="login-password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
                class="block w-full rounded-xl border <?= !empty($errors['password']) ? 'border-red-400 dark:border-red-500 ring-1 ring-red-400 dark:ring-red-500' : 'border-gray-300 dark:border-gray-600' ?> bg-white dark:bg-gray-700/50 px-4 py-2.5 pr-10 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
            >
            <button
                type="button"
                onclick="togglePasswordVisibility('login-password', this)"
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
    </div>

    <!-- Submit -->
    <button
        type="submit"
        class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800 transition-colors duration-150"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
        </svg>
        Sign in
    </button>
</form>

<!-- Register link -->
<div class="mt-6 text-center">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Don't have an account?
        <a href="/kinarahub/register" class="font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors">
            Register your store
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
</script>
