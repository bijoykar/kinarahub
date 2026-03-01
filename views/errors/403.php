<?php
/**
 * views/errors/403.php — "Access Denied / Forbidden" error page.
 *
 * Rendered by middleware or controllers when the authenticated user lacks
 * the required permission to access the requested resource.
 *
 * Sets HTTP 403 and renders a full standalone Tailwind page (no sidebar).
 *
 * Usage from a controller or middleware:
 *   http_response_code(403);
 *   require __DIR__ . '/../views/errors/403.php';
 *   exit;
 */

http_response_code(403);

$pageTitle = '403 — Access Denied | Kinara Store Hub';

$isAuthenticated = !empty($_SESSION['staff_id']);
$dashboardHref   = APP_URL . '/dashboard';
$loginHref       = APP_URL . '/login';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Favicon placeholder -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%234f46e5'/><text y='22' x='6' font-size='18' fill='white' font-family='sans-serif' font-weight='bold'>K</text></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:  '#eef2ff',
                            100: '#e0e7ff',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#312e81',
                        },
                    },
                },
            },
        };
    </script>

    <!-- Dark mode init: prevent flash of wrong theme -->
    <script>
        (function () {
            var stored = localStorage.getItem('kinarahub_theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (stored === null && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <style>
        *, *::before, *::after {
            transition-property: color, background-color, border-color;
            transition-duration: 200ms;
            transition-timing-function: ease;
        }
        @keyframes pulse-ring {
            0%   { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70%  { transform: scale(1);    box-shadow: 0 0 0 16px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .pulse-ring { animation: pulse-ring 2.5s ease-in-out infinite; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up         { animation: fadeUp 0.5s ease both; }
        .fade-up-d1      { animation-delay: 0.1s; }
        .fade-up-d2      { animation-delay: 0.2s; }
        .fade-up-d3      { animation-delay: 0.3s; }
    </style>
</head>

<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white flex items-center justify-center px-4 py-16">

    <div class="flex flex-col items-center text-center max-w-md mx-auto space-y-6">

        <!-- Lock icon with pulse ring -->
        <div class="relative select-none" aria-hidden="true">
            <!-- Outer glow -->
            <div class="absolute inset-0 rounded-full bg-red-100 dark:bg-red-900/20 blur-3xl opacity-70 scale-150"></div>
            <!-- Pulse ring -->
            <div class="relative pulse-ring flex h-24 w-24 items-center justify-center rounded-2xl bg-white dark:bg-gray-800 shadow-xl shadow-gray-200/60 dark:shadow-gray-950/60 ring-1 ring-red-200 dark:ring-red-800/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                </svg>
            </div>
        </div>

        <!-- Large 403 numeral -->
        <div class="fade-up">
            <p
                class="text-8xl font-black tracking-tight leading-none select-none text-transparent bg-clip-text bg-gradient-to-br from-red-500 to-red-700 dark:from-red-400 dark:to-red-600"
                aria-label="Error 403"
            >
                403
            </p>
        </div>

        <!-- Heading + description -->
        <div class="fade-up fade-up-d1 space-y-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Access denied
            </h1>
            <p class="text-base text-gray-500 dark:text-gray-400 leading-relaxed">
                You don't have permission to view this page.
                <?php if ($isAuthenticated): ?>
                If you believe this is a mistake, contact your store owner or
                manager to update your role permissions.
                <?php else: ?>
                Please sign in to continue.
                <?php endif; ?>
            </p>
        </div>

        <!-- Contextual info card -->
        <?php if ($isAuthenticated && !empty($_SESSION['staff_name'])): ?>
        <div class="fade-up fade-up-d2 w-full rounded-xl bg-white dark:bg-gray-800 px-4 py-3 ring-1 ring-gray-200 dark:ring-gray-700 text-sm text-left space-y-1.5">
            <div class="flex items-center justify-between gap-2">
                <span class="text-gray-500 dark:text-gray-400">Signed in as</span>
                <span class="font-medium text-gray-800 dark:text-gray-200">
                    <?= htmlspecialchars($_SESSION['staff_name'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <?php if (!empty($_SESSION['store_name'])): ?>
            <div class="flex items-center justify-between gap-2">
                <span class="text-gray-500 dark:text-gray-400">Store</span>
                <span class="font-medium text-gray-800 dark:text-gray-200">
                    <?= htmlspecialchars($_SESSION['store_name'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <?php endif; ?>
            <div class="flex items-center justify-between gap-2">
                <span class="text-gray-500 dark:text-gray-400">Role</span>
                <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">
                    <?= htmlspecialchars(match ((int)($_SESSION['role_id'] ?? 0)) {
                        1 => 'Owner',
                        2 => 'Manager',
                        3 => 'Staff',
                        default => 'Staff',
                    }, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action buttons -->
        <div class="fade-up fade-up-d3 flex flex-col sm:flex-row items-center gap-3">
            <?php if ($isAuthenticated): ?>
            <a
                href="<?= htmlspecialchars($dashboardHref, ENT_QUOTES, 'UTF-8') ?>"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 transition-colors duration-150"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
                Go to Dashboard
            </a>
            <?php else: ?>
            <a
                href="<?= htmlspecialchars($loginHref, ENT_QUOTES, 'UTF-8') ?>"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 transition-colors duration-150"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                </svg>
                Sign In
            </a>
            <?php endif; ?>

            <button
                type="button"
                onclick="history.back()"
                class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 transition-colors duration-150"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Go Back
            </button>
        </div>

        <!-- Branding footer -->
        <p class="fade-up fade-up-d3 text-xs text-gray-400 dark:text-gray-600 pt-4">
            Kinara Store Hub &mdash; &copy; <?= date('Y') ?>
        </p>

    </div>

</body>
</html>
