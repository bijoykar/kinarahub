<!DOCTYPE html>
<html lang="en" class="h-full">
<!--
  views/layouts/auth.php — Authentication pages shell.

  Used by: login, register, forgot-password, reset-password.

  Expected variables (all optional):
    $pageTitle  (string) — <title> text. Default: 'Kinara Store Hub'
    $content    (string) — Pre-rendered HTML string to inject into the card body.
    $view       (string) — Absolute path to a view file to include.
-->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle ?? 'Kinara Store Hub', ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Favicon placeholder -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%234f46e5'/><text y='22' x='6' font-size='18' fill='white' font-family='sans-serif' font-weight='bold'>K</text></svg>">

    <!-- Tailwind CSS via CDN -->
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
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.4s ease both',
                        'slide-up': 'slideUp 0.4s ease both',
                    },
                    keyframes: {
                        fadeIn: {
                            from: { opacity: '0' },
                            to:   { opacity: '1' },
                        },
                        slideUp: {
                            from: { opacity: '0', transform: 'translateY(16px)' },
                            to:   { opacity: '1', transform: 'translateY(0)' },
                        },
                    },
                },
            },
        };
    </script>

    <!--
      Dark-mode init: runs synchronously BEFORE body paint to prevent flash.
    -->
    <script>
        (function () {
            var stored = localStorage.getItem('kinarahub_theme');
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
        .no-transition, .no-transition * {
            transition: none !important;
        }
        /* Decorative background pattern */
        .auth-bg-pattern {
            background-color: #f8fafc;
            background-image:
                radial-gradient(circle at 25% 25%, rgba(99, 102, 241, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(79, 70, 229, 0.04) 0%, transparent 50%);
        }
        .dark .auth-bg-pattern {
            background-color: #0f172a;
            background-image:
                radial-gradient(circle at 25% 25%, rgba(99, 102, 241, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(79, 70, 229, 0.08) 0%, transparent 50%);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card-enter {
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
    </style>
</head>

<body class="h-full auth-bg-pattern flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">

    <!-- =========================================================
         AUTH CARD WRAPPER
         ========================================================= -->
    <div class="w-full max-w-md space-y-8 card-enter">

        <!-- Brand / Logo -->
        <div class="flex flex-col items-center text-center">
            <!-- Logo mark -->
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 shadow-lg shadow-brand-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/>
                </svg>
            </div>

            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                Kinara Store Hub
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Inventory &amp; Sales Management
            </p>
        </div>

        <!-- Content card -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 px-6 py-8 shadow-xl shadow-gray-200/60 dark:shadow-gray-950/60 ring-1 ring-gray-200 dark:ring-gray-700 sm:px-8">
            <?php
            if (!empty($view) && file_exists($view)):
                include $view;
            elseif (isset($content)):
                echo $content;
            else:
            ?>
            <!-- Placeholder when no view or content is provided -->
            <p class="text-center text-gray-400 dark:text-gray-600 text-sm py-8">Content not loaded.</p>
            <?php endif; ?>
        </div>

        <!-- Footer note -->
        <p class="text-center text-xs text-gray-400 dark:text-gray-600">
            &copy; <?= date('Y') ?> Kinara Store Hub.
            All rights reserved.
        </p>

    </div>
    <!-- /auth card wrapper -->

    <!-- Toast container (useful for flash messages on auth pages) -->
    <?php include __DIR__ . '/../partials/toast.php'; ?>

    <!-- Suppress transition flicker on load -->
    <script>
        document.body.classList.add('no-transition');
        window.addEventListener('load', function () {
            setTimeout(function () {
                document.body.classList.remove('no-transition');
            }, 50);
        });
    </script>

</body>
</html>
