<!DOCTYPE html>
<html lang="en" class="h-full">
<!--
  views/admin/login.php — Platform admin login page.

  Standalone page (does NOT use a layout file).
  Different branding from store login — "Kinara Hub Admin" with red accent.

  Expected variables:
    $error     (string|null) — Error message from failed login attempt.
    $csrfToken (string)      — CSRF token for the form.
-->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login — Kinara Hub</title>

    <!-- Favicon — red accent for admin -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%23dc2626'/><text y='22' x='6' font-size='18' fill='white' font-family='sans-serif' font-weight='bold'>A</text></svg>">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        admin: {
                            50:  '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        },
                    },
                },
            },
        };
    </script>

    <!-- Dark-mode init -->
    <script>
        (function () {
            var stored = localStorage.getItem('kinarahub_admin_theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (stored === null && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <style>
        *, *::before, *::after {
            transition-property: color, background-color, border-color, box-shadow;
            transition-duration: 200ms;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }
        .no-transition, .no-transition * { transition: none !important; }
        .admin-bg-pattern {
            background-color: #f8fafc;
            background-image:
                radial-gradient(circle at 25% 25%, rgba(220, 38, 38, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(185, 28, 28, 0.04) 0%, transparent 50%);
        }
        .dark .admin-bg-pattern {
            background-color: #0f172a;
            background-image:
                radial-gradient(circle at 25% 25%, rgba(220, 38, 38, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(185, 28, 28, 0.08) 0%, transparent 50%);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card-enter { animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) both; }
    </style>
</head>

<?php
    $error     = $error ?? null;
    $csrfToken = $csrfToken ?? '';
?>

<body class="h-full admin-bg-pattern flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">

    <div class="w-full max-w-md space-y-8 card-enter">

        <!-- Brand / Logo -->
        <div class="flex flex-col items-center text-center">
            <!-- Logo mark (red for admin) -->
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-admin-600 shadow-lg shadow-admin-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
            </div>

            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                Kinara Hub Admin
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Platform Administration
            </p>
        </div>

        <!-- Login card -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 px-6 py-8 shadow-xl shadow-gray-200/60 dark:shadow-gray-950/60 ring-1 ring-gray-200 dark:ring-gray-700 sm:px-8">

            <?php if ($error): ?>
            <div class="mb-5 flex items-center gap-2.5 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 px-4 py-3 text-sm text-red-700 dark:text-red-300" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                </svg>
                <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= APP_URL ?>/admin/login" class="space-y-5">
                <?= \App\Middleware\CsrfMiddleware::field() ?>

                <!-- Email -->
                <div>
                    <label for="admin-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email address</label>
                    <input
                        type="email"
                        id="admin-email"
                        name="email"
                        required
                        autofocus
                        autocomplete="email"
                        oninput="this.value=this.value.toLowerCase()"
                        placeholder="admin@kinarahub.com"
                        class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 shadow-sm focus:border-admin-500 focus:ring-2 focus:ring-admin-500/30 focus:outline-none transition-colors"
                    >
                </div>

                <!-- Password -->
                <div>
                    <label for="admin-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            id="admin-password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 pr-11 text-sm text-gray-900 dark:text-white placeholder-gray-400 shadow-sm focus:border-admin-500 focus:ring-2 focus:ring-admin-500/30 focus:outline-none transition-colors"
                        >
                        <button
                            type="button"
                            onclick="toggleAdminPw()"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                            aria-label="Toggle password visibility"
                        >
                            <svg id="pw-eye-open" xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <svg id="pw-eye-closed" xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    class="flex w-full justify-center rounded-xl bg-admin-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-admin-600/30 hover:bg-admin-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-admin-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800 transition-colors duration-150"
                >
                    Sign in
                </button>
            </form>
        </div>

        <!-- Footer note -->
        <p class="text-center text-xs text-gray-400 dark:text-gray-600">
            &copy; <?= date('Y') ?> Kinara Store Hub &mdash; Platform Admin
        </p>

    </div>

    <!-- Dark mode toggle (floating) -->
    <button
        id="admin-login-dark-toggle"
        type="button"
        class="fixed top-4 right-4 rounded-lg p-2 text-gray-400 hover:bg-white/80 dark:hover:bg-gray-800/80 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
        aria-label="Toggle dark mode"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
    </button>

    <script>
    (function () {
        'use strict';

        // Password toggle
        window.toggleAdminPw = function () {
            var input = document.getElementById('admin-password');
            var eyeOpen = document.getElementById('pw-eye-open');
            var eyeClosed = document.getElementById('pw-eye-closed');
            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        };

        // Dark mode toggle (login page)
        var toggleBtn = document.getElementById('admin-login-dark-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                var isDark = document.documentElement.classList.toggle('dark');
                try {
                    localStorage.setItem('kinarahub_admin_theme', isDark ? 'dark' : 'light');
                } catch (_) {}
            });
        }

        // Suppress transition flicker
        document.body.classList.add('no-transition');
        window.addEventListener('load', function () {
            setTimeout(function () {
                document.body.classList.remove('no-transition');
            }, 50);
        });
    })();
    </script>

</body>
</html>
