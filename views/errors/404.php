<?php
/**
 * views/errors/404.php — "Page Not Found" error page.
 *
 * Rendered by the router / error handler when no route matches the request
 * and when a controller explicitly triggers a 404.
 *
 * Sets HTTP response code 404 and renders a full standalone Tailwind page
 * (no sidebar, no authentication required).
 *
 * Usage from a controller or front controller:
 *   http_response_code(404);
 *   require __DIR__ . '/../views/errors/404.php';
 *   exit;
 */

http_response_code(404);

$pageTitle = '404 — Page Not Found | Kinara Store Hub';

// Detect whether the user is authenticated (to show a more helpful link).
$isAuthenticated = !empty($_SESSION['staff_id']);
$homeHref        = $isAuthenticated ? APP_URL . '/dashboard' : APP_URL . '/login';
$homeLabel       = $isAuthenticated ? 'Go to Dashboard' : 'Back to Login';
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
        @keyframes float {
            0%, 100% { transform: translateY(0);    }
            50%       { transform: translateY(-12px); }
        }
        .float-anim { animation: float 4s ease-in-out infinite; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up            { animation: fadeUp 0.5s ease both; }
        .fade-up-delay-1    { animation-delay: 0.1s; }
        .fade-up-delay-2    { animation-delay: 0.2s; }
        .fade-up-delay-3    { animation-delay: 0.3s; }
    </style>
</head>

<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white flex items-center justify-center px-4 py-16">

    <div class="flex flex-col items-center text-center max-w-md mx-auto space-y-6">

        <!-- Floating icon illustration -->
        <div class="float-anim select-none" aria-hidden="true">
            <div class="relative inline-flex items-center justify-center">
                <!-- Background glow -->
                <div class="absolute inset-0 rounded-full bg-brand-100 dark:bg-brand-900/30 blur-3xl opacity-60 scale-150"></div>
                <!-- Icon card -->
                <div class="relative flex h-24 w-24 items-center justify-center rounded-2xl bg-white dark:bg-gray-800 shadow-xl shadow-gray-200/60 dark:shadow-gray-950/60 ring-1 ring-gray-200 dark:ring-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Large 404 numeral -->
        <div class="fade-up">
            <p
                class="text-8xl font-black tracking-tight leading-none select-none text-transparent bg-clip-text bg-gradient-to-br from-brand-500 to-brand-700 dark:from-brand-300 dark:to-brand-500"
                aria-label="Error 404"
            >
                404
            </p>
        </div>

        <!-- Heading + description -->
        <div class="fade-up fade-up-delay-1 space-y-2">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Page not found
            </h1>
            <p class="text-base text-gray-500 dark:text-gray-400 leading-relaxed">
                The page you're looking for doesn't exist or may have been
                moved. Double-check the URL or return home.
            </p>
        </div>

        <!-- Requested URL hint -->
        <?php
        $requestedUri = htmlspecialchars(
            parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '',
            ENT_QUOTES,
            'UTF-8'
        );
        if (!empty($requestedUri) && $requestedUri !== '/'): ?>
        <div class="fade-up fade-up-delay-2 w-full rounded-lg bg-gray-100 dark:bg-gray-800 px-4 py-2.5 text-sm font-mono text-gray-500 dark:text-gray-400 ring-1 ring-gray-200 dark:ring-gray-700 overflow-x-auto text-left">
            <span class="text-gray-400 dark:text-gray-600 select-none">URL: </span><?= $requestedUri ?>
        </div>
        <?php endif; ?>

        <!-- Action buttons -->
        <div class="fade-up fade-up-delay-3 flex flex-col sm:flex-row items-center gap-3">
            <a
                href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 transition-colors duration-150"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
                <?= htmlspecialchars($homeLabel, ENT_QUOTES, 'UTF-8') ?>
            </a>

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
        <p class="fade-up fade-up-delay-3 text-xs text-gray-400 dark:text-gray-600 pt-4">
            Kinara Store Hub &mdash; &copy; <?= date('Y') ?>
        </p>

    </div>

</body>
</html>
