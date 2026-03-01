<?php
/**
 * views/auth/verify-pending.php — Email verification pending page.
 *
 * Rendered inside views/layouts/auth.php.
 *
 * Expected variables:
 *   $email      (string) — The email address the verification was sent to.
 *   $csrfToken  (string) — CSRF token for the resend form.
 *   $resent     (bool)   — Whether a resend was just triggered. Default: false
 */

$email     = $email ?? '';
$csrfToken = $csrfToken ?? '';
$resent    = $resent ?? false;
?>

<div class="text-center">
    <!-- Email icon -->
    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 dark:bg-brand-900/20 ring-1 ring-brand-200 dark:ring-brand-700/40">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
        </svg>
    </div>

    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Check your inbox</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed mb-1">
        We've sent a verification link to
    </p>
    <?php if (!empty($email)): ?>
    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">
        <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
    </p>
    <?php endif; ?>
    <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed mb-6">
        Click the link in the email to verify your account. If you don't see it, check your spam folder.
    </p>

    <?php if ($resent): ?>
    <div class="mb-5 flex items-center justify-center gap-2 rounded-xl border border-green-200 dark:border-green-700/50 bg-green-50 dark:bg-green-900/20 px-4 py-3" role="status">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-green-500 dark:text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
        </svg>
        <p class="text-sm font-medium text-green-800 dark:text-green-200">Verification email resent</p>
    </div>
    <?php endif; ?>

    <!-- Resend form -->
    <form method="POST" action="/kinarahub/verify/resend" class="mb-4">
        <?= \App\Middleware\CsrfMiddleware::field() ?>
        <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
        <button
            type="submit"
            id="resend-btn"
            class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-gray-700 px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/>
            </svg>
            <span id="resend-text">Resend verification email</span>
        </button>
    </form>

    <!-- Back to login -->
    <a href="/kinarahub/login" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Back to sign in
    </a>
</div>

<script>
(function () {
    'use strict';
    // Cooldown: disable resend button for 60 seconds after click
    var btn = document.getElementById('resend-btn');
    var txt = document.getElementById('resend-text');
    if (!btn) return;

    var form = btn.closest('form');
    if (form) {
        form.addEventListener('submit', function () {
            btn.disabled = true;
            var remaining = 60;
            txt.textContent = 'Resend in ' + remaining + 's';

            var interval = setInterval(function () {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(interval);
                    btn.disabled = false;
                    txt.textContent = 'Resend verification email';
                } else {
                    txt.textContent = 'Resend in ' + remaining + 's';
                }
            }, 1000);
        });
    }
})();
</script>
